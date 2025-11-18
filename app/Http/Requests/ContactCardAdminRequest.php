<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactCardAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is handled in ContactCardAdminController@store
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'exists:companies,id'],
            'team_id' => ['required', 'exists:teams,id'],
        ];
    }
}
