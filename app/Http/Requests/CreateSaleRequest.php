<?php

namespace App\Http\Requests;

use App\Models\Sale;
use App\Models\Warehouse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * Class CreateSaleRequest
 */
class CreateSaleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return Sale::$rules;
    }

    /**
     * Configure the validator instance.
     */
    protected function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->has('warehouse_id')) {
                $warehouse = Warehouse::where('id', $this->warehouse_id)
                    ->where('tenant_id', Auth::user()->tenant_id)
                    ->first();

                if (!$warehouse) {
                    $validator->errors()->add('warehouse_id', 'Please select a valid warehouse or update form settings.');
                }
            }
        });
    }
}
