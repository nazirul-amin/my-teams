<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Models\User $target */
        $target = $this->route('user');

        return $this->user()?->can('update', $target) ?? false;
    }

    public function rules(): array
    {
        /** @var \App\Models\User $target */
        $target = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($target?->getKey(), 'id')],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['sometimes', 'string', Rule::in(['super-admin', 'admin', 'manager', 'user'])],
            'company_ids' => ['array'],
            'company_ids.*' => ['string'],

            // Profile (nested) fields
            'profile' => ['sometimes', 'array'],
            'profile.bio' => ['nullable', 'string', 'max:255'],
            'profile.position' => ['nullable', 'string', 'max:255'],
            'profile.phone' => ['required', 'string', 'max:50'],
            'profile.website' => ['nullable', 'string', 'max:255'],
            'profile.linkedin' => ['nullable', 'string', 'max:255'],
            'profile.twitter' => ['nullable', 'string', 'max:255'],
            'profile.facebook' => ['nullable', 'string', 'max:255'],
            'profile.instagram' => ['nullable', 'string', 'max:255'],
            'profile.photo' => ['nullable', 'string', 'max:255'],
            'profile.cover_photo' => ['nullable', 'string', 'max:255'],

            // Image upload fields
            'photo_file' => ['nullable', 'file', 'image', 'max:5120'],
            'cover_photo_file' => ['nullable', 'file', 'image', 'max:5120'],
            'photo_removed' => ['sometimes', 'boolean'],
            'cover_photo_removed' => ['sometimes', 'boolean'],
        ];
    }
}
