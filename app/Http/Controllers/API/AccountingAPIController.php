<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\Account;
use App\Models\Expense;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Purchase;
use App\Models\Sale;
use App\Services\AutoJournalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class AccountingAPIController extends AppBaseController
{
    public function __construct(private readonly AutoJournalService $autoJournal)
    {
    }

    public function seedChartOfAccounts(): JsonResponse
    {
        $this->autoJournal->seedDefaultCoa();
        return $this->sendSuccess('Default chart of accounts seeded.');
    }

    public function autoPostSale(Sale $sale): JsonResponse
    {
        $entry = $this->autoJournal->postSale($sale);
        return $entry
            ? $this->sendResponse($entry->load('lines'), 'Journal entry created for sale.')
            : $this->sendError('Could not create journal entry (zero total or duplicate).');
    }

    public function autoPostPurchase(Purchase $purchase): JsonResponse
    {
        $entry = $this->autoJournal->postPurchase($purchase);
        return $entry
            ? $this->sendResponse($entry->load('lines'), 'Journal entry created for purchase.')
            : $this->sendError('Could not create journal entry (zero total or duplicate).');
    }

    public function autoPostExpense(Expense $expense): JsonResponse
    {
        $entry = $this->autoJournal->postExpense($expense);
        return $entry
            ? $this->sendResponse($entry->load('lines'), 'Journal entry created for expense.')
            : $this->sendError('Could not create journal entry (zero total or duplicate).');
    }

    // ---- Accounts (Chart of Accounts) ----
    public function accounts(Request $request): JsonResponse
    {
        $query = Account::query()->orderBy('code');
        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        return $this->sendResponse(
            $query->get(),
            'Accounts retrieved successfully.'
        );
    }

    public function storeAccount(Request $request): JsonResponse
    {
        $input = $this->validateAccount($request);
        return $this->sendResponse(Account::create($input), 'Account created successfully.');
    }

    public function updateAccount(Request $request, Account $account): JsonResponse
    {
        $input = $this->validateAccount($request, $account->id);
        $account->update($input);
        return $this->sendResponse($account->refresh(), 'Account updated successfully.');
    }

    public function destroyAccount(Account $account): JsonResponse
    {
        if ($account->lines()->exists()) {
            throw new UnprocessableEntityHttpException('Account has journal entries and cannot be deleted.');
        }
        $account->delete();
        return $this->sendSuccess('Account deleted successfully.');
    }

    // ---- Journal Entries ----
    public function journalEntries(Request $request): JsonResponse
    {
        $query = JournalEntry::query()->with('lines.account:id,code,name,type');
        foreach (['status', 'source_type'] as $f) {
            if ($request->filled($f)) {
                $query->where($f, $request->get($f));
            }
        }
        if ($request->filled('start_date')) {
            $query->whereDate('entry_date', '>=', $request->get('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('entry_date', '<=', $request->get('end_date'));
        }
        return $this->sendResponse(
            $query->orderByDesc('entry_date')->orderByDesc('id')->paginate(getPageSize($request)),
            'Journal entries retrieved successfully.'
        );
    }

    public function storeJournalEntry(Request $request): JsonResponse
    {
        $input = $request->validate([
            'reference' => 'nullable|string|max:255',
            'entry_date' => 'required|date',
            'description' => 'nullable|string|max:500',
            'source_type' => 'nullable|string|max:50',
            'source_id' => 'nullable|integer',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
            'lines.*.memo' => 'nullable|string|max:255',
        ]);

        $totalDebit = collect($input['lines'])->sum(fn($l) => (float) ($l['debit'] ?? 0));
        $totalCredit = collect($input['lines'])->sum(fn($l) => (float) ($l['credit'] ?? 0));
        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            throw new UnprocessableEntityHttpException('Journal entry is not balanced (debit ≠ credit).');
        }
        if ($totalDebit <= 0) {
            throw new UnprocessableEntityHttpException('Journal entry must have non-zero amounts.');
        }

        return DB::transaction(function () use ($input) {
            $entry = JournalEntry::create([
                'reference' => $input['reference'] ?? null,
                'entry_date' => $input['entry_date'],
                'description' => $input['description'] ?? null,
                'source_type' => $input['source_type'] ?? 'manual',
                'source_id' => $input['source_id'] ?? null,
                'status' => JournalEntry::STATUS_DRAFT,
                'created_by' => Auth::id(),
            ]);

            foreach ($input['lines'] as $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'memo' => $line['memo'] ?? null,
                ]);
            }

            return $this->sendResponse(
                $entry->load('lines.account:id,code,name,type'),
                'Journal entry created successfully.'
            );
        });
    }

    public function postJournalEntry(JournalEntry $journal): JsonResponse
    {
        if ($journal->status === JournalEntry::STATUS_POSTED) {
            return $this->sendError('Journal entry is already posted.');
        }
        $journal->update(['status' => JournalEntry::STATUS_POSTED]);
        return $this->sendResponse($journal, 'Journal entry posted.');
    }

    // ---- Trial Balance / Ledger ----
    public function trialBalance(Request $request): JsonResponse
    {
        $tenantId = currentTenantId();
        $endDate = $request->get('end_date');

        $rows = DB::table('accounts')
            ->leftJoin('journal_entry_lines', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->leftJoin('journal_entries', function ($join) use ($endDate) {
                $join->on('journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
                    ->where('journal_entries.status', JournalEntry::STATUS_POSTED);
                if ($endDate) {
                    $join->whereDate('journal_entries.entry_date', '<=', $endDate);
                }
            })
            ->where(function ($q) use ($tenantId) {
                $q->where('accounts.tenant_id', $tenantId)
                    ->orWhereNull('accounts.tenant_id');
            })
            ->select(
                'accounts.id',
                'accounts.code',
                'accounts.name',
                'accounts.type',
                DB::raw('COALESCE(SUM(journal_entry_lines.debit), 0) as total_debit'),
                DB::raw('COALESCE(SUM(journal_entry_lines.credit), 0) as total_credit')
            )
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.type')
            ->orderBy('accounts.code')
            ->get()
            ->map(function ($r) {
                $debit = (float) $r->total_debit;
                $credit = (float) $r->total_credit;
                $isDebitNormal = in_array($r->type, ['asset', 'expense'], true);
                $balance = $isDebitNormal ? $debit - $credit : $credit - $debit;
                return [
                    'id' => $r->id,
                    'code' => $r->code,
                    'name' => $r->name,
                    'type' => $r->type,
                    'total_debit' => $debit,
                    'total_credit' => $credit,
                    'balance' => round($balance, 2),
                ];
            });

        $totals = [
            'debit' => round($rows->sum('total_debit'), 2),
            'credit' => round($rows->sum('total_credit'), 2),
        ];

        return $this->sendResponse([
            'rows' => $rows,
            'totals' => $totals,
        ], 'Trial balance retrieved successfully.');
    }

    public function ledger(Request $request, Account $account): JsonResponse
    {
        $query = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entry_lines.account_id', $account->id)
            ->where('journal_entries.status', JournalEntry::STATUS_POSTED);

        if ($request->filled('start_date')) {
            $query->whereDate('journal_entries.entry_date', '>=', $request->get('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('journal_entries.entry_date', '<=', $request->get('end_date'));
        }

        $rows = $query->select(
            'journal_entry_lines.id',
            'journal_entries.entry_date',
            'journal_entries.reference',
            'journal_entries.description',
            'journal_entry_lines.debit',
            'journal_entry_lines.credit',
            'journal_entry_lines.memo'
        )
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entries.id')
            ->get();

        $running = 0.0;
        $isDebitNormal = in_array($account->type, ['asset', 'expense'], true);
        $data = $rows->map(function ($row) use (&$running, $isDebitNormal) {
            $delta = $isDebitNormal
                ? ((float) $row->debit - (float) $row->credit)
                : ((float) $row->credit - (float) $row->debit);
            $running = round($running + $delta, 2);
            return [
                'id' => $row->id,
                'entry_date' => $row->entry_date,
                'reference' => $row->reference,
                'description' => $row->description,
                'debit' => (float) $row->debit,
                'credit' => (float) $row->credit,
                'memo' => $row->memo,
                'balance' => $running,
            ];
        });

        return $this->sendResponse([
            'account' => $account,
            'rows' => $data,
            'closing_balance' => $running,
        ], 'Ledger retrieved successfully.');
    }

    // ---- Profit & Loss ----
    public function profitAndLoss(Request $request): JsonResponse
    {
        $tenantId = currentTenantId();
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $rows = DB::table('accounts')
            ->leftJoin('journal_entry_lines', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->leftJoin('journal_entries', function ($join) use ($startDate, $endDate) {
                $join->on('journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
                    ->where('journal_entries.status', JournalEntry::STATUS_POSTED);
                if ($startDate) {
                    $join->whereDate('journal_entries.entry_date', '>=', $startDate);
                }
                if ($endDate) {
                    $join->whereDate('journal_entries.entry_date', '<=', $endDate);
                }
            })
            ->where(function ($q) use ($tenantId) {
                $q->where('accounts.tenant_id', $tenantId)
                    ->orWhereNull('accounts.tenant_id');
            })
            ->whereIn('accounts.type', [Account::TYPE_REVENUE, Account::TYPE_EXPENSE])
            ->select(
                'accounts.id',
                'accounts.code',
                'accounts.name',
                'accounts.type',
                DB::raw('COALESCE(SUM(journal_entry_lines.debit), 0) as total_debit'),
                DB::raw('COALESCE(SUM(journal_entry_lines.credit), 0) as total_credit')
            )
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.type')
            ->orderBy('accounts.code')
            ->get();

        $revenue = $rows->where('type', Account::TYPE_REVENUE)->map(function ($r) {
            return [
                'id' => $r->id,
                'code' => $r->code,
                'name' => $r->name,
                'amount' => round((float) $r->total_credit - (float) $r->total_debit, 2),
            ];
        })->values();

        $expense = $rows->where('type', Account::TYPE_EXPENSE)->map(function ($r) {
            return [
                'id' => $r->id,
                'code' => $r->code,
                'name' => $r->name,
                'amount' => round((float) $r->total_debit - (float) $r->total_credit, 2),
            ];
        })->values();

        $totalRevenue = round($revenue->sum('amount'), 2);
        $totalExpense = round($expense->sum('amount'), 2);
        $netProfit = round($totalRevenue - $totalExpense, 2);

        return $this->sendResponse([
            'period' => ['start_date' => $startDate, 'end_date' => $endDate],
            'revenue' => $revenue,
            'expense' => $expense,
            'totals' => [
                'revenue' => $totalRevenue,
                'expense' => $totalExpense,
                'net_profit' => $netProfit,
            ],
        ], 'Profit & loss retrieved successfully.');
    }

    // ---- Balance Sheet ----
    public function balanceSheet(Request $request): JsonResponse
    {
        $tenantId = currentTenantId();
        $endDate = $request->get('end_date');

        $rows = DB::table('accounts')
            ->leftJoin('journal_entry_lines', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->leftJoin('journal_entries', function ($join) use ($endDate) {
                $join->on('journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
                    ->where('journal_entries.status', JournalEntry::STATUS_POSTED);
                if ($endDate) {
                    $join->whereDate('journal_entries.entry_date', '<=', $endDate);
                }
            })
            ->where(function ($q) use ($tenantId) {
                $q->where('accounts.tenant_id', $tenantId)
                    ->orWhereNull('accounts.tenant_id');
            })
            ->whereIn('accounts.type', [
                Account::TYPE_ASSET,
                Account::TYPE_LIABILITY,
                Account::TYPE_EQUITY,
                Account::TYPE_REVENUE,
                Account::TYPE_EXPENSE,
            ])
            ->select(
                'accounts.id',
                'accounts.code',
                'accounts.name',
                'accounts.type',
                DB::raw('COALESCE(SUM(journal_entry_lines.debit), 0) as total_debit'),
                DB::raw('COALESCE(SUM(journal_entry_lines.credit), 0) as total_credit')
            )
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.type')
            ->orderBy('accounts.code')
            ->get();

        $assets = $rows->where('type', Account::TYPE_ASSET)->map(fn($r) => [
            'id' => $r->id,
            'code' => $r->code,
            'name' => $r->name,
            'amount' => round((float) $r->total_debit - (float) $r->total_credit, 2),
        ])->values();

        $liabilities = $rows->where('type', Account::TYPE_LIABILITY)->map(fn($r) => [
            'id' => $r->id,
            'code' => $r->code,
            'name' => $r->name,
            'amount' => round((float) $r->total_credit - (float) $r->total_debit, 2),
        ])->values();

        $equity = $rows->where('type', Account::TYPE_EQUITY)->map(fn($r) => [
            'id' => $r->id,
            'code' => $r->code,
            'name' => $r->name,
            'amount' => round((float) $r->total_credit - (float) $r->total_debit, 2),
        ])->values();

        $totalRevenue = round($rows->where('type', Account::TYPE_REVENUE)
            ->sum(fn($r) => (float) $r->total_credit - (float) $r->total_debit), 2);
        $totalExpense = round($rows->where('type', Account::TYPE_EXPENSE)
            ->sum(fn($r) => (float) $r->total_debit - (float) $r->total_credit), 2);
        $retainedEarnings = round($totalRevenue - $totalExpense, 2);

        $totalAssets = round($assets->sum('amount'), 2);
        $totalLiabilities = round($liabilities->sum('amount'), 2);
        $totalEquity = round($equity->sum('amount') + $retainedEarnings, 2);

        return $this->sendResponse([
            'as_of' => $endDate,
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'retained_earnings' => $retainedEarnings,
            'totals' => [
                'assets' => $totalAssets,
                'liabilities' => $totalLiabilities,
                'equity' => $totalEquity,
                'liabilities_plus_equity' => round($totalLiabilities + $totalEquity, 2),
            ],
        ], 'Balance sheet retrieved successfully.');
    }

    private function validateAccount(Request $request, ?int $accountId = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:30',
                Rule::unique('accounts', 'code')
                    ->where(fn($q) => $q->where('tenant_id', currentTenantId()))
                    ->ignore($accountId)],
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in([
                Account::TYPE_ASSET,
                Account::TYPE_LIABILITY,
                Account::TYPE_EQUITY,
                Account::TYPE_REVENUE,
                Account::TYPE_EXPENSE,
            ])],
            'parent_id' => 'nullable|exists:accounts,id',
            'is_active' => 'nullable|boolean',
        ]);
    }
}
