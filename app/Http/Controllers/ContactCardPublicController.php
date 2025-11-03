<?php

namespace App\Http\Controllers;

use App\Models\ContactCard;
use Inertia\Inertia;

class ContactCardPublicController extends Controller
{
    public function show(string $slug)
    {
        $card = ContactCard::query()
            ->with([
                'user:id,name,email',
                'user.profile:id,user_id,bio,position,phone,website,linkedin,twitter,facebook,instagram,photo,cover_photo',
                'company:id,name,address,city,state,country,website,phone,email,cover_photo',
                'team:id,name',
            ])
            ->where('slug', $slug)
            ->firstOrFail();

        return Inertia::render('contact-card/Show', [
            'user' => $card->user,
            'profile' => optional($card->user->profile),
            'company' => $card->company,
            'team' => $card->team,
            'slug' => $card->slug,
        ]);
    }
}
