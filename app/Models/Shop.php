<?php

namespace App\Models;

use App\Models\Contracts\JsonResourceful;
use App\Traits\HasJsonResourcefulData;
use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Shop extends BaseModel implements JsonResourceful
{
    use HasFactory, HasJsonResourcefulData, BelongsToTenant, Multitenantable;

    protected $table = 'shops';

    public const JSON_API_TYPE = 'shops';

    protected $fillable = [
        'tenant_id',
        'store_id',
        'name',
        'code',
        'status',
    ];

    public static function rules(): array
    {
        return [
            'store_id' => 'required|exists:stores,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255',
            'status' => 'nullable|boolean',
        ];
    }

    public function prepareLinks(): array
    {
        return [
            'self' => route('shops.show', $this->id),
        ];
    }

    public function prepareAttributes(): array
    {
        return [
            'store_id' => $this->store_id,
            'store_name' => $this->store?->name,
            'name' => $this->name,
            'code' => $this->code,
            'status' => (bool) $this->status,
            'users' => $this->users()->count(),
            'created_at' => $this->created_at,
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(UserShop::class, 'shop_id');
    }
}
