<?php

namespace App\Http\Controllers;

use App\Enums\RolesEnum;
use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
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

            // Handle image uploads: logo_light, logo_dark, bg_light, bg_dark
            foreach (['logo_light', 'logo_dark', 'bg_light', 'bg_dark'] as $imageField) {
                if ($request->hasFile($imageField)) {
                    $path = $request->file($imageField)->store('companies', 'public');
                    $data[$imageField] = Storage::url($path);
                }
            }

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
            $company->users()->sync($assignableIds->all());

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

        return Inertia::render('companies/Show', [
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
            // Handle image removals/replacements: logo_light, logo_dark, bg_light, bg_dark
            foreach (['logo_light', 'logo_dark', 'bg_light', 'bg_dark'] as $imageField) {
                $removeFlag = $request->boolean('remove_'.$imageField);
                $hasNewFile = $request->hasFile($imageField);

                // delete existing file if removal requested or a new file is uploaded
                if (($removeFlag || $hasNewFile) && $company->{$imageField}) {
                    $publicPrefix = '/storage/';
                    $relativePath = str_starts_with($company->{$imageField}, $publicPrefix)
                        ? substr($company->{$imageField}, strlen($publicPrefix))
                        : ltrim($company->{$imageField}, '/');
                    Storage::disk('public')->delete($relativePath);
                }

                if ($removeFlag && ! $hasNewFile) {
                    // Remove only
                    $data[$imageField] = null;
                }

                if ($hasNewFile) {
                    $path = $request->file($imageField)->store('companies', 'public');
                    $data[$imageField] = Storage::url($path);
                }
            }

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
            DB::transaction(function () use ($company) {
                // Detach company members
                $company->users()->detach();

                // Remove company stored assets if present
                foreach (['logo_light', 'logo_dark', 'bg_light', 'bg_dark'] as $imageField) {
                    if (! empty($company->{$imageField})) {
                        $publicPrefix = '/storage/';
                        $relativePath = str_starts_with($company->{$imageField}, $publicPrefix)
                            ? substr($company->{$imageField}, strlen($publicPrefix))
                            : ltrim($company->{$imageField}, '/');
                        try {
                            Storage::disk('public')->delete($relativePath);
                        } catch (\Throwable $e) {
                            // ignore file delete errors
                        }
                    }
                }

                // Delete teams under this company (and detach their users)
                $company->teams()->each(function ($team) {
                    try {
                        $team->users()->detach();
                    } catch (\Throwable $e) {
                        // ignore
                    }
                    $team->delete();
                });

                // Finally delete the company
                $company->delete();
            });

            return $this->successRedirect('companies.index', 'Company deleted');
        } catch (\Throwable $th) {
            $this->logException($th, 'delete company', [
                'company_id' => $company->getKey(),
            ]);

            return $this->errorBack('Failed to delete company');
        }
    }

    public function teams(Company $company)
    {
        $auth = request()->user();
        // Basic access: must be creator or a member of the company, unless super-admin
        if (! $auth->hasRole(RolesEnum::SUPERADMIN->value)) {
            $allowed = ($company->created_by === $auth->getKey())
                || $company->users()->where('users.id', $auth->getKey())->exists();
            abort_unless($allowed, 403);
        }

        $query = $company->teams()->orderBy('name');

        // Optional filter by user membership (for selecting teams the target user belongs to)
        $userId = request('user_id');
        if ($userId) {
            $query->whereHas('users', function ($q) use ($userId) {
                $q->where('users.id', $userId);
            });
        }

        $teams = $query->get(['teams.id', 'teams.name']);

        return response()->json($teams);
    }

    /**
     * Assign selected users to the company (adds, does not remove existing).
     */
    public function assignUsers(Request $request, Company $company)
    {
        Gate::authorize('update', $company);

        $auth = $request->user();
        $ids = collect($request->input('user_ids', []))->filter();

        if ($auth->hasRole(RolesEnum::SUPERADMIN->value)) {
            $company->users()->sync($ids->all());
        } elseif ($auth->hasRole(RolesEnum::ADMIN->value)) {
            // Admin can target companies they created or are assigned to
            $canTarget = ($company->created_by === $auth->getKey())
                || $company->users()->where('users.id', $auth->getKey())->exists();
            abort_unless($canTarget, 403);

            // Filter to users admin can assign: created by them or in their companies
            $adminCompanyIds = $auth->companies()->pluck('companies.id');
            $assignableIds = User::query()
                ->whereIn('id', $ids)
                ->where(function ($q) use ($auth, $adminCompanyIds) {
                    $q->where('created_by', $auth->getKey())
                        ->orWhereHas('companies', function ($qc) use ($adminCompanyIds) {
                            $qc->whereIn('companies.id', $adminCompanyIds);
                        });
                })
                ->pluck('id')
                ->unique();
            $company->users()->syncWithoutDetaching($assignableIds->all());
        } else {
            abort(403);
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Members assigned');
    }
}
