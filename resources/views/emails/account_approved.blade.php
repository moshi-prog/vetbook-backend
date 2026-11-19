<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VetBook — Account Approved</title>
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
        .approved-box {
            background: #E8F5E9;
            border: 1.5px solid #A5D6A7;
            border-radius: 16px;
            padding: 22px;
            text-align: center;
            margin: 24px 0;
        }
        .approved-badge {
            display: inline-block;
            background: #C8E8D0;
            color: #2A6038;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            padding: 5px 12px;
            border-radius: 999px;
            margin-bottom: 10px;
        }
        .approved-title {
            font-size: 18px;
            font-weight: 700;
            color: #1B5E20;
            margin: 0 0 6px 0;
        }
        .approved-desc {
            font-size: 13px;
            color: #2A6038;
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
                    <h2 class="greeting">Welcome to VetBook, {{ $name }}!</h2>
                    <p class="text">
                        Your VetBook pet owner account has been verified and approved by BK Petaholic Animal Clinic staff. You can now open the VetBook app and sign in!
                    </p>

                    <div class="approved-box">
                        <div class="approved-badge">Account Approved</div>
                        <div class="approved-title">Ready to Sign In</div>
                        <p class="approved-desc">
                            Add your pet profiles, book clinic appointments, and view medical records &amp; reminders anytime.
                        </p>
                    </div>

                    <div class="instruction">
                        <strong>Getting Started:</strong><br>
                        Open the VetBook mobile app or web portal and log in with your registered email address and password.
                    </div>

                    <p class="text" style="font-size: 13px; color: #8A857A; margin-bottom: 0;">
                        Thank you for trusting BK Petaholic Animal Clinic with your pet's care.
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
