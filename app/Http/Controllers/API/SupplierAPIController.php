<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Http\Requests\CreateSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Http\Resources\SupplierCollection;
use App\Http\Resources\SupplierResource;
use App\Imports\SupplierImport;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Repositories\SupplierRepository;
use Intervention\Image\Facades\Image;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;
use Barryvdh\DomPDF\Facade\Pdf as CPDF;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Prettus\Validator\Exceptions\ValidatorException;

/**
 * Class SupplierAPIController
 */
class SupplierAPIController extends AppBaseController
{
    /** @var SupplierRepository */
    private $supplierRepository;

    public function __construct(SupplierRepository $supplierRepository)
    {
        $this->supplierRepository = $supplierRepository;
    }

    public function index(Request $request): SupplierCollection
    {
        $perPage = getPageSize($request);
        $suppliers = $this->supplierRepository->paginate($perPage);
        SupplierResource::usingWithCollection();

        return new SupplierCollection($suppliers);
    }

    /**
     * @throws ValidatorException
     */
    public function store(CreateSupplierRequest $request): SupplierResource
    {
        $input = $request->all();
        $supplier = $this->supplierRepository->create($input);

        return new SupplierResource($supplier);
    }

    public function show($id): SupplierResource
    {
        $supplier = $this->supplierRepository->find($id);

        return new SupplierResource($supplier);
    }

    /**
     * @throws ValidatorException
     */
    public function update(UpdateSupplierRequest $request, $id): SupplierResource
    {
        $input = $request->all();
        $supplier = $this->supplierRepository->update($input, $id);

        return new SupplierResource($supplier);
    }

    public function destroy($id): JsonResponse
    {
        $purchaseModel = [
            Purchase::class,
        ];
        $useSupplier = canDelete($purchaseModel, 'supplier_id', $id);
        if ($useSupplier) {
            return $this->sendError(__('messages.error.supplier_cant_delete'));
        }
        $this->supplierRepository->delete($id);

        return $this->sendSuccess('Supplier deleted successfully');
    }

    public function importSuppliers(Request $request): JsonResponse
    {
        Excel::import(new SupplierImport(), request()->file('file'));

        return $this->sendSuccess('Suppliers imported successfully');
    }

    public function pdfDownload(Supplier $supplier): JsonResponse
    {
        ini_set('memory_limit', '-1');
        $supplier = $supplier->load('purchases');

        $purchasesData = [];

        $purchasesData['totalPurchase'] = $supplier->purchases->count();

        $purchasesData['totalAmount'] = $supplier->purchases->sum('grand_total');

        $data = [];

        if (Storage::exists('pdf/suppliers-report-' . $supplier->id . '.pdf')) {
            Storage::delete('pdf/suppliers-report-' . $supplier->id . '.pdf');
        }

        $companyLogo = getLogoUrl();

        $companyLogo = (string) Image::make($companyLogo)->encode('data-url');

        $pdfViewPath = getLoginUserLanguage() == 'ar' ? 'pdf.ar.suppliers-report-pdf' : 'pdf.suppliers-report-pdf';
        if(getLoginUserLanguage() == 'ar'){
            $pdf = PDF::loadView($pdfViewPath, compact('supplier', 'companyLogo', 'purchasesData'));
        }else{
            $pdf = CPDF::loadView($pdfViewPath, compact('supplier', 'companyLogo', 'purchasesData'));
        }
        Storage::disk(config('app.media_disc'))->put('pdf/suppliers-report-' . $supplier->id . '.pdf', $pdf->output());
        $data['suppliers_report_pdf_url'] = Storage::url('pdf/suppliers-report-' . $supplier->id . '.pdf');

        return $this->sendResponse($data, 'pdf retrieved Successfully');
    }
}
