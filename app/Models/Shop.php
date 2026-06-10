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
        'country',
        'shop_type',
        'enabled_modules',
        'status',
        // FBR POS — Pakistan only (country = PK)
        'fbr_pos_id',
        'fbr_auth_token',
        'fbr_ntn',
        'fbr_strn',
        'fbr_req_type',   // 0=live-esp  1=live-gw  2=sandbox/disabled
    ];

    protected $casts = [
        'status'         => 'boolean',
        'enabled_modules'=> 'array',
        'fbr_req_type'   => 'integer',
    ];

    /** True when this shop has FBR POS enabled (Pakistan, live mode, token set) */
    public function isFbrEnabled(): bool
    {
        return $this->country === 'PK'
            && in_array($this->fbr_req_type, [0, 1], true)
            && !empty($this->fbr_auth_token)
            && !empty($this->fbr_pos_id);
    }

    public static function rules(): array
    {
        return [
            'store_id'      => 'required|exists:stores,id',
            'name'          => 'required|string|max:255',
            'code'          => 'nullable|string|max:255',
            'country'       => 'nullable|string|max:5',
            'shop_type'     => 'nullable|string|max:60',
            'enabled_modules' => 'nullable',
            'status'        => 'nullable|boolean',
            // FBR — required only when country=PK and fbr_req_type != 2
            'fbr_pos_id'    => 'nullable|string|max:120',
            'fbr_auth_token'=> 'nullable|string',
            'fbr_ntn'       => 'nullable|string|max:32',
            'fbr_strn'      => 'nullable|string|max:32',
            'fbr_req_type'  => 'nullable|integer|in:0,1,2',
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
            'store_id'        => $this->store_id,
            'store_name'      => $this->store?->name,
            'name'            => $this->name,
            'code'            => $this->code,
            'country'         => $this->country,
            'shop_type'       => $this->shop_type ?: 'retail',
            'enabled_modules' => $this->enabled_modules,
            'status'          => (bool) $this->status,
            'users'           => $this->users()->count(),
            'created_at'      => $this->created_at,
            // FBR — only exposed when country=PK; token hidden from response
            'fbr_enabled'     => $this->isFbrEnabled(),
            'fbr_pos_id'      => $this->fbr_pos_id,
            'fbr_ntn'         => $this->fbr_ntn,
            'fbr_strn'        => $this->fbr_strn,
            'fbr_req_type'    => $this->fbr_req_type ?? 2,
            // Never return the raw token to the frontend
            'fbr_token_set'   => !empty($this->fbr_auth_token),
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
