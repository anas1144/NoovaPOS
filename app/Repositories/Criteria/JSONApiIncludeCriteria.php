<?php

namespace App\Repositories\Criteria;

use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;
use Spatie\QueryBuilder\QueryBuilder;

class JSONApiIncludeCriteria implements CriteriaInterface
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
     * @return Builder|mixed
     */
    public function apply($model, RepositoryInterface $repository)
    {
        /** @var BaseRepository $baseRepository */
        $baseRepository = $repository;

        $availableRelations = $baseRepository->getAvailableRelations();

        // Spatie QueryBuilder v6 types for() as Builder|Relation|string, so a
        // bare Model instance would be coerced to its JSON string. Normalize to
        // a query builder first.
        $subject = $model instanceof Model ? $model->newQuery() : $model;

        return QueryBuilder::for($subject)
            ->allowedIncludes($availableRelations)
            ->getEloquentBuilder();
    }
}
