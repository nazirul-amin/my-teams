<?php

namespace App\Http\Controllers;

use App\Enums\RolesEnum;
use App\Http\Requests\ContactCardAdminRequest;
use App\Models\ContactCard;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class ContactCardAdminController extends BaseController
{
    public function store(User $user, ContactCardAdminRequest $request): RedirectResponse
    {
        try {
            $auth = request()->user();

            // Fetch existing card if any (we'll update associations if exists)
            $existing = $user->contactCard;

            // Use validated inputs
            $validated = $request->validated();
            $companyId = $validated['company_id'];
            // Authorization:
            // - super-admin: all
            // - admin: users they created
            // - manager: users who share BOTH a company and a team with them
            $allowed = false;
            if ($auth->hasRole(RolesEnum::SUPERADMIN->value)) {
                $allowed = true;
            } elseif ($auth->hasRole(RolesEnum::ADMIN->value)) {
                $createdByAdmin = ($user->created_by === $auth->getKey());
                $sharesCompany = $user->companies()->whereIn('companies.id', $auth->companies()->pluck('companies.id'))->exists();
                $allowed = $createdByAdmin && $sharesCompany;
            } elseif ($auth->hasRole(RolesEnum::MANAGER->value)) {
                $sharesCompany = $user->companies()->whereIn('companies.id', $auth->companies()->pluck('companies.id'))->exists();
                if ($sharesCompany) {
                    $allowed = $user->teams()->whereIn('teams.id', $auth->teams()->pluck('teams.id'))->exists();
                }
            }
            abort_unless($allowed, 403);

            // Validate the user belongs to the selected company
            $belongs = $user->companies()->where('companies.id', $companyId)->exists();
            abort_unless($belongs, 422, 'Selected company is not assigned to this user');

            $teamId = $validated['team_id'];
            $validTeam = Team::query()
                ->whereKey($teamId)
                ->where('company_id', $companyId)
                ->whereHas('users', fn ($q) => $q->where('users.id', $user->getKey()))
                ->exists();
            abort_unless($validTeam, 422, 'Selected team is invalid for this user/company');

            // Build values for update/create
            $values = [
                'company_id' => $companyId,
                'team_id' => $teamId,
            ];

            // If creating new, also set slug and is_dark_mode
            if (! $existing) {
                // Create unique slug based on name
                $base = Str::slug($user->name);
                $slug = $base ?: Str::lower(Str::random(8));
                $i = 1;
                while (ContactCard::where('slug', $slug)->exists()) {
                    $slug = $base.'-'.$i++;
                    if ($i > 10) { // fallback
                        $slug = $base.'-'.Str::lower(Str::random(5));
                        break;
                    }
                }
                $values['slug'] = $slug;
                $values['is_dark_mode'] = false;
            }

            ContactCard::updateOrCreate(
                ['user_id' => $user->getKey()],
                $values,
            );

            return back()->with('success', $existing ? 'Contact card updated' : 'Contact card generated');
        } catch (\Throwable $th) {
            $this->logException($th, 'generate contact card for user', [
                'target_user_id' => $user->getKey(),
            ]);

            return back()->with('error', 'Failed to generate contact card');
        }
    }
}
