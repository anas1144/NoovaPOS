<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\BankAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Super-admin management of platform payout bank accounts (per country).
 */
class PlatformBankAccountController extends AppBaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = BankAccount::query()->orderBy('country')->orderBy('bank_name');

        if ($country = $request->get('country')) {
            $query->where('country', $country);
        }

        return $this->sendResponse($query->get(), 'Bank accounts retrieved successfully.');
    }

    public function store(Request $request): JsonResponse
    {
        $account = BankAccount::create($this->validatePayload($request));

        return $this->sendResponse($account, 'Bank account created successfully.');
    }

    public function update(Request $request, BankAccount $bankAccount): JsonResponse
    {
        $bankAccount->update($this->validatePayload($request));

        return $this->sendResponse($bankAccount, 'Bank account updated successfully.');
    }

    public function destroy(BankAccount $bankAccount): JsonResponse
    {
        $bankAccount->delete();

        return $this->sendSuccess('Bank account deleted successfully.');
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'country'        => 'required|string|max:5',
            'bank_name'      => 'required|string|max:191',
            'account_title'  => 'required|string|max:191',
            'account_number' => 'nullable|string|max:64',
            'iban'           => 'nullable|string|max:64',
            'swift'          => 'nullable|string|max:32',
            'currency'       => 'nullable|string|max:8',
            'instructions'   => 'nullable|string|max:2000',
            'status'         => 'nullable|boolean',
        ]);
    }
}
