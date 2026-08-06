<?php

namespace App\Http\Requests;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;

class AssignCompanyUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        $company = $this->route('company');

        return $company instanceof Company
            && ($this->user()?->can('assignUsers', $company) ?? false);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'user_ids' => ['sometimes', 'array'],
            'user_ids.*' => ['distinct', 'exists:users,id'],
        ];
    }

    /**
     * @return list<string>
     */
    public function userIds(): array
    {
        return array_values($this->validated('user_ids', []));
    }
}
