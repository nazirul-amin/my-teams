<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Settings\ContactCardGenerateRequest;
use App\Models\Company;
use App\Models\ContactCard;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactCardController extends BaseController
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        $companies = $user->companies()->orderBy('name')->get(['companies.id', 'companies.name']);

        $teams = Team::query()
            ->whereHas('company', function ($q) use ($user) {
                $q->whereIn('companies.id', $user->companies()->pluck('companies.id'));
            })
            ->whereHas('users', function ($q) use ($user) {
                $q->where('users.id', $user->getKey());
            })
            ->orderBy('name')
            ->get(['teams.id', 'teams.name']);

        $existing = ContactCard::query()->where('user_id', $user->getKey())->first();

        return Inertia::render('settings/ContactCard', [
            'companies' => $companies,
            'teams' => $teams,
            'contactCard' => $existing ? [
                'slug' => $existing->slug,
                'company_id' => $existing->company_id,
                'team_id' => $existing->team_id,
                'is_dark_mode' => (bool) $existing->is_dark_mode,
            ] : null,
        ]);
    }

    public function store(ContactCardGenerateRequest $request): RedirectResponse
    {
        try {
            $user = $request->user();
            $data = $request->validated();

            // Ensure selected company and team are allowed for this user
            $allowedCompanyIds = $user->companies()->pluck('companies.id');
            abort_unless($allowedCompanyIds->contains($data['company_id']), 403);

            $allowedTeamIds = Team::query()
                ->whereHas('company', function ($q) use ($data) {
                    $q->where('companies.id', $data['company_id']);
                })
                ->whereHas('users', function ($q) use ($user) {
                    $q->where('users.id', $user->getKey());
                })
                ->pluck('teams.id');
            abort_unless($allowedTeamIds->contains($data['team_id']), 403);

            ContactCard::updateOrCreate(
                ['user_id' => $user->getKey()],
                [
                    'company_id' => $data['company_id'],
                    'team_id' => $data['team_id'],
                    'slug' => $data['slug'],
                    'is_dark_mode' => $request->boolean('is_dark_mode'),
                ]
            );

            return $this->successRedirect('contact-card.edit', 'Contact card generated');
        } catch (\Throwable $th) {
            $this->logException($th, 'generate contact card');

            return $this->errorBack('Failed to generate contact card');
        }
    }
}
