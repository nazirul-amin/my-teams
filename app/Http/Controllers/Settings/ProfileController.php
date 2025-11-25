<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            'profile' => optional($request->user()->profile)->only([
                'bio', 'position', 'phone', 'website', 'linkedin', 'twitter', 'facebook', 'instagram', 'photo', 'cover_photo',
            ]),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        // Upsert profile fields
        $profileData = array_intersect_key($validated, array_flip([
            'bio', 'position', 'phone', 'website', 'linkedin', 'twitter', 'facebook', 'instagram', 'cover_photo',
        ]));

        // Load existing profile for file management
        $existingProfile = $user->profile()->first();

        // Handle uploaded profile photo (replace)
        if ($request->hasFile('photo')) {
            if ($existingProfile && ! empty($existingProfile->photo)) {
                try {
                    Storage::disk('public')->delete($existingProfile->photo);
                } catch (\Throwable $e) {
                }
            }
            $path = $request->file('photo')->store('profiles/photos', 'public');
            $profileData['photo'] = $path;
        } elseif ((bool) $request->boolean('photo_removed')) {
            // Optional flag to explicitly remove existing photo
            if ($existingProfile && ! empty($existingProfile->photo)) {
                try {
                    Storage::disk('public')->delete($existingProfile->photo);
                } catch (\Throwable $e) {
                }
            }
            $profileData['photo'] = null;
        }
        if (! empty($profileData)) {
            $user->profile()->updateOrCreate([], $profileData);
        }

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
