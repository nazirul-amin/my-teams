<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var \App\Models\Company $company */
        $company = $this->route('company');

        return $this->user()?->can('update', $company) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var \App\Models\Company $company */
        $company = $this->route('company');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('companies', 'slug')->ignore($company?->getKey(), 'id'),
            ],
            'website' => ['nullable', 'url'],
            'linkedin' => ['nullable', 'url'],
            'twitter' => ['nullable', 'url'],
            'facebook' => ['nullable', 'url'],
            'instagram' => ['nullable', 'url'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:8192'],
            'bg_light' => ['nullable', 'image', 'max:8192'],
            'bg_dark' => ['nullable', 'image', 'max:8192'],
            'user_ids' => ['array'],
            'user_ids.*' => ['string'],
        ];
    }
}
