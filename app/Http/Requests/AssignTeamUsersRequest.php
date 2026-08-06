<?php

namespace App\Http\Requests;

use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;

class AssignTeamUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        $team = $this->route('team');

        return $team instanceof Team
            && ($this->user()?->can('assignUsers', $team) ?? false);
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
