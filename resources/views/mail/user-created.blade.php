<div>
    <p>Hello {{ $user->name }},</p>
    <p>Your account has been created.</p>
    <p>Email: {{ $user->email }}</p>
    <p>Temporary Password: <strong>{{ $tempPassword }}</strong></p>
    <p>Please log in and change your password immediately.</p>
</div>
