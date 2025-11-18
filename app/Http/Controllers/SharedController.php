<?php

namespace App\Http\Controllers;

use App\Enums\RolesEnum;
use App\Models\Company;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;

class SharedController extends Controller
{
    /**
     * Return assignable users for a target company/team based on role rules.
     * Accepts either company_id or team_id (or both). If only team_id is provided,
     * company_id is resolved from the team.
     */
    public function assignableUsers(Request $request)
    {
        $auth = $request->user();

        $companyId = (string) $request->query('company_id', '');
        $teamId = (string) $request->query('team_id', '');

        $team = null;
        if (! $companyId && $teamId) {
            $team = Team::query()->findOrFail($teamId);
            $companyId = (string) $team->company_id;
        }

        // Require at least company_id now
        abort_unless(! empty($companyId), 422, 'company_id is required');
        $company = Company::query()->findOrFail($companyId);

        // Super Admin
        if ($auth->hasRole(RolesEnum::SUPERADMIN->value)) {
            $query = User::query();
            // Only restrict to target company members when team_id is present
            if (! empty($teamId)) {
                $query->whereHas('companies', function ($q) use ($companyId) {
                    $q->where('companies.id', $companyId);
                });
            }

            return response()->json(
                $query->orderBy('name')->get(['id', 'name', 'email'])
            );
        }

        // Admin logic
        if ($auth->hasRole(RolesEnum::ADMIN->value)) {
            // Target checks
            $canTarget = false;
            if ($teamId) {
                $team = $team ?: Team::query()->findOrFail($teamId);
                $canTarget = ($team->created_by === $auth->getKey())
                    || $team->users()->where('users.id', $auth->getKey())->exists()
                    || ($company->created_by === $auth->getKey())
                    || $company->users()->where('users.id', $auth->getKey())->exists();
            } else {
                $canTarget = ($company->created_by === $auth->getKey())
                    || $company->users()->where('users.id', $auth->getKey())->exists();
            }
            abort_unless($canTarget, 403);

            // Users created by admin OR users in same companies as admin
            $adminCompanyIds = $auth->companies()->pluck('companies.id');
            $query = User::query()
                ->where(function ($q) use ($auth, $adminCompanyIds) {
                    $q->where('created_by', $auth->getKey())
                        ->orWhereHas('companies', function ($qc) use ($adminCompanyIds) {
                            $qc->whereIn('companies.id', $adminCompanyIds);
                        });
                });
            // Only restrict to target company members when team_id is present
            if (! empty($teamId)) {
                $query->whereHas('companies', function ($q) use ($companyId) {
                    $q->where('companies.id', $companyId);
                });
            }

            $users = $query->orderBy('name')->get(['id', 'name', 'email']);

            return response()->json($users);
        }

        // Manager logic
        if ($auth->hasRole(RolesEnum::MANAGER->value)) {
            // Manager cannot target company-level assignment (must specify a team)
            abort_unless($teamId, 403);
            $team = $team ?: Team::query()->findOrFail($teamId);

            // Can target only teams assigned to them
            $canTarget = $team->users()->where('users.id', $auth->getKey())->exists();
            abort_unless($canTarget, 403);

            // Users in the target company
            $users = User::query()
                ->whereHas('companies', function ($q) use ($companyId) {
                    $q->where('companies.id', $companyId);
                })
                ->orderBy('name')
                ->get(['id', 'name', 'email']);

            return response()->json($users);
        }

        // Users have no assignment privileges
        abort(403);
    }

    /**
     * Return currently assigned users for a target company/team.
     * Accepts company_id or team_id. If only team_id is provided, resolves company_id.
     */
    public function assignedUsers(Request $request)
    {
        $auth = $request->user();

        $companyId = (string) $request->query('company_id', '');
        $teamId = (string) $request->query('team_id', '');

        $team = null;
        if (! $companyId && $teamId) {
            $team = Team::query()->findOrFail($teamId);
            $companyId = (string) $team->company_id;
        }

        abort_unless(! empty($companyId) || ! empty($teamId), 422, 'company_id or team_id is required');
        $company = Company::query()->findOrFail($companyId);

        // Authorization similar to assignable checks
        if ($teamId) {
            $team = $team ?: Team::query()->findOrFail($teamId);
            if (! $auth->hasRole(RolesEnum::SUPERADMIN->value)) {
                $canTarget = ($team->created_by === $auth->getKey())
                    || $team->users()->where('users.id', $auth->getKey())->exists()
                    || ($company->created_by === $auth->getKey())
                    || $company->users()->where('users.id', $auth->getKey())->exists();
                abort_unless($canTarget, 403);
            }

            $users = $team->users()->orderBy('users.name')->get(['users.id', 'users.name', 'users.email']);
        } else {
            if (! $auth->hasRole(RolesEnum::SUPERADMIN->value)) {
                $canTarget = ($company->created_by === $auth->getKey())
                    || $company->users()->where('users.id', $auth->getKey())->exists();
                abort_unless($canTarget, 403);
            }
            $users = $company->users()->orderBy('users.name')->get(['users.id', 'users.name', 'users.email']);
        }

        return response()->json($users);
    }
}
