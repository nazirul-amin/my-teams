<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Authorize Application</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f6f7fb; margin: 0; padding: 24px; color: #1f2937; }
        .card { max-width: 640px; margin: 40px auto; background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 24px; }
        h1 { margin-top: 0; font-size: 22px; }
        .muted { color: #6b7280; font-size: 14px; }
        ul { margin-top: 10px; margin-bottom: 24px; }
        .actions { display: flex; gap: 12px; }
        button { border: 0; border-radius: 6px; padding: 10px 16px; font-size: 14px; cursor: pointer; }
        .approve { background: #111827; color: #fff; }
        .deny { background: #e5e7eb; color: #111827; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Authorize {{ $client->name }}</h1>
        <p class="muted">{{ $client->name }} is requesting access to your account.</p>

        @if (count($scopes) > 0)
            <p><strong>Requested permissions:</strong></p>
            <ul>
                @foreach ($scopes as $scope)
                    <li>{{ $scope->description ?: $scope->id }}</li>
                @endforeach
            </ul>
        @else
            <p class="muted">This app did not request additional scopes.</p>
        @endif

        <div class="actions">
            <form method="post" action="{{ route('passport.authorizations.approve') }}">
                @csrf
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <button type="submit" class="approve">Authorize</button>
            </form>

            <form method="post" action="{{ route('passport.authorizations.deny') }}">
                @csrf
                @method('DELETE')
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <button type="submit" class="deny">Cancel</button>
            </form>
        </div>
    </div>
</body>
</html>
