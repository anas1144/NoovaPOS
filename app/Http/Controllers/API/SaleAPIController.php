<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Http\Requests\CreateSaleRequest;
use App\Http\Requests\UpdateSaleRequest;
use App\Http\Resources\SaleCollection;
use App\Http\Resources\SaleResource;
use App\Models\Customer;
use App\Models\Hold;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\Tax;
use App\Models\Warehouse;
use App\Repositories\SaleRepository;
use Barryvdh\DomPDF\Facade\Pdf as CPDF;
use Intervention\Image\Facades\Image;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use App\Models\Shop;
use App\Services\FbrSubmissionService;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Class SaleAPIController
 */
class SaleAPIController extends AppBaseController
{
    /** @var saleRepository */
    private $saleRepository;

    public function __construct(SaleRepository $saleRepository)
    {
        $this->saleRepository = $saleRepository;
    }

    public function index(Request $request): SaleCollection
    {
        $perPage = getPageSize($request);
        $search = $request->filter['search'] ?? '';
        $customer = (Customer::where('name', 'LIKE', "%$search%")->get()->count() != 0);
        $warehouse = (Warehouse::where('name', 'LIKE', "%$search%")->get()->count() != 0);
        $products = (Product::where('name', 'LIKE', "%$search%")->orWhere('code', 'LIKE', "%$search%")->get()->count() != 0);

        $sales = $this->saleRepository;
        if ($customer || $warehouse || $products) {
            $sales->whereHas('customer', function (Builder $q) use ($search, $customer) {
                if ($customer) {
                    $q->where('name', 'LIKE', "%$search%");
                }
            })->whereHas('warehouse', function (Builder $q) use ($search, $warehouse) {
                if ($warehouse) {
                    $q->where('name', 'LIKE', "%$search%");
                }
            })->whereHas('saleItems', function (Builder $q) use ($search) {
                $q->whereHas('product', function (Builder $q) use ($search) {
                    $q->where('name', 'LIKE', "%$search%")
                        ->orWhere('code', 'LIKE', "%$search%");
                });
            });
        }

        if ($request->get('start_date') && $request->get('end_date')) {
            $sales->whereBetween('date', [$request->get('start_date'), $request->get('end_date')]);
        }

        if ($request->get('warehouse_id')) {
            $sales->where('warehouse_id', $request->get('warehouse_id'));
        }

        if ($request->get('customer_id')) {
            $sales->where('customer_id', $request->get('customer_id'));
        }

        if ($request->get('status') && $request->get('status') != 'null') {
            $sales->Where('status', $request->get('status'));
        }

        if ($request->get('payment_status') && $request->get('payment_status') != 'null') {
            $sales->where('payment_status', $request->get('payment_status'));
        }

        if ($request->get('payment_type') && $request->get('payment_type') != 'null') {
            $sales->where('payment_type', $request->get('payment_type'));
        }

        $sales = $sales->paginate($perPage);

        SaleResource::usingWithCollection();

        return new SaleCollection($sales);
    }

    public function store(CreateSaleRequest $request): SaleResource
    {
        if (isset($request->hold_ref_no)) {
            $holdExist = Hold::whereReferenceCode($request->hold_ref_no)->first();
            if (!empty($holdExist)) {
                $holdExist->delete();
            }
        }
        $input = $request->all();

        if (isset($input['payment_status']) && $input['payment_status'] != Sale::UNPAID) {
            $grand_total = floatval($input['grand_total'] ?? 0);
            $paymentDetails = $input['payment_details'] ?? [];

            if (empty($paymentDetails) || !is_array($paymentDetails)) {
                throw new UnprocessableEntityHttpException('Payment details are required when payment status is PAID.');
            }

            $totalAmount = collect($paymentDetails)->sum(function ($detail) {
                return floatval($detail['amount'] ?? 0);
            });

            // if ($totalAmount > $grand_total) {
            //     throw new UnprocessableEntityHttpException('Total payment amount cannot be greater than the grand total.');
            // }

            // if ($totalAmount < $grand_total) {
            //     throw new UnprocessableEntityHttpException('Total payment amount should be equal to grand total.');
            // }
        }

        $sale = $this->saleRepository->storeSale($input);

        // ── FBR POS submission (Pakistan shops only) ───────────────────────
        // Attempt synchronously so the FBR invoice number can be included in
        // the response and printed on the receipt immediately.
        // If the shop is not PK / FBR disabled, this is a no-op.
        $fbrInvoice = null;
        try {
            $shop = isset($input['shop_id'])
                ? Shop::find($input['shop_id'])
                : null;

            $fbrService = app(FbrSubmissionService::class);
            $fbrInvoice = $fbrService->submitForSale($sale->load('saleItems.product', 'customer'), $shop);
        } catch (\Throwable $e) {
            // Never fail the sale because of FBR — it can be retried from queue
            \Log::warning('FBR submission error on sale ' . $sale->id . ': ' . $e->getMessage());
        }

        $resource = new SaleResource($sale);

        // Attach FBR info to response so POS receipt can display it
        if ($fbrInvoice) {
            $resource->additional([
                'fbr' => [
                    'fbr_invoice_id'     => $fbrInvoice->id,
                    'fbr_invoice_number' => $fbrInvoice->fbr_invoice_number,
                    'usin'               => $fbrInvoice->invoice_no,
                    'status'             => $fbrInvoice->status,
                    'fbr_code'           => $fbrInvoice->fbr_code,
                    'fbr_response'       => $fbrInvoice->fbr_response,
                    'qr_payload'         => $fbrInvoice->fbr_qr_payload,
                    // What to print: FBR number if synced, else USIN with pending flag
                    'printable_number'   => $fbrInvoice->fbr_invoice_number ?? $fbrInvoice->invoice_no,
                    'is_pending'         => !$fbrInvoice->isSynced(),
                ],
            ]);
        }

        return $resource;
    }

    public function show($id): SaleResource
    {
        $sale = $this->saleRepository->find($id);

        return new SaleResource($sale);
    }

    public function edit(Sale $sale): SaleResource
    {
        $sale = $sale->load('saleItems.product.stocks', 'warehouse');

        return new SaleResource($sale);
    }

    public function update(UpdateSaleRequest $request, $id): SaleResource
    {
        $input = $request->all();

        if (isset($input['payment_status']) && $input['payment_status'] != Sale::UNPAID) {
            $grand_total = floatval($input['grand_total'] ?? 0);
            $paymentDetails = $input['payment_details'] ?? [];

            if (empty($paymentDetails) || !is_array($paymentDetails)) {
                throw new UnprocessableEntityHttpException('Payment details are required when payment status is PAID.');
            }

            $totalAmount = collect($paymentDetails)->sum(function ($detail) {
                return floatval($detail['amount'] ?? 0);
            });

            // if ($totalAmount > $grand_total) {
            //     throw new UnprocessableEntityHttpException('Total payment amount cannot be greater than the grand total.');
            // }

            // if ($totalAmount < $grand_total) {
            //     throw new UnprocessableEntityHttpException('Total payment amount should be equal to grand total.');
            // }
        }

        $sale = $this->saleRepository->updateSale($input, $id);

        return new SaleResource($sale);
    }

    public function destroy($id): JsonResponse
    {
        try {
            DB::beginTransaction();
            $sale = $this->saleRepository->with('saleItems')->where('id', $id)->first();
            foreach ($sale->saleItems as $saleItem) {
                manageStock($sale->warehouse_id, $saleItem['product_id'], $saleItem['quantity']);
            }
            if (File::exists(Storage::path('sales/barcode-' . $sale->reference_code . '.png'))) {
                File::delete(Storage::path('sales/barcode-' . $sale->reference_code . '.png'));
            }
            $this->saleRepository->delete($id);
            DB::commit();

            return $this->sendSuccess('Sale Deleted successfully');
        } catch (Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    /**
     * @throws \Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist
     * @throws \Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig
     */
    public function pdfDownload(Sale $sale): JsonResponse
    {
        ini_set('memory_limit', '-1');
        $sale = $sale->load('customer', 'saleItems.product', 'payments');
        $data = [];
        if (Storage::exists('pdf/Sale-' . $sale->reference_code . '.pdf')) {
            Storage::delete('pdf/Sale-' . $sale->reference_code . '.pdf');
        }
        $companyLogo = getLogoUrl();

        $companyLogo = (string) Image::make($companyLogo)->encode('data-url');

        $taxes = Tax::where('status', 1)->get();

        $pdfViewPath = getLoginUserLanguage() == 'ar' ? 'pdf.ar.sale-pdf' : 'pdf.sale-pdf';
        if(getLoginUserLanguage() == 'ar'){
            $pdf = PDF::loadView($pdfViewPath, compact('sale', 'companyLogo', 'taxes'));
        }else{
            $pdf = CPDF::loadView($pdfViewPath, compact('sale', 'companyLogo', 'taxes'));
        }
        Storage::disk(config('app.media_disc'))->put('pdf/Sale-' . $sale->reference_code . '.pdf', $pdf->output());
        $data['sale_pdf_url'] = Storage::url('pdf/Sale-' . $sale->reference_code . '.pdf');

        return $this->sendResponse($data, 'pdf retrieved Successfully');
    }

    public function saleInfo(Sale $sale): JsonResponse
    {
        $sale = $sale->load('saleItems.product', 'warehouse', 'customer', 'payments');
        $keyName = [
            'email',
            'company_name',
            'phone',
            'address',
        ];
        $company_info = Setting::whereIn('key', $keyName)->pluck('value', 'key')->toArray();
        if (getActiveStoreName()) {
            $company_info['company_name'] = getActiveStoreName();
        }

        $sale['company_info'] = $company_info;
        $sale['barcode_url'] = Storage::url('sales/barcode-' . $sale->reference_code . '.png');
        return $this->sendResponse($sale, 'Sale information retrieved successfully');
    }

    public function getSaleProductReport(Request $request): SaleCollection
    {
        $perPage = getPageSize($request);
        $productId = $request->get('product_id');
        $sales = $this->saleRepository->whereHas('saleItems', function ($q) use ($productId) {
            $q->where('product_id', '=', $productId);
        })->with(['saleItems.product', 'customer']);

        $sales = $sales->paginate($perPage);

        SaleResource::usingWithCollection();

        return new SaleCollection($sales);
    }

    /**
     * Post a sale - Apply stock changes and mark as posted
     */
    public function post(int $id): SaleResource
    {
        $sale = $this->saleRepository->postSale($id);

        return new SaleResource($sale);
    }
}