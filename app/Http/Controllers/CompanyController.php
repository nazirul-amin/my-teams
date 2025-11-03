<?php

namespace App\Http\Controllers;

use App\Enums\RolesEnum;
use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class CompanyController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('viewAny', Company::class);

        $user = request()->user();

        $query = Company::query()->with(['creator:id,name']);

        // Super Admin: all; others: companies they created OR where they are a member
        if (! $user->hasRole(RolesEnum::SUPERADMIN->value)) {
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->getKey())
                    ->orWhereHas('users', function ($m) use ($user) {
                        $m->where('users.id', $user->getKey());
                    });
            });
        }

        // Optional simple filters
        $this->applySearch($query, ['name', 'slug']);

        $perPage = $this->perPage();

        $companies = $query->orderByDesc('created_at')->paginate($perPage)->withQueryString();

        return Inertia::render('companies/Index', [
            'companies' => $companies,
            'filters' => [
                'search' => (string) request('search', ''),
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create', Company::class);
        $auth = request()->user();

        $usersQuery = User::query();
        if ($auth->hasRole(RolesEnum::ADMIN->value)) {
            $adminCompanyIds = $auth->companies()->pluck('companies.id');
            $usersQuery->where(function ($q) use ($auth, $adminCompanyIds) {
                $q->where('created_by', $auth->getKey())
                    ->orWhereHas('companies', function ($qc) use ($adminCompanyIds) {
                        $qc->whereIn('companies.id', $adminCompanyIds);
                    });
            });
        }
        $users = $usersQuery->orderBy('name')->get(['id', 'name', 'email']);

        return Inertia::render('companies/Create', [
            'users' => $users,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCompanyRequest $request)
    {
        try {
            Gate::authorize('create', Company::class);
            $data = $request->validated();
            $data['created_by'] = $request->user()->getKey();

            $company = Company::create($data);

            // Sync additional members based on role constraints
            $auth = $request->user();
            $ids = collect($data['user_ids'] ?? [])->filter();

            $assignable = User::query();
            if ($auth->hasRole(RolesEnum::ADMIN->value)) {
                $adminCompanyIds = $auth->companies()->pluck('companies.id');
                $assignable->where(function ($q) use ($auth, $adminCompanyIds) {
                    $q->where('created_by', $auth->getKey())
                        ->orWhereHas('companies', function ($qc) use ($adminCompanyIds) {
                            $qc->whereIn('companies.id', $adminCompanyIds);
                        });
                });
            }
            $assignableIds = $assignable->whereIn('id', $ids)->pluck('id')->unique();
            $company->users()->syncWithoutDetaching($assignableIds->all());

            return $this->successRedirect('companies.index', 'Company created');
        } catch (\Throwable $th) {
            $this->logException($th, 'create company', [
                'data' => $request->validated(),
            ]);

            return $this->errorBack('Failed to create company');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Company $company)
    {
        Gate::authorize('view', $company);

        return Inertia::render('companies/Edit', [
            'company' => $company,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Company $company)
    {
        Gate::authorize('update', $company);
        $auth = request()->user();

        $usersQuery = User::query();
        if ($auth->hasRole(RolesEnum::ADMIN->value)) {
            $adminCompanyIds = $auth->companies()->pluck('companies.id');
            $usersQuery->where(function ($q) use ($auth, $adminCompanyIds) {
                $q->where('created_by', $auth->getKey())
                    ->orWhereHas('companies', function ($qc) use ($adminCompanyIds) {
                        $qc->whereIn('companies.id', $adminCompanyIds);
                    });
            });
        }
        $users = $usersQuery->orderBy('name')->get(['id', 'name', 'email']);

        return Inertia::render('companies/Edit', [
            'company' => $company,
            'users' => $users,
            'assigned_user_ids' => $company->users()->pluck('users.id'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCompanyRequest $request, Company $company)
    {
        try {
            Gate::authorize('update', $company);
            $data = $request->validated();
            $company->update($data);

            // Sync members based on role constraints
            $auth = $request->user();
            $ids = collect($data['user_ids'] ?? [])->filter();

            $assignable = User::query();
            if ($auth->hasRole(RolesEnum::ADMIN->value)) {
                $adminCompanyIds = $auth->companies()->pluck('companies.id');
                $assignable->where(function ($q) use ($auth, $adminCompanyIds) {
                    $q->where('created_by', $auth->getKey())
                        ->orWhereHas('companies', function ($qc) use ($adminCompanyIds) {
                            $qc->whereIn('companies.id', $adminCompanyIds);
                        });
                });
            }
            $assignableIds = $assignable->whereIn('id', $ids)->pluck('id')->unique();
            $company->users()->sync($assignableIds->all());

            return $this->successRedirect('companies.index', 'Company updated');
        } catch (\Throwable $th) {
            $this->logException($th, 'update company', [
                'company_id' => $company->getKey(),
                'data' => $request->validated(),
            ]);

            return $this->errorBack('Failed to update company');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company)
    {
        try {
            Gate::authorize('delete', $company);
            $company->users()->detach();
            $company->delete();

            return $this->successRedirect('companies.index', 'Company deleted');
        } catch (\Throwable $th) {
            $this->logException($th, 'delete company', [
                'company_id' => $company->getKey(),
            ]);

            return $this->errorBack('Failed to delete company');
        }
    }

    /**
     * Return users assigned to a company that the current user can use for team assignment.
     */
    public function users(Company $company)
    {
        // Access check: user must be able to create/update teams for this company
        $auth = request()->user();
        if (! $auth->hasRole(RolesEnum::SUPERADMIN->value)) {
            $allowed = Company::query()
                ->whereKey($company->getKey())
                ->where(function ($q) use ($auth) {
                    $q->where('created_by', $auth->getKey())
                        ->orWhereHas('users', function ($m) use ($auth) {
                            $m->where('users.id', $auth->getKey());
                        });
                })
                ->exists();

            abort_unless($allowed, 403);
        }

        $users = $company->users()->orderBy('name')->get(['users.id', 'users.name', 'users.email']);

        return response()->json($users);
    }
}
