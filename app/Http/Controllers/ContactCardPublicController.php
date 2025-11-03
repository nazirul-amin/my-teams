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
                'user.profile:id,user_id,bio,position,phone,website,linkedin,twitter,facebook,instagram,photo',
                'company:id,name,address,city,state,country,website,phone,email,bg_light,bg_dark,logo',
                'team:id,name,logo',
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
