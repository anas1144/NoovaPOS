<?php

namespace App\Http\Requests;

class CreatePlatformTenantRequest extends RegisterTenantRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            // One or more plans the tenant subscribes to (one per shop type).
            // Each provisions a store. plan_id (single) kept for compatibility.
            'plan_ids'   => 'nullable|array',
            'plan_ids.*' => 'integer|exists:plans,id',
            'plan_id'    => 'nullable|integer|exists:plans,id',
            'shop_type'  => 'nullable|string|max:60',
        ]);
    }
}
