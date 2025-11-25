<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Welcome to {{ $appName }}</title>
</head>

<body
    style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
        style="background-color: #f3f4f6; padding: 24px 0;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                    style="max-width: 600px; background-color: white; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);">
                    <tr>
                        <td
                            style="padding: 16px 16px 0; text-align: center; background: radial-gradient(circle at top, #fff6ea, #fde3c3);">
                            <!-- Illustration -->
                            <div style="margin-bottom: 8px;">
                                <img src="{{ asset('assets/images/undraw_working-together_r43a.png') }}" alt=""
                                    style="width: 400px;">
                            </div>

                            <h1 style="margin: 12px 0 4px; font-size: 20px; line-height: 1.3; color: #111827;">
                                Welcome to {{ $appName }}
                            </h1>
                            <p style="margin: 0 0 16px; font-size: 14px; color: #4b5563;">
                                Your account has been created successfully.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 24px 24px 8px;">
                            <p style="margin: 0 0 12px; font-size: 14px; color: #111827;">
                                Hello <strong>{{ $user->name }}</strong>,
                            </p>
                            <p style="margin: 0 0 16px; font-size: 14px; color: #4b5563;">
                                We've created a new {{ $appName }} account for you. Here are your
                                login details
                            </p>

                            <table cellpadding="0" cellspacing="0" role="presentation"
                                style="width: 100%; margin: 0 0 16px; font-size: 14px; color: #111827; border-collapse: collapse;">
                                <tr>
                                    <td style="padding: 6px 0; width: 200px; color: #6b7280;">
                                        Email
                                    </td>
                                    <td style="padding: 6px 0; width: 10px; color: #6b7280;">
                                        :
                                    </td>
                                    <td style="padding: 6px 0;">
                                        <strong>{{ $user->email }}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 6px 0; width: 200px; color: #6b7280;">
                                        Temporary password
                                    </td>
                                    <td style="padding: 6px 0; width: 10px; color: #6b7280;">
                                        :
                                    </td>
                                    <td style="padding: 6px 0;">
                                        <strong>{{ $tempPassword }}</strong>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0 0 16px; font-size: 14px; color: #4b5563;">
                                For security, please log in as soon as possible and change this
                                temporary password to one that only you know.
                            </p>

                            <p style="margin: 0 0 24px; text-align: center;">
                                <a href="{{ route('login') }}"
                                    style="display: inline-block; padding: 10px 18px; border-radius: 9999px; background: linear-gradient(135deg, #ee5b71, #f38456); color: white; font-size: 14px; font-weight: 600; text-decoration: none;">
                                    Log in to {{ $appName }}
                                </a>
                            </p>

                            <p style="margin: 0 0 24px; font-size: 14px; color: #4b5563;">
                                If you didn't expect this email, you can ignore it.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 16px 24px 24px; border-top: 1px solid #e5e7eb; text-align: center;">
                            <p style="margin: 0 0 4px; font-size: 12px; color: #9ca3af;">
                                &copy; {{ date('Y') }} {{ $appName }}. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>