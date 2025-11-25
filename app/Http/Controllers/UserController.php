<?php

namespace App\Http\Controllers;

use App\Enums\RolesEnum;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Mail\UserCreated;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class UserController extends BaseController
{
    public function index()
    {
        Gate::authorize('viewAny', User::class);

        $auth = request()->user();

        $query = User::query()->with(['profile', 'contactCard']);

        if ($auth->hasRole(RolesEnum::SUPERADMIN->value)) {
            // no filter
        } elseif ($auth->hasRole(RolesEnum::ADMIN->value)) {
            // Admin: users they created OR users in the same companies
            $userCompanyIds = $auth->companies()->pluck('companies.id');
            $query->where(function ($q) use ($auth, $userCompanyIds) {
                $q->where('created_by', $auth->getKey())
                    ->orWhereHas('companies', function ($qc) use ($userCompanyIds) {
                        $qc->whereIn('companies.id', $userCompanyIds);
                    });
            });
        } else {
            // Manager/User: users who share a TEAM with the auth user
            $userTeamIds = $auth->teams()->pluck('teams.id');
            $query->whereHas('teams', function ($q) use ($userTeamIds) {
                $q->whereIn('teams.id', $userTeamIds);
            });
        }

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = $this->perPage();
        $users = $query->orderBy('name')->paginate($perPage)->withQueryString();

        return Inertia::render('users/Index', [
            'users' => $users,
            'filters' => [
                'search' => (string) request('search', ''),
                'per_page' => $perPage,
            ],
            'auth_id' => $auth->getKey(),
        ]);
    }

    public function show(User $user)
    {
        Gate::authorize('view', $user);

        return Inertia::render('users/Show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function create()
    {
        Gate::authorize('create', User::class);

        $auth = request()->user();

        $companiesQuery = Company::query();
        if (! $auth->hasRole(RolesEnum::SUPERADMIN->value)) {
            // Admin can assign companies they created OR companies they are a member of
            $companiesQuery->where(function ($q) use ($auth) {
                $q->where('created_by', $auth->getKey())
                    ->orWhereHas('users', function ($m) use ($auth) {
                        $m->where('users.id', $auth->getKey());
                    });
            });
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
        try {
            Gate::authorize('create', User::class);

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
            Mail::to($user->email)->send(new UserCreated($user, $tempPassword));

            // Assign role (limit by assignable set)
            $requestedRole = $data['role'] ?? RolesEnum::USER->value;
            $assignableRoles = $auth->hasRole(RolesEnum::SUPERADMIN->value)
                ? [RolesEnum::SUPERADMIN->value, RolesEnum::ADMIN->value, RolesEnum::MANAGER->value, RolesEnum::USER->value]
                : [RolesEnum::ADMIN->value, RolesEnum::MANAGER->value, RolesEnum::USER->value];
            if (! in_array($requestedRole, $assignableRoles, true)) {
                $requestedRole = RolesEnum::USER->value;
            }
            $user->syncRoles([$requestedRole]);

            // Filter assignable companies for Admin: created by admin OR admin is a member
            $assignable = Company::query();
            if (! $auth->hasRole(RolesEnum::SUPERADMIN->value)) {
                $assignable->where(function ($q) use ($auth) {
                    $q->where('created_by', $auth->getKey())
                        ->orWhereHas('users', function ($m) use ($auth) {
                            $m->where('users.id', $auth->getKey());
                        });
                });
            }
            $assignableIds = $assignable->whereIn('id', $companyIds)->pluck('id');
            $user->companies()->sync($assignableIds);

            return $this->successRedirect('members.index', 'User created');
        } catch (\Throwable $th) {
            $this->logException($th, 'create user', [
                'data' => $request->validated(),
            ]);

            return $this->errorBack('Failed to create user');
        }
    }

    public function edit(User $user)
    {
        Gate::authorize('update', $user);

        $auth = request()->user();

        $companiesQuery = Company::query();
        if (! $auth->hasRole(RolesEnum::SUPERADMIN->value)) {
            // Admin can assign companies they created OR companies they are a member of
            $companiesQuery->where(function ($q) use ($auth) {
                $q->where('created_by', $auth->getKey())
                    ->orWhereHas('users', function ($m) use ($auth) {
                        $m->where('users.id', $auth->getKey());
                    });
            });
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
                'profile' => [
                    'bio' => $user->profile?->bio ?? null,
                    'position' => $user->profile?->position ?? null,
                    'phone' => $user->profile?->phone ?? null,
                    'website' => $user->profile?->website ?? null,
                    'linkedin' => $user->profile?->linkedin ?? null,
                    'twitter' => $user->profile?->twitter ?? null,
                    'facebook' => $user->profile?->facebook ?? null,
                    'instagram' => $user->profile?->instagram ?? null,
                    'photo' => $user->profile?->photo ?? null,
                    'cover_photo' => $user->profile?->cover_photo ?? null,
                ],
            ],
            'companies' => $companies,
            'assigned_company_ids' => $currentCompanyIds,
            'roles' => $roles,
            'current_role' => $currentRole,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        try {
            Gate::authorize('update', $user);

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

            // Company assignment: only process if there is an actual change
            $companyIds = collect($data['company_ids'] ?? [])->filter();
            $currentCompanyIds = $user->companies()->pluck('companies.id');
            $isChanged = $currentCompanyIds->sort()->values()->toJson() !== $companyIds->sort()->values()->toJson();

            if ($isChanged) {
                $assignable = Company::query();
                if (! $auth->hasRole(RolesEnum::SUPERADMIN->value)) {
                    // Ensure Admin is eligible to assign this user: creator OR shares at least one company
                    $adminCompanyIds = $auth->companies()->pluck('companies.id');
                    $eligible = ($user->created_by === $auth->getKey())
                        || $user->companies()->whereIn('companies.id', $adminCompanyIds)->exists();
                    abort_unless($eligible, 403);

                    // Admin can assign to companies they created OR companies they are a member of
                    $assignable->where(function ($q) use ($auth) {
                        $q->where('created_by', $auth->getKey())
                            ->orWhereHas('users', function ($m) use ($auth) {
                                $m->where('users.id', $auth->getKey());
                            });
                    });
                }
                $assignableIds = $assignable->whereIn('id', $companyIds)->pluck('id');
                $user->companies()->sync($assignableIds);
            }

            // Profile upsert (basic fields + images)
            $profile = (array) ($data['profile'] ?? []);
            $allowedProfileKeys = [
                'bio', 'position', 'phone', 'website', 'linkedin', 'twitter', 'facebook', 'instagram', 'photo', 'cover_photo',
            ];
            $profileData = array_intersect_key($profile, array_flip($allowedProfileKeys));
            // Normalize empty strings to nulls
            $profileData = array_map(function ($v) {
                return ($v === '') ? null : $v;
            }, $profileData);

            // Load existing profile to manage file replacements/removals
            $existingProfile = $user->profile()->first();

            // Handle uploaded photo
            if ($request->hasFile('photo_file')) {
                $file = $request->file('photo_file');
                if ($file && $file->isValid()) {
                    if ($existingProfile && ! empty($existingProfile->photo)) {
                        try {
                            Storage::disk('public')->delete($existingProfile->photo);
                        } catch (\Throwable $e) {
                        }
                    }
                    $path = $file->store('profiles/photos', 'public');
                    $profileData['photo'] = $path;
                }
            } elseif ((bool) $request->boolean('photo_removed')) {
                if ($existingProfile && ! empty($existingProfile->photo)) {
                    try {
                        Storage::disk('public')->delete($existingProfile->photo);
                    } catch (\Throwable $e) {
                    }
                }
                $profileData['photo'] = null;
            }

            // Handle uploaded cover photo
            if ($request->hasFile('cover_photo_file')) {
                $file = $request->file('cover_photo_file');
                if ($file && $file->isValid()) {
                    if ($existingProfile && ! empty($existingProfile->cover_photo)) {
                        try {
                            Storage::disk('public')->delete($existingProfile->cover_photo);
                        } catch (\Throwable $e) {
                        }
                    }
                    $path = $file->store('profiles/covers', 'public');
                    $profileData['cover_photo'] = $path;
                }
            } elseif ((bool) $request->boolean('cover_photo_removed')) {
                if ($existingProfile && ! empty($existingProfile->cover_photo)) {
                    try {
                        Storage::disk('public')->delete($existingProfile->cover_photo);
                    } catch (\Throwable $e) {
                    }
                }
                $profileData['cover_photo'] = null;
            }

            if (! empty($profileData)) {
                $user->profile()->updateOrCreate([], $profileData);
            }

            return $this->successRedirect('members.index', 'User updated');
        } catch (\Throwable $th) {
            $this->logException($th, 'update user', [
                'user_id' => $user->getKey(),
                'data' => $request->validated(),
            ]);

            return $this->errorBack('Failed to update user');
        }
    }

    public function destroy(User $user): RedirectResponse
    {
        try {
            Gate::authorize('delete', $user);
            DB::transaction(function () use ($user) {
                // Detach relationships
                $user->companies()->detach();
                $user->teams()->detach();

                // Remove roles and direct permissions (Spatie)
                if (method_exists($user, 'syncRoles')) {
                    $user->syncRoles([]);
                }
                if (method_exists($user, 'syncPermissions')) {
                    $user->syncPermissions([]);
                }

                // Delete related profile (and its media if any)
                if ($user->relationLoaded('profile')) {
                    $profile = $user->profile;
                } else {
                    $profile = $user->profile()->first();
                }
                if ($profile) {
                    try {
                        if (! empty($profile->photo)) {
                            Storage::disk('public')->delete($profile->photo);
                        }
                        if (! empty($profile->cover_photo)) {
                            Storage::disk('public')->delete($profile->cover_photo);
                        }
                    } catch (\Throwable $e) {
                        // ignore file delete errors
                    }
                    $profile->delete();
                }

                // Delete related contact card if exists
                if ($user->relationLoaded('contactCard')) {
                    $contact = $user->contactCard;
                } else {
                    $contact = $user->contactCard()->first();
                }
                if ($contact) {
                    $contact->delete();
                }

                // Finally, delete the user
                $user->delete();
            });

            return $this->successRedirect('members.index', 'User deleted');
        } catch (\Throwable $th) {
            $this->logException($th, 'delete user', [
                'user_id' => $user->getKey(),
            ]);

            return $this->errorBack('Failed to delete user');
        }
    }

    public function companies(User $user)
    {
        $auth = request()->user();
        // Super Admin: all companies of target user
        if ($auth->hasRole(RolesEnum::SUPERADMIN->value)) {
            $companies = $user->companies()->orderBy('name')->get(['companies.id', 'companies.name']);

            return response()->json($companies);
        }

        // Admin: only if they created the user
        if ($auth->hasRole(RolesEnum::ADMIN->value)) {
            abort_unless($user->created_by === $auth->getKey(), 403);
            $companies = $user->companies()->orderBy('name')->get(['companies.id', 'companies.name']);

            return response()->json($companies);
        }

        // Manager: only companies shared with the manager
        if ($auth->hasRole(RolesEnum::MANAGER->value)) {
            $managerCompanyIds = $auth->companies()->pluck('companies.id');
            $companies = $user->companies()
                ->whereIn('companies.id', $managerCompanyIds)
                ->orderBy('name')
                ->get(['companies.id', 'companies.name']);

            return response()->json($companies);
        }

        abort(403);
    }
}
