<?php

namespace App\Repositories;

use App\Models\Shop;

class ShopRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'name',
        'code',
        'status',
        'store_id',
    ];

    protected $allowedFields = [
        'tenant_id',
        'store_id',
        'name',
        'code',
        'status',
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return Shop::class;
    }
}
