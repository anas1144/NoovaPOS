<?php

namespace App\Models;

use App\Models\Contracts\JsonResourceful;
use App\Traits\HasJsonResourcefulData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;

class Store extends BaseModel implements JsonResourceful
{
    use HasFactory, HasJsonResourcefulData;

    const JSON_API_TYPE = 'stores';

    protected $table = 'stores';

    protected $fillable = [
        'name',
        'shop_type',
        'tenant_id',
        'status',
        'is_default',
    ];

    public static function rules(): array
    {
        return [
            'name' => 'required',
            'domain' => 'nullable|string|max:255|unique:domains,domain',
            'subdomain' => 'nullable|string|max:63',
        ];
    }

    public function prepareLinks(): array
    {
        return [
            'self' => route('stores.show', $this->id),
        ];
    }

    public function prepareAttributes(): array
    {
        return [
            'name' => $this->name,
            'shop_type' => $this->shop_type ?: 'retail',
            'tenant_id' => $this->tenant_id,
            'status' => (int)$this->status,
            'is_default' => (bool)$this->is_default,
            'users' => UserStore::where('store_id', $this->id)->count(),
            // Active = the store the user has selected. Fall back to the default
            // store when the user hasn't picked one yet.
            'active' => Auth::user()->active_store_id
                ? (int) Auth::user()->active_store_id === (int) $this->id
                : (bool) $this->is_default,
        ];
    }
}
