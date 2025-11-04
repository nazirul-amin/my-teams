<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateTeamRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $team = $this->route('team');

        return $team ? Gate::allows('update', $team) : false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $team = $this->route('team');

        return [
            'company_id' => ['sometimes', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('teams', 'slug')->ignore($team?->getKey()),
            ],
            'website' => ['sometimes', 'nullable', 'string', 'max:255', 'url'],
            'logo_light' => ['nullable', 'image', 'max:8192'],
            'logo_dark' => ['nullable', 'image', 'max:8192'],
            'remove_logo_light' => ['sometimes', 'boolean'],
            'remove_logo_dark' => ['sometimes', 'boolean'],
            'user_ids' => ['sometimes', 'array'],
            'user_ids.*' => ['distinct', 'exists:users,id'],
        ];
    }
}
