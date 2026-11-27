<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VetBook — Email Verified, Awaiting Clinic Approval</title>
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
        .status-box {
            background: #FFF8E6;
            border: 1.5px solid #F3D68D;
            border-radius: 16px;
            padding: 20px 22px;
            text-align: center;
            margin: 24px 0;
        }
        .status-badge {
            display: inline-block;
            background: #F8E0A8;
            color: #705C30;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            padding: 5px 12px;
            border-radius: 999px;
            margin-bottom: 10px;
        }
        .status-title {
            font-size: 17px;
            font-weight: 700;
            color: #2E3230;
            margin: 0 0 6px 0;
        }
        .status-desc {
            font-size: 13px;
            color: #6B6358;
            line-height: 1.5;
            margin: 0;
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
                        Great news — your email address has been verified! Your VetBook pet owner registration is now queued for verification and approval by BK Petaholic Animal Clinic staff.
                    </p>

                    <div class="status-box">
                        <div class="status-badge">Awaiting Clinic Approval</div>
                        <div class="status-title">Account Review in Progress</div>
                        <p class="status-desc">
                            Our clinic staff review new registrations before granting sign-in access to keep patient records accurate and secure.
                        </p>
                    </div>

                    <div class="instruction">
                        <strong>What happens next?</strong><br>
                        You do not need to take any further action right now. We will send another email as soon as your account has been approved by our clinic staff.
                    </div>

                    <p class="text" style="font-size: 13px; color: #8A857A; margin-bottom: 0;">
                        Questions? Contact BK Petaholic Animal Clinic (Mabini Branch) for assistance.
                    </p>
                </td>
            </tr>
            <tr>
                <td class="footer">
                    <strong>BK Petaholic Animal Clinic</strong><br>
                    Mabini Branch<br>
                    &copy; {{ date('Y') }} VetBook. All rights reserved.
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
