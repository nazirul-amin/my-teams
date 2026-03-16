<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\SlackAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SlackController extends Controller
{
    public function edit(Request $request): Response
    {
        $slackAccount = $request->user()?->slackAccount;

        return Inertia::render('settings/Slack', [
            'status' => $request->session()->get('status'),
            'error' => $request->session()->get('error'),
            'slackAccount' => $slackAccount ? [
                'slack_user_id' => $slackAccount->slack_user_id,
                'slack_team_id' => $slackAccount->slack_team_id,
                'slack_team_name' => $slackAccount->slack_team_name,
                'slack_name' => $slackAccount->slack_name,
                'slack_email' => $slackAccount->slack_email,
                'slack_avatar' => $slackAccount->slack_avatar,
                'connected_at' => optional($slackAccount->updated_at)?->toIso8601String(),
            ] : null,
        ]);
    }

    public function connect(Request $request): RedirectResponse
    {
        $clientId = config('services.slack.client_id');
        $redirectUri = config('services.slack.redirect');
        $scopes = collect(config('services.slack.scopes', []))
            ->filter()
            ->implode(' ');

        if (! $clientId || ! $redirectUri || ! $scopes) {
            return to_route('slack.edit')->with('error', 'Slack sign-in is not configured yet.');
        }

        $state = Str::random(40);
        $nonce = Str::random(40);

        $request->session()->put('slack_oauth', [
            'state' => $state,
            'nonce' => $nonce,
        ]);

        $authorizeUrl = 'https://slack.com/openid/connect/authorize?'.http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'scope' => $scopes,
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'nonce' => $nonce,
        ]);

        return redirect()->away($authorizeUrl);
    }

    public function callback(Request $request): RedirectResponse
    {
        $oauthSession = $request->session()->pull('slack_oauth');
        $expectedState = data_get($oauthSession, 'state');

        if (! $expectedState || ! hash_equals($expectedState, (string) $request->string('state'))) {
            return to_route('slack.edit')->with('error', 'Slack sign-in could not be verified. Please try again.');
        }

        if ($request->filled('error')) {
            return to_route('slack.edit')->with('error', 'Slack sign-in was cancelled.');
        }

        $code = (string) $request->string('code');

        if ($code === '') {
            return to_route('slack.edit')->with('error', 'Slack did not return an authorization code.');
        }

        $clientId = config('services.slack.client_id');
        $clientSecret = config('services.slack.client_secret');
        $redirectUri = config('services.slack.redirect');

        if (! $clientId || ! $clientSecret || ! $redirectUri) {
            return to_route('slack.edit')->with('error', 'Slack sign-in is not configured yet.');
        }

        try {
            $tokenResponse = Http::asForm()
                ->post('https://slack.com/api/openid.connect.token', [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'redirect_uri' => $redirectUri,
                ])
                ->throw()
                ->json();

            if (! data_get($tokenResponse, 'ok') || ! data_get($tokenResponse, 'access_token')) {
                return to_route('slack.edit')->with('error', 'Slack sign-in failed while exchanging the authorization code.');
            }

            $accessToken = (string) data_get($tokenResponse, 'access_token');

            $userInfo = Http::withToken($accessToken)
                ->get('https://slack.com/api/openid.connect.userInfo')
                ->throw()
                ->json();

            if (! data_get($userInfo, 'sub')) {
                return to_route('slack.edit')->with('error', 'Slack sign-in failed while loading your Slack profile.');
            }
        } catch (\Throwable $e) {
            report($e);

            return to_route('slack.edit')->with('error', 'Slack sign-in failed. Please try again.');
        }

        $user = $request->user();
        $slackUserId = (string) data_get($userInfo, 'sub');

        $existingAccount = SlackAccount::query()
            ->where('slack_user_id', $slackUserId)
            ->first();

        if ($existingAccount && $existingAccount->user_id !== $user->getKey()) {
            return to_route('slack.edit')->with('error', 'That Slack account is already connected to another user.');
        }

        $teamId = (string) ($userInfo['https://slack.com/team_id'] ?? '');
        $teamName = (string) ($userInfo['https://slack.com/team_name'] ?? '');

        $user->slackAccount()->updateOrCreate([], [
            'slack_user_id' => $slackUserId,
            'slack_team_id' => $teamId !== '' ? $teamId : null,
            'slack_team_name' => $teamName !== '' ? $teamName : null,
            'slack_name' => (string) data_get($userInfo, 'name') ?: null,
            'slack_email' => (string) data_get($userInfo, 'email') ?: null,
            'slack_avatar' => (string) data_get($userInfo, 'picture') ?: null,
            'access_token' => $accessToken,
        ]);

        return to_route('slack.edit')->with('status', 'Slack account connected.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->user()?->slackAccount()?->delete();

        return to_route('slack.edit')->with('status', 'Slack account disconnected.');
    }
}
