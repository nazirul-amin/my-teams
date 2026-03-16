<?php

use App\Models\SlackAccount;
use App\Models\User;
use Illuminate\Support\Facades\Http;

test('authenticated users can start the slack oauth flow', function () {
    config()->set('services.slack.client_id', 'client-id');
    config()->set('services.slack.redirect', 'http://localhost/settings/slack/callback');
    config()->set('services.slack.scopes', ['openid', 'profile', 'email']);

    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('slack.connect'));

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toStartWith('https://slack.com/openid/connect/authorize?');
    expect(session('slack_oauth.state'))->not->toBeEmpty();
    expect(session('slack_oauth.nonce'))->not->toBeEmpty();
});

test('authenticated users can connect their slack account', function () {
    config()->set('services.slack.client_id', 'client-id');
    config()->set('services.slack.client_secret', 'client-secret');
    config()->set('services.slack.redirect', 'http://localhost/settings/slack/callback');

    Http::fake([
        'https://slack.com/api/openid.connect.token' => Http::response([
            'ok' => true,
            'access_token' => 'slack-access-token',
        ]),
        'https://slack.com/api/openid.connect.userInfo' => Http::response([
            'sub' => 'U12345',
            'name' => 'Taylor Slack',
            'email' => 'taylor@example.com',
            'picture' => 'https://example.com/avatar.png',
            'https://slack.com/team_id' => 'T12345',
            'https://slack.com/team_name' => 'My Teams',
        ]),
    ]);

    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->withSession([
            'slack_oauth' => [
                'state' => 'expected-state',
                'nonce' => 'expected-nonce',
            ],
        ])
        ->get(route('slack.callback', [
            'state' => 'expected-state',
            'code' => 'authorization-code',
        ]));

    $response->assertRedirect(route('slack.edit'));
    $response->assertSessionHas('status', 'Slack account connected.');

    $this->assertDatabaseHas('slack_accounts', [
        'user_id' => $user->getKey(),
        'slack_user_id' => 'U12345',
        'slack_team_id' => 'T12345',
        'slack_team_name' => 'My Teams',
        'slack_name' => 'Taylor Slack',
        'slack_email' => 'taylor@example.com',
    ]);
});

test('slack callback requires a valid state value', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->withSession([
            'slack_oauth' => [
                'state' => 'expected-state',
                'nonce' => 'expected-nonce',
            ],
        ])
        ->get(route('slack.callback', [
            'state' => 'wrong-state',
            'code' => 'authorization-code',
        ]));

    $response->assertRedirect(route('slack.edit'));
    $response->assertSessionHas('error', 'Slack sign-in could not be verified. Please try again.');
    expect(SlackAccount::count())->toBe(0);
});

test('authenticated users can disconnect their slack account', function () {
    $user = User::factory()->create();

    $user->slackAccount()->create([
        'slack_user_id' => 'U12345',
        'slack_team_id' => 'T12345',
        'slack_team_name' => 'My Teams',
        'slack_name' => 'Taylor Slack',
        'slack_email' => 'taylor@example.com',
        'slack_avatar' => 'https://example.com/avatar.png',
        'access_token' => 'slack-access-token',
    ]);

    $response = $this
        ->actingAs($user)
        ->delete(route('slack.destroy'));

    $response->assertRedirect(route('slack.edit'));
    $response->assertSessionHas('status', 'Slack account disconnected.');
    expect(SlackAccount::count())->toBe(0);
});
