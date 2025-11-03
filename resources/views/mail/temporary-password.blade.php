<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Temporary Password</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.5; color: #111827;">
    <h2 style="margin-bottom: 16px;">Welcome, {{ $name }}</h2>

    <p>Your account has been created. Use the temporary password below to sign in and change your password immediately:</p>

    <p style="font-size: 16px; font-weight: bold; background: #f3f4f6; padding: 8px 12px; display: inline-block; border-radius: 6px;">
        {{ $temporaryPassword }}
    </p>

    <p>Login here: <a href="{{ $loginUrl }}" target="_blank" rel="noopener">{{ $loginUrl }}</a></p>

    <p style="margin-top: 24px; color: #6b7280;">If you did not expect this message, you can safely ignore it.</p>
</body>
</html>
