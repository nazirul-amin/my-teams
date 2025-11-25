<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            // password omitted; will be generated server-side
            'role' => ['required', 'string', Rule::in(['super-admin', 'admin', 'manager', 'user'])],
            // optional: assign companies on create
            'company_ids' => ['array'],
            'company_ids.*' => ['string'],

            // optional profile photo on create
            'photo_file' => ['nullable', 'file', 'image', 'max:5120'],
        ];
    }
}
