<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFbrProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'store_id' => 'sometimes|nullable|exists:stores,id',
            'business_name' => 'sometimes|required|string|max:255',
            'ntn' => 'sometimes|required|string|max:32',
            'strn' => 'nullable|string|max:32',
            'province' => 'nullable|string|max:100',
            'business_activity' => 'nullable|string|max:255',
            'pos_id' => 'nullable|string|max:120',
            'sandbox_token' => 'nullable|string',
            'production_token' => 'nullable|string',
            'mode' => 'sometimes|required|in:sandbox,production',
            'enabled' => 'nullable|boolean',
        ];
    }
}

