<?php

namespace App\Models;

use App\Enums\RolesEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasUlids;

    protected $guarded = ['id'];

    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole(RolesEnum::SUPERADMIN->value)) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user) {
            $query->where('created_by', $user->getKey())
                ->orWhereHas('users', function (Builder $users) use ($user) {
                    $users->where('users.id', $user->getKey());
                });
        });
    }

    /**
     * Owner/creator of the company.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Users who are members of this company (with pivot role).
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /**
     * Teams under this company.
     */
    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }
}
