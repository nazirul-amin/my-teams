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
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('teams', 'slug')->ignore($team?->getKey()),
            ],
            'photo' => ['nullable', 'string', 'max:2048'],
            'cover_photo' => ['nullable', 'string', 'max:2048'],
            'user_ids' => ['sometimes', 'array'],
            'user_ids.*' => ['distinct', 'exists:users,id'],
        ];
    }
}
