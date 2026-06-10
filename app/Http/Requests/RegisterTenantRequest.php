<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalise fields coming from the React frontend.
     *
     * The React RegisterTenant form sends owner_first_name, owner_last_name,
     * owner_email, owner_phone, owner_password so that the field names are
     * distinct from any admin fields. We remap them here before validation.
     *
     * Also auto-fills confirm_password when not sent separately.
     */
    protected function prepareForValidation(): void
    {
        $map = [
            'owner_first_name' => 'first_name',
            'owner_last_name'  => 'last_name',
            'owner_email'      => 'email',
            'owner_phone'      => 'phone',
            'owner_password'   => 'password',
        ];

        $merge = [];
        foreach ($map as $from => $to) {
            if ($this->has($from) && ! $this->has($to)) {
                $merge[$to] = $this->input($from);
            }
        }

        // Auto-fill confirm_password if the frontend did not send it
        if (! $this->has('confirm_password')) {
            $merge['confirm_password'] = $merge['password'] ?? $this->input('password');
        }

        $this->merge($merge);
    }

    public function rules(): array
    {
        return [
            'business_name'    => 'required|string|max:255',
            'subdomain'        => 'required_without:domain|string|max:63|regex:/^[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?$/',
            'domain'           => 'required_without:subdomain|string|max:255',
            'first_name'       => 'required|string|max:255',
            'last_name'        => 'nullable|string|max:255',
            'email'            => 'required|email|unique:users,email',
            'phone'            => 'nullable|string|max:30',
            'password'         => 'required|min:6',
            'confirm_password' => 'required|same:password',
            'country'          => 'nullable|string|max:5',
        ];
    }
}
