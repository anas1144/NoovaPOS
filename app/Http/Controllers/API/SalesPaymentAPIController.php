<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Http\Requests\CreateSalePaymentRequest;
use App\Http\Resources\SalesPaymentResource;
use App\Models\Sale;
use App\Models\SalesPayment;
use App\Repositories\SalesPaymentRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class SalesPaymentAPIController extends AppBaseController
{
    /** @var SalesPaymentRepository */
    private $salesPaymentRepository;

    /**
     * SalesPaymentAPIController constructor.
     */
    public function __construct(SalesPaymentRepository $salesPaymentRepository)
    {
        $this->salesPaymentRepository = $salesPaymentRepository;
    }

    /**
     * @return array
     */
    public function getAllPayments(Sale $sale)
    {
        $data = [
            'sale_id' => $sale->id,
            'data' => $sale->payments,
        ];

        return $data;
    }

    public function createSalePayment(Sale $sale, CreateSalePaymentRequest $request): SalesPaymentResource
    {
        $input = $request->all();

        $salePayment = $this->salesPaymentRepository->storeSalePayment($input, $sale);

        return new SalesPaymentResource($salePayment);
    }

    public function updateSalePayment(SalesPayment $salesPayment, Request $request): SalesPaymentResource
    {
        $input = $request->all();

        $salePayment = $this->salesPaymentRepository->updateSalePayment($input, $salesPayment);

        return new SalesPaymentResource($salePayment);
    }

    public function deletePayment($id)
    {
        try {
            DB::beginTransaction();

            $salePayment = SalesPayment::whereId($id)->firstOrFail();
            $saleID = $salePayment->sale_id;

            $existAmount = SalesPayment::whereSaleId($saleID)->sum('amount') - $salePayment->amount;
            $sale = Sale::findOrFail($saleID);
            $status = Sale::UNPAID;
            if (round($existAmount, 2) > 0 && round($existAmount, 2) < round($sale->grand_total, 2)) {
                $status = Sale::PARTIAL_PAID;
            } elseif (round($existAmount, 2) >= round($sale->grand_total, 2)) {
                $status = Sale::PAID;
            }

            Sale::whereId($saleID)->update([
                'payment_status' => $status,
                'paid_amount' => $existAmount,
            ]);

            SalesPayment::findOrFail($id)->delete();

            $latestPayment = SalesPayment::whereSaleId($saleID)->latest()->first();

            Sale::whereId($saleID)->update([
                'payment_type' => ! empty($latestPayment) ? $latestPayment->payment_type : null,
            ]);

            DB::commit();

            return $this->sendSuccess('Payment deleted successfully');
        } catch (Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }
}
