<?php

namespace App\Models;

use App\Enums\RolesEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Team extends Model
{
    use HasUlids;

    protected $guarded = ['id'];

    protected $appends = [
        'manager_name',
        'manager_names',
    ];

    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole(RolesEnum::SUPERADMIN->value)) {
            return $query;
        }

        if ($user->hasRole(RolesEnum::ADMIN->value)) {
            return $query->whereHas('company', function (Builder $company) use ($user) {
                $company->where('created_by', $user->getKey())
                    ->orWhereHas('users', function (Builder $members) use ($user) {
                        $members->where('users.id', $user->getKey());
                    });
            });
        }

        return $query->whereHas('users', function (Builder $members) use ($user) {
            $members->where('users.id', $user->getKey());
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function getManagerNameAttribute(): ?string
    {
        return $this->users()
            ->role(RolesEnum::MANAGER->value)
            ->value('name');
    }

    public function getManagerNamesAttribute(): array
    {
        return $this->users()
            ->role(RolesEnum::MANAGER->value)
            ->pluck('name')
            ->filter()
            ->values()
            ->all();
    }
}
