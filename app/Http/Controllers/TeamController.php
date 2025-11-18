<?php

namespace App\Http\Controllers;

use App\Enums\RolesEnum;
use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Models\Company;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class TeamController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('viewAny', Team::class);

        $user = request()->user();

        $query = Team::query()->with(['company:id,name,created_by']);

        if ($user->hasRole(RolesEnum::SUPERADMIN->value)) {
            // no additional restrictions
        } elseif ($user->hasRole(RolesEnum::ADMIN->value)) {
            // Admin: teams of companies they created OR are assigned to
            $query->whereHas('company', function ($q) use ($user) {
                $q->whereHas('users', function ($m) use ($user) {
                    $m->where('users.id', $user->getKey());
                })->orWhere('created_by', $user->getKey());
            });
        } else {
            // Managers and regular users: only teams they are assigned to
            $query->whereHas('users', function ($q) use ($user) {
                $q->where('users.id', $user->getKey());
            });
        }

        // Expose whether the auth user is a member of each team (for UI gating)
        $query->withCount(['users as is_member' => function ($q) use ($user) {
            $q->where('users.id', $user->getKey());
        }]);

        $this->applySearch($query, ['name', 'slug']);
        $perPage = $this->perPage();
        $teams = $query->orderByDesc('created_at')->paginate($perPage)->withQueryString();

        return Inertia::render('teams/Index', [
            'teams' => $teams,
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
        Gate::authorize('create', Team::class);
        $auth = request()->user();

        // Companies the user can create a team for
        $companies = Company::query()
            ->when(! $auth->hasRole(RolesEnum::SUPERADMIN->value), function ($q) use ($auth) {
                $q->where(function ($qq) use ($auth) {
                    $qq->whereHas('users', function ($m) use ($auth) {
                        $m->where('user_id', $auth->getKey());
                    });
                })->orWhere('created_by', $auth->getKey());
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('teams/Create', [
            'companies' => $companies,
            'users' => [],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTeamRequest $request)
    {
        try {
            Gate::authorize('create', Team::class);
            $data = $request->validated();

            $auth = request()->user();

            // Check company access according to role
            $allowedCompanies = Company::query()
                ->when(! $auth->hasRole(RolesEnum::SUPERADMIN->value), function ($q) use ($auth) {
                    $q->where(function ($qq) use ($auth) {
                        $qq->whereHas('users', function ($m) use ($auth) {
                            $m->where('user_id', $auth->getKey());
                        });
                    })->orWhere('created_by', $auth->getKey());
                })
                ->pluck('id');

            if (! $allowedCompanies->contains($data['company_id'])) {
                abort(403);
            }

            $data['created_by'] = $auth->getKey();

            // Handle logo uploads (no deletion needed on create)
            foreach (['logo_light', 'logo_dark'] as $imageField) {
                if ($request->hasFile($imageField)) {
                    $path = $request->file($imageField)->store('teams', 'public');
                    $data[$imageField] = Storage::url($path);
                }
            }

            $team = Team::create($data);

            // Sync members limited to selected company's users
            $ids = collect($data['user_ids'] ?? [])->filter();
            $companyUserIds = Company::query()
                ->whereKey($data['company_id'])
                ->firstOrFail()
                ->users()
                ->pluck('users.id')
                ->unique();
            $team->users()->syncWithoutDetaching($companyUserIds->intersect($ids)->all());

            return $this->successRedirect('teams.index', 'Team created');
        } catch (\Throwable $th) {
            $this->logException($th, 'create team', [
                'data' => $request->validated(),
            ]);

            return $this->errorBack('Failed to create team');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Team $team)
    {
        Gate::authorize('view', $team);

        return Inertia::render('teams/Show', [
            'team' => $team->load('company:id,name'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Team $team)
    {
        Gate::authorize('update', $team);
        $auth = request()->user();

        $companies = Company::query()
            ->when(! $auth->hasRole(RolesEnum::SUPERADMIN->value), function ($q) use ($auth) {
                $q->where(function ($qq) use ($auth) {
                    $qq->whereHas('users', function ($m) use ($auth) {
                        $m->where('user_id', $auth->getKey());
                    });
                })->orWhere('created_by', $auth->getKey());
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        // Users limited to the team's company members
        $users = $team->company->users()->orderBy('name')->get(['users.id', 'users.name', 'users.email']);

        return Inertia::render('teams/Edit', [
            'team' => $team,
            'companies' => $companies,
            'users' => $users,
            'assigned_user_ids' => $team->users()->pluck('users.id'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTeamRequest $request, Team $team)
    {
        try {
            Gate::authorize('update', $team);
            $data = $request->validated();

            $auth = request()->user();
            if (isset($data['company_id'])) {
                $allowedCompanies = Company::query()
                    ->when(! $auth->hasRole(RolesEnum::SUPERADMIN->value), function ($q) use ($auth) {
                        $q->where(function ($qq) use ($auth) {
                            $qq->whereHas('users', function ($m) use ($auth) {
                                $m->where('user_id', $auth->getKey());
                            });
                        })->orWhere('created_by', $auth->getKey());
                    })
                    ->pluck('id');

                if (! $allowedCompanies->contains($data['company_id'])) {
                    abort(403);
                }
            }

            // Handle logo removal or replacement for logo_light, logo_dark
            foreach (['logo_light', 'logo_dark'] as $imageField) {
                $removeFlag = $request->boolean('remove_'.$imageField);
                $hasNew = $request->hasFile($imageField);

                if (($removeFlag || $hasNew) && $team->{$imageField}) {
                    $publicPrefix = '/storage/';
                    $relativePath = str_starts_with($team->{$imageField}, $publicPrefix)
                        ? substr($team->{$imageField}, strlen($publicPrefix))
                        : ltrim($team->{$imageField}, '/');
                    Storage::disk('public')->delete($relativePath);
                }

                if ($removeFlag && ! $hasNew) {
                    $data[$imageField] = null;
                }

                if ($hasNew) {
                    $path = $request->file($imageField)->store('teams', 'public');
                    $data[$imageField] = Storage::url($path);
                }
            }

            $team->update($data);

            // Sync members limited to selected company's users
            $ids = collect($data['user_ids'] ?? [])->filter();
            $companyId = $data['company_id'] ?? $team->company_id;
            $companyUserIds = Company::query()
                ->whereKey($companyId)
                ->firstOrFail()
                ->users()
                ->pluck('users.id')
                ->unique();
            $team->users()->sync($companyUserIds->intersect($ids)->all());

            // Ensure team-company consistency: if company changed, it's already updated via $team->update($data)

            return $this->successRedirect('teams.index', 'Team updated');
        } catch (\Throwable $th) {
            $this->logException($th, 'update team', [
                'team_id' => $team->getKey(),
                'data' => $request->validated(),
            ]);

            return $this->errorBack('Failed to update team');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Team $team)
    {
        try {
            Gate::authorize('delete', $team);
            DB::transaction(function () use ($team) {
                // Detach members
                $team->users()->detach();

                // Remove stored logo assets if present
                foreach (['logo_light', 'logo_dark'] as $imageField) {
                    if (! empty($team->{$imageField})) {
                        $publicPrefix = '/storage/';
                        $relativePath = str_starts_with($team->{$imageField}, $publicPrefix)
                            ? substr($team->{$imageField}, strlen($publicPrefix))
                            : ltrim($team->{$imageField}, '/');
                        try {
                            Storage::disk('public')->delete($relativePath);
                        } catch (\Throwable $e) {
                            // ignore file delete errors
                        }
                    }
                }

                // Delete team
                $team->delete();
            });

            return $this->successRedirect('teams.index', 'Team deleted');
        } catch (\Throwable $th) {
            $this->logException($th, 'delete team', [
                'team_id' => $team->getKey(),
            ]);

            return $this->errorBack('Failed to delete team');
        }
    }

    /**
     * Assign selected users to the team (adds, does not remove existing). Only users from the team's company are allowed.
     */
    public function assignUsers(Request $request, Team $team)
    {
        Gate::authorize('update', $team);

        $auth = $request->user();
        $ids = collect($request->input('user_ids', []))->filter();

        if ($auth->hasRole(RolesEnum::SUPERADMIN->value)) {
            // Only assign users who are already members of the team's company
            $companyUserIds = $team->company->users()->pluck('users.id')->unique();
            $attachIds = $companyUserIds->intersect($ids)->all();
            $team->users()->sync($attachIds);
        } elseif ($auth->hasRole(RolesEnum::ADMIN->value)) {
            // Can target teams they created or are assigned to or whose company they created/are assigned to
            $canTarget = ($team->created_by === $auth->getKey())
                || $team->users()->where('users.id', $auth->getKey())->exists()
                || ($team->company->created_by === $auth->getKey())
                || $team->company->users()->where('users.id', $auth->getKey())->exists();
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

            // Only assign users who are already members of the team's company
            $companyUserIds = $team->company->users()->pluck('users.id')->unique();
            $attachIds = $companyUserIds->intersect($assignableIds)->all();
            $team->users()->sync($attachIds);
        } elseif ($auth->hasRole(RolesEnum::MANAGER->value)) {
            // Only teams assigned to them
            $canTarget = $team->users()->where('users.id', $auth->getKey())->exists();
            abort_unless($canTarget, 403);

            // Users must be in same companies as manager and already in the team's company
            $managerCompanyIds = $auth->companies()->pluck('companies.id');
            $eligibleIds = User::query()
                ->whereIn('id', $ids)
                ->whereHas('companies', function ($q) use ($managerCompanyIds) {
                    $q->whereIn('companies.id', $managerCompanyIds);
                })
                ->pluck('id')
                ->unique();

            $companyUserIds = $team->company->users()->pluck('users.id')->unique();
            $attachIds = $companyUserIds->intersect($eligibleIds)->all();
            $team->users()->sync($attachIds);
        } else {
            abort(403);
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Members assigned to team');
    }
}
