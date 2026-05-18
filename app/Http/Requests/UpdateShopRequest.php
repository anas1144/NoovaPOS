<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'store_id' => 'sometimes|required|exists:stores,id',
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:255',
            'status' => 'nullable|boolean',
        ];
    }
}
