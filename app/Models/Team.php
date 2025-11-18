<?php

namespace App\Models;

use App\Enums\RolesEnum;
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
