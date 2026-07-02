<?php

namespace App\Repositories\Criteria;

use App\Sorts\UserCustomSort;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\Sorts\Sort;

class JSONApiSortingCriteria implements CriteriaInterface
{
    /**
     * @var Request
     */
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Apply criteria in query repository.
     *
     * @return mixed
     */
    public function apply($model, RepositoryInterface $repository)
    {
        // Spatie QueryBuilder v6 types for() as Builder|Relation|string, so a
        // bare Model instance would be coerced to its JSON string. Normalize to
        // a query builder first.
        $subject = $model instanceof Model ? $model->newQuery() : $model;

        $sortingExists = $this->request->get('sort');
        if (empty($sortingExists) && isset($subject->getModel()->defaultSort)) {
            $this->request->query->set('sort', $subject->getModel()->defaultSort);
        }

        $searchableFields = $repository->getFieldsSearchable();

        //        $customSort = AllowedSort::custom('custom-sort', new UserCustomSort())->defaultDirection('desc');

        return QueryBuilder::for($subject)
            ->allowedSorts($searchableFields)
            ->getEloquentBuilder();
    }
}
