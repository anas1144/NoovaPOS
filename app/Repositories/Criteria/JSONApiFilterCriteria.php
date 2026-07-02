<?php

namespace App\Repositories\Criteria;

use App\Filters\CustomSearchFilter;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class JSONApiFilterCriteria implements CriteriaInterface
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

        $searchableFields = $repository->getFieldsSearchable();

        // Add custom search filter for search single string on multiple fields. ?filter[search]=Jhon
        $searchableFields[] = AllowedFilter::custom('search', new CustomSearchFilter($searchableFields));

        /** @var BaseRepository $repository */
        $idFields = $subject->getModel()->getIdFilterFields();
        if (request()->segment(2) == 'supplier-report' && isset(request()->filter['search'])) {
            $query = QueryBuilder::for($subject)
                ->whereIn('id', getSupplierGrandTotalFilterIds(request()->filter['search']))
                ->allowedFilters($searchableFields)
                ->getEloquentBuilder();
        } else {
            $query = QueryBuilder::for($subject)
                ->allowedFilters($searchableFields)
                ->getEloquentBuilder();
        }

        return $this->filterIdFields($query, $idFields);
    }

    /**
     * @return mixed
     */
    public function filterIdFields($query, $idFields)
    {
        foreach ($idFields as $idField => $modelClassName) {
            $value = $this->request->input('filter.'.$idField);

            if (! is_null($value)) {
                $modelClass = app($modelClassName);
                $ids = [];
                if (is_array($value)) {
                    foreach ($value as $id) {
                        $ids[] = $modelClass::whereId($id)->firstOrFail()->id;
                    }
                } else {
                    $value = explode(',', $value);
                    foreach ($value as $id) {
                        $ids[] = $modelClass::whereId($id)->firstOrFail()->id;
                    }
                }
                $query->whereIn($idField, $ids);
            }
        }

        return $query;
    }
}
