<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enums\RolesEnum;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use LaravelAndVueJS\Traits\LaravelPermissionToVueJS;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, HasUlids, LaravelPermissionToVueJS, Notifiable, TwoFactorAuthenticatable;

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole(RolesEnum::SUPERADMIN->value)) {
            return $query;
        }

        if ($user->hasRole(RolesEnum::ADMIN->value)) {
            $companyIds = $user->companies()->pluck('companies.id');

            return $query->where(function (Builder $query) use ($user, $companyIds) {
                $query->where('created_by', $user->getKey())
                    ->orWhereHas('companies', function (Builder $companies) use ($companyIds) {
                        $companies->whereIn('companies.id', $companyIds);
                    });
            });
        }

        $teamIds = $user->teams()->pluck('teams.id');

        return $query->whereHas('teams', function (Builder $teams) use ($teamIds) {
            $teams->whereIn('teams.id', $teamIds);
        });
    }

    public function scopeAssignableBy(Builder $query, User $user): Builder
    {
        if ($user->hasRole(RolesEnum::SUPERADMIN->value)) {
            return $query;
        }

        $companyIds = $user->companies()->pluck('companies.id');

        return $query->where(function (Builder $query) use ($user, $companyIds) {
            $query->where('created_by', $user->getKey())
                ->orWhereHas('companies', function (Builder $companies) use ($companyIds) {
                    $companies->whereIn('companies.id', $companyIds);
                });
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factory_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Companies this user is a member of.
     */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)
            ->withTimestamps();
    }

    /**
     * Teams this user is a member of.
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)
            ->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function createdUsers(): HasMany
    {
        return $this->hasMany(User::class, 'created_by');
    }

    /**
     * One-to-one profile for this user.
     */
    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    /**
     * One-to-one contact card for this user.
     */
    public function contactCard(): HasOne
    {
        return $this->hasOne(ContactCard::class);
    }

    /**
     * The primary role name for this user.
     */
    public function getPrimaryRoleAttribute(): ?string
    {
        return $this->getRoleNames()->first() ?: null;
    }

    /**
     * A human-readable label for the primary role.
     */
    public function getRoleLabelAttribute(): ?string
    {
        $role = $this->primary_role;

        return match ($role) {
            RolesEnum::SUPERADMIN->value => 'Super Admin',
            RolesEnum::ADMIN->value => 'Admin',
            RolesEnum::MANAGER->value => 'Manager',
            RolesEnum::USER->value => 'User',
            default => $role,
        };
    }

    protected $appends = [
        'primary_role',
        'role_label',
    ];
}
