<?php

namespace App\Repositories;

use App\Models\Expense;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Class ExpenseRepository
 */
class ExpenseRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'date',
        'amount',
        'details',
        'reference_code',
        'created_at',
        'title',
    ];

    public function getAvailableRelations(): array
    {
        return array_values(Expense::$availableRelations);
    }

    /**
     * Return searchable fields
     */
    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    /**
     * Configure the Model
     **/
    public function model()
    {
        return Expense::class;
    }

    /**
     * @return LengthAwarePaginator|Collection|mixed
     */
    public function storeExpense($input)
    {
        try {
            DB::beginTransaction();
            // Expenses default to Draft
            $input['posted_status'] = Expense::STATUS_DRAFT;
            $expense = $this->create($input);
            $input['reference_code'] = getSettingValue('expense_code').'_11'.$expense->id;
            $expense->update($input);
            DB::commit();

            return $expense;
        } catch (Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    /**
     * Post an expense - Mark as posted
     */
    public function postExpense($id): Expense
    {
        try {
            DB::beginTransaction();

            $expense = Expense::findOrFail($id);

            // Check if already posted
            if ($expense->posted_status == Expense::STATUS_POSTED) {
                throw new UnprocessableEntityHttpException('This expense has already been posted.');
            }

            // Mark as posted
            $expense->update(['posted_status' => Expense::STATUS_POSTED]);

            DB::commit();

            return $expense;
        } catch (Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }
}
