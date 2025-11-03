<?php

namespace App\Http\Controllers;

use App\Enums\RolesEnum;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Mail\TemporaryPasswordMail;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', User::class);

        $auth = request()->user();

        $query = User::query()->with('creator');

        if ($auth->hasRole(RolesEnum::SUPERADMIN->value)) {
            // no filter
        } elseif ($auth->hasRole(RolesEnum::ADMIN->value)) {
            // Admin: users they created
            $query->where('created_by', $auth->getKey());
        } else {
            // Manager/User: users who share BOTH company and team
            $userCompanyIds = $auth->companies()->pluck('companies.id');
            $userTeamIds = $auth->teams()->pluck('teams.id');
            $query->whereHas('companies', function ($q) use ($userCompanyIds) {
                $q->whereIn('companies.id', $userCompanyIds);
            })->whereHas('teams', function ($q) use ($userTeamIds) {
                $q->whereIn('teams.id', $userTeamIds);
            });
        }

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = (int) request('per_page', 10);
        $users = $query->orderBy('name')->paginate($perPage)->withQueryString();

        return Inertia::render('users/Index', [
            'users' => $users,
            'filters' => [
                'search' => (string) request('search', ''),
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create()
    {
        $this->authorize('create', User::class);

        $auth = request()->user();

        $companiesQuery = Company::query();
        if (! $auth->hasRole(RolesEnum::SUPERADMIN->value)) {
            // Admin can assign only companies they created
            $companiesQuery->where('created_by', $auth->getKey());
        }
        $companies = $companiesQuery->orderBy('name')->get(['id', 'name']);

        // Roles available to assign
        $allRoles = [
            RolesEnum::SUPERADMIN->value,
            RolesEnum::ADMIN->value,
            RolesEnum::MANAGER->value,
            RolesEnum::USER->value,
        ];
        $assignableRoles = $auth->hasRole(RolesEnum::SUPERADMIN->value)
            ? $allRoles
            : [RolesEnum::ADMIN->value, RolesEnum::MANAGER->value, RolesEnum::USER->value];
        $roles = collect($assignableRoles)->map(fn ($r) => [
            'value' => $r,
            'label' => match ($r) {
                RolesEnum::SUPERADMIN->value => 'Super Admin',
                RolesEnum::ADMIN->value => 'Admin',
                RolesEnum::MANAGER->value => 'Manager',
                default => 'User',
            },
        ]);

        return Inertia::render('users/Create', [
            'companies' => $companies,
            'roles' => $roles,
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $auth = $request->user();

        $data = $request->validated();
        $companyIds = collect($data['company_ids'] ?? [])->filter();

        $user = new User;
        $user->name = $data['name'];
        $user->email = $data['email'];
        // Generate temporary password
        $tempPassword = Str::random(16);
        $user->password = bcrypt($tempPassword);
        $user->created_by = $auth->getKey();
        $user->save();

        // Notify user via email with the temporary password
        Mail::to($user->email)->send(new TemporaryPasswordMail($user, $tempPassword));

        // Assign role (limit by assignable set)
        $requestedRole = $data['role'] ?? RolesEnum::USER->value;
        $assignableRoles = $auth->hasRole(RolesEnum::SUPERADMIN->value)
            ? [RolesEnum::SUPERADMIN->value, RolesEnum::ADMIN->value, RolesEnum::MANAGER->value, RolesEnum::USER->value]
            : [RolesEnum::ADMIN->value, RolesEnum::MANAGER->value, RolesEnum::USER->value];
        if (! in_array($requestedRole, $assignableRoles, true)) {
            $requestedRole = RolesEnum::USER->value;
        }
        $user->syncRoles([$requestedRole]);

        // Filter assignable companies for Admin
        $assignable = Company::query();
        if (! $auth->hasRole(RolesEnum::SUPERADMIN->value)) {
            $assignable->where('created_by', $auth->getKey());
        }
        $assignableIds = $assignable->whereIn('id', $companyIds)->pluck('id');

        $user->companies()->sync($assignableIds);

        return redirect()->route('users.index')->with('success', 'User created');
    }

    public function edit(User $user)
    {
        $this->authorize('update', $user);

        $auth = request()->user();

        $companiesQuery = Company::query();
        if (! $auth->hasRole(RolesEnum::SUPERADMIN->value)) {
            $companiesQuery->where('created_by', $auth->getKey());
        }
        $companies = $companiesQuery->orderBy('name')->get(['id', 'name']);

        $currentCompanyIds = $user->companies()->pluck('companies.id');

        // Roles available to assign and current role
        $allRoles = [
            RolesEnum::SUPERADMIN->value,
            RolesEnum::ADMIN->value,
            RolesEnum::MANAGER->value,
            RolesEnum::USER->value,
        ];
        $assignableRoles = $auth->hasRole(RolesEnum::SUPERADMIN->value)
            ? $allRoles
            : [RolesEnum::ADMIN->value, RolesEnum::MANAGER->value, RolesEnum::USER->value];
        $roles = collect($assignableRoles)->map(fn ($r) => [
            'value' => $r,
            'label' => match ($r) {
                RolesEnum::SUPERADMIN->value => 'Super Admin',
                RolesEnum::ADMIN->value => 'Admin',
                RolesEnum::MANAGER->value => 'Manager',
                default => 'User',
            },
        ]);
        $currentRole = $user->getRoleNames()->first();

        return Inertia::render('users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'companies' => $companies,
            'assigned_company_ids' => $currentCompanyIds,
            'roles' => $roles,
            'current_role' => $currentRole,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $auth = $request->user();

        $user->name = $data['name'];
        $user->email = $data['email'];
        if (! empty($data['password'])) {
            $user->password = bcrypt($data['password']);
        }
        $user->save();

        // Role assignment (optional)
        if (! empty($data['role'])) {
            $assignableRoles = $auth->hasRole(RolesEnum::SUPERADMIN->value)
                ? [RolesEnum::SUPERADMIN->value, RolesEnum::ADMIN->value, RolesEnum::MANAGER->value, RolesEnum::USER->value]
                : [RolesEnum::ADMIN->value, RolesEnum::MANAGER->value, RolesEnum::USER->value];
            $role = in_array($data['role'], $assignableRoles, true) ? $data['role'] : null;
            if ($role) {
                $user->syncRoles([$role]);
            }
        }

        // Company assignment
        $companyIds = collect($data['company_ids'] ?? [])->filter();
        $assignable = Company::query();
        if (! $auth->hasRole(RolesEnum::SUPERADMIN->value)) {
            $assignable->where('created_by', $auth->getKey());
        }
        $assignableIds = $assignable->whereIn('id', $companyIds)->pluck('id');
        $user->companies()->sync($assignableIds);

        return redirect()->route('users.index')->with('success', 'User updated');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);
        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted');
    }
}
