<?php

namespace App\Http\Controllers;

use App\Enums\RolesEnum;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', User::class);

        $auth = request()->user();

        $query = User::query()->with('creator');

        // Super Admin sees all; Admin sees users they created
        if (! $auth->hasRole(RolesEnum::SUPERADMIN->value)) {
            $query->where('created_by', $auth->getKey());
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

        return Inertia::render('users/Create', [
            'companies' => $companies,
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
        $user->password = bcrypt($data['password']);
        $user->created_by = $auth->getKey();
        $user->save();

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

        return Inertia::render('users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'companies' => $companies,
            'assigned_company_ids' => $currentCompanyIds,
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
