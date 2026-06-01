<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Expense;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AutoJournalService
{
    /**
     * Default chart of accounts seeded per tenant the first time auto-journal runs.
     * code => [name, type]
     */
    private const DEFAULT_COA = [
        '1000' => ['Cash', Account::TYPE_ASSET],
        '1010' => ['Bank', Account::TYPE_ASSET],
        '1100' => ['Accounts Receivable', Account::TYPE_ASSET],
        '1200' => ['Inventory', Account::TYPE_ASSET],
        '2000' => ['Accounts Payable', Account::TYPE_LIABILITY],
        '2100' => ['Sales Tax Payable', Account::TYPE_LIABILITY],
        '3000' => ['Owner Equity', Account::TYPE_EQUITY],
        '4000' => ['Sales Revenue', Account::TYPE_REVENUE],
        '4100' => ['Sales Returns', Account::TYPE_REVENUE],
        '5000' => ['Cost of Goods Sold', Account::TYPE_EXPENSE],
        '6000' => ['Operating Expenses', Account::TYPE_EXPENSE],
        '6100' => ['Discounts Given', Account::TYPE_EXPENSE],
    ];

    /**
     * Look up an account by code, auto-create from defaults if missing.
     */
    public function account(string $code, ?string $tenantId = null): ?Account
    {
        $tenantId = $tenantId ?? currentTenantId();
        $existing = Account::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->first();
        if ($existing) {
            return $existing;
        }

        if (!isset(self::DEFAULT_COA[$code])) {
            return null;
        }

        [$name, $type] = self::DEFAULT_COA[$code];

        return Account::create([
            'tenant_id' => $tenantId,
            'code' => $code,
            'name' => $name,
            'type' => $type,
            'is_active' => true,
        ]);
    }

    public function seedDefaultCoa(?string $tenantId = null): void
    {
        $tenantId = $tenantId ?? currentTenantId();
        foreach (self::DEFAULT_COA as $code => [$name, $type]) {
            $this->account($code, $tenantId);
        }
    }

    /**
     * Build a balanced JE for a sale: Dr Cash/AR, Dr Discount (if any), Cr Sales, Cr Sales Tax.
     */
    public function postSale(Sale $sale, bool $cashSale = true): ?JournalEntry
    {
        $tenantId = $sale->tenant_id ?? currentTenantId();
        $this->seedDefaultCoa($tenantId);

        $grandTotal = (float) ($sale->grand_total ?? 0);
        $tax = (float) ($sale->tax_amount ?? 0);
        $discount = (float) ($sale->discount ?? 0);
        $netRevenue = round($grandTotal - $tax, 2);

        if ($grandTotal <= 0) {
            return null;
        }

        $lines = [];
        $drAccount = $cashSale ? '1000' : '1100';
        $lines[] = ['code' => $drAccount, 'debit' => $grandTotal, 'credit' => 0, 'memo' => 'Cash/AR for sale'];
        if ($discount > 0) {
            $lines[] = ['code' => '6100', 'debit' => $discount, 'credit' => 0, 'memo' => 'Discount given'];
            $netRevenue = round($netRevenue + $discount, 2);
        }
        $lines[] = ['code' => '4000', 'debit' => 0, 'credit' => $netRevenue, 'memo' => 'Sales revenue'];
        if ($tax > 0) {
            $lines[] = ['code' => '2100', 'debit' => 0, 'credit' => $tax, 'memo' => 'Sales tax payable'];
        }

        return $this->writeEntry([
            'tenant_id' => $tenantId,
            'reference' => $sale->reference_code ?? ('SALE-' . $sale->id),
            'entry_date' => $sale->date ?? now()->toDateString(),
            'description' => 'Auto-journal for sale #' . $sale->id,
            'source_type' => 'sale',
            'source_id' => $sale->id,
        ], $lines);
    }

    /**
     * Build a balanced JE for a purchase: Dr Inventory + Dr Tax, Cr Cash/AP.
     */
    public function postPurchase(Purchase $purchase, bool $cashPurchase = true): ?JournalEntry
    {
        $tenantId = $purchase->tenant_id ?? currentTenantId();
        $this->seedDefaultCoa($tenantId);

        $grandTotal = (float) ($purchase->grand_total ?? 0);
        $tax = (float) ($purchase->tax_amount ?? 0);
        if ($grandTotal <= 0) {
            return null;
        }

        $netInventory = round($grandTotal - $tax, 2);
        $crAccount = $cashPurchase ? '1000' : '2000';

        $lines = [
            ['code' => '1200', 'debit' => $netInventory, 'credit' => 0, 'memo' => 'Inventory bought'],
        ];
        if ($tax > 0) {
            $lines[] = ['code' => '2100', 'debit' => $tax, 'credit' => 0, 'memo' => 'Recoverable / input tax'];
        }
        $lines[] = ['code' => $crAccount, 'debit' => 0, 'credit' => $grandTotal, 'memo' => 'Cash/AP for purchase'];

        return $this->writeEntry([
            'tenant_id' => $tenantId,
            'reference' => $purchase->reference_code ?? ('PUR-' . $purchase->id),
            'entry_date' => $purchase->date ?? now()->toDateString(),
            'description' => 'Auto-journal for purchase #' . $purchase->id,
            'source_type' => 'purchase',
            'source_id' => $purchase->id,
        ], $lines);
    }

    /**
     * Build a balanced JE for an expense: Dr Operating Expense, Cr Cash.
     */
    public function postExpense(Expense $expense): ?JournalEntry
    {
        $tenantId = $expense->tenant_id ?? currentTenantId();
        $this->seedDefaultCoa($tenantId);

        $amount = (float) ($expense->amount ?? 0);
        if ($amount <= 0) {
            return null;
        }

        $lines = [
            ['code' => '6000', 'debit' => $amount, 'credit' => 0, 'memo' => $expense->details ?? 'Operating expense'],
            ['code' => '1000', 'debit' => 0, 'credit' => $amount, 'memo' => 'Cash paid for expense'],
        ];

        return $this->writeEntry([
            'tenant_id' => $tenantId,
            'reference' => $expense->reference_code ?? ('EXP-' . $expense->id),
            'entry_date' => $expense->date ?? now()->toDateString(),
            'description' => 'Auto-journal for expense #' . $expense->id,
            'source_type' => 'expense',
            'source_id' => $expense->id,
        ], $lines);
    }

    /**
     * @param array $header
     * @param array $lines  Each: ['code' => '1000', 'debit' => float, 'credit' => float, 'memo' => ?string]
     */
    public function writeEntry(array $header, array $lines): ?JournalEntry
    {
        $totalDebit = collect($lines)->sum(fn($l) => (float) ($l['debit'] ?? 0));
        $totalCredit = collect($lines)->sum(fn($l) => (float) ($l['credit'] ?? 0));
        if (round($totalDebit, 2) !== round($totalCredit, 2) || $totalDebit <= 0) {
            return null;
        }

        return DB::transaction(function () use ($header, $lines) {
            // Idempotency: if a posted/draft entry for this source already exists, do nothing.
            if (!empty($header['source_type']) && !empty($header['source_id'])) {
                $exists = JournalEntry::query()
                    ->where('source_type', $header['source_type'])
                    ->where('source_id', $header['source_id'])
                    ->where('status', '!=', JournalEntry::STATUS_VOID)
                    ->first();
                if ($exists) {
                    return $exists;
                }
            }

            $entry = JournalEntry::create([
                'tenant_id' => $header['tenant_id'] ?? null,
                'reference' => $header['reference'] ?? null,
                'entry_date' => $header['entry_date'],
                'description' => $header['description'] ?? null,
                'source_type' => $header['source_type'] ?? 'manual',
                'source_id' => $header['source_id'] ?? null,
                'status' => JournalEntry::STATUS_POSTED,
                'created_by' => Auth::id(),
            ]);

            foreach ($lines as $line) {
                $account = $this->account($line['code']);
                if (!$account) {
                    throw new \RuntimeException("Account code {$line['code']} not configured in chart of accounts.");
                }
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $account->id,
                    'debit' => (float) ($line['debit'] ?? 0),
                    'credit' => (float) ($line['credit'] ?? 0),
                    'memo' => $line['memo'] ?? null,
                ]);
            }

            return $entry->refresh();
        });
    }
}
