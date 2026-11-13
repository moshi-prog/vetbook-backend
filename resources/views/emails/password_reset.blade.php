<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your VetBook Password</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #FAF6F0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #2E3230;
            -webkit-font-smoothing: antialiased;
        }
        .wrapper {
            width: 100%;
            table-layout: fixed;
            background-color: #FAF6F0;
            padding: 40px 0;
        }
        .main-card {
            background-color: #FFFFFF;
            margin: 0 auto;
            width: 100%;
            max-width: 540px;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            border: 1px solid #EAE6DE;
        }
        .header {
            background-color: #4A7C59;
            padding: 32px 30px;
            text-align: center;
        }
        .header-title {
            color: #FFFFFF;
            font-size: 24px;
            font-weight: 700;
            margin: 8px 0 0 0;
            letter-spacing: -0.5px;
        }
        .header-sub {
            color: #C8E8D0;
            font-size: 13px;
            margin: 4px 0 0 0;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }
        .body-content {
            padding: 36px 32px;
        }
        .greeting {
            font-size: 20px;
            font-weight: 700;
            color: #2E3230;
            margin: 0 0 16px 0;
        }
        .text {
            font-size: 15px;
            line-height: 1.6;
            color: #4A4E4A;
            margin: 0 0 24px 0;
        }
        .btn-container {
            text-align: center;
            margin: 30px 0;
        }
        .reset-btn {
            display: inline-block;
            background-color: #4A7C59;
            color: #FFFFFF !important;
            text-decoration: none;
            font-size: 16px;
            font-weight: 700;
            padding: 14px 32px;
            border-radius: 999px;
            box-shadow: 0 4px 12px rgba(74, 124, 89, 0.28);
        }
        .instruction {
            background: #F5F1EA;
            border-radius: 12px;
            padding: 14px 18px;
            font-size: 13px;
            color: #6B6358;
            line-height: 1.5;
            margin-bottom: 24px;
        }
        .link-fallback {
            font-size: 12px;
            color: #8A857A;
            word-break: break-all;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        .footer {
            background-color: #F5F1EA;
            border-top: 1px solid #EAE6DE;
            padding: 24px;
            text-align: center;
            font-size: 12px;
            color: #6B6358;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <table class="main-card" role="presentation" cellpadding="0" cellspacing="0">
            <tr>
                <td class="header">
                    <div style="font-size: 32px;">🐾</div>
                    <h1 class="header-title">VetBook</h1>
                    <p class="header-sub">BK Petaholic Animal Clinic</p>
                </td>
            </tr>
            <tr>
                <td class="body-content">
                    <h2 class="greeting">Hello, {{ $name }}!</h2>
                    <p class="text">
                        We received a request to reset the password for your VetBook account. Click the button below to set a new password:
                    </p>

                    <div class="btn-container">
                        <a href="{{ url('/password-reset/' . $token . '?email=' . urlencode($email)) }}" class="reset-btn">
                            Reset Password
                        </a>
                    </div>

                    <div class="instruction">
                        <strong>⏱️ Expires in 60 minutes:</strong> This password reset link is valid for 60 minutes and can only be used once.<br>
                        If you did not request a password reset, you can safely ignore this email—your password will remain unchanged.
                    </div>

                    <p class="link-fallback">
                        If the button above doesn't work, copy and paste this link into your browser:<br>
                        <a href="{{ url('/password-reset/' . $token . '?email=' . urlencode($email)) }}" style="color: #4A7C59;">
                            {{ url('/password-reset/' . $token . '?email=' . urlencode($email)) }}
                        </a>
                    </p>
                </td>
            </tr>
            <tr>
                <td class="footer">
                    <strong>BK Petaholic Animal Clinic</strong><br>
                    Mabini Branch &bull; Batangas City Branch<br>
                    &copy; {{ date('Y') }} VetBook. All rights reserved.
                </td>
            </tr>
        </table>
    </div>
</body>
</html>

