<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContactCardGenerateRequest extends FormRequest
{
    public function rules(): array
    {
        $existingId = optional($this->user()->contactCard)->getKey();

        return [
            'company_id' => ['required', 'exists:companies,id'],
            'team_id' => ['required', 'exists:teams,id'],
            'slug' => [
                'required',
                'string',
                'max:60',
                'alpha_dash',
                Rule::unique('contact_cards', 'slug')->ignore($existingId, 'id'),
            ],
        ];
    }
}
