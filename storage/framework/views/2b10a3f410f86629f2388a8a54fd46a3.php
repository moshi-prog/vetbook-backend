<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VetBook Verification Code</title>
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
        .code-container {
            background: #E8F5E9;
            border: 2px dashed rgba(74, 124, 89, 0.4);
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            margin: 28px 0;
        }
        .code-label {
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 700;
            color: #2E7D32;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }
        .code-digits {
            font-family: 'Courier New', Courier, monospace;
            font-size: 38px;
            font-weight: 800;
            letter-spacing: 10px;
            color: #1B5E20;
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
                    <p class="header-sub">BK Petaholic Veterinary Clinic</p>
                </td>
            </tr>
            <tr>
                <td class="body-content">
                    <h2 class="greeting">Hello, <?php echo e($name); ?>!</h2>
                    <p class="text">
                        Thank you for registering with VetBook. To activate your pet owner account and protect your profile, please verify your email address using the 6-digit code below:
                    </p>

                    <div class="code-container">
                        <div class="code-label">Your Verification Code</div>
                        <div class="code-digits"><?php echo e($code); ?></div>
                    </div>

                    <div class="instruction">
                        <strong>⏱️ Expires in 15 minutes:</strong> Enter this 6-digit code in the VetBook mobile app to activate your account.<br>
                        If you did not attempt to register on VetBook, you can safely disregard this email.
                    </div>

                    <p class="text" style="font-size: 13px; color: #8A857A; margin-bottom: 0;">
                        Need help? Visit any BK Petaholic Clinic branch or reply to this email for assistance.
                    </p>
                </td>
            </tr>
            <tr>
                <td class="footer">
                    <strong>BK Petaholic Veterinary Clinic</strong><br>
                    Mabini Branch &bull; Batangas City Branch<br>
                    &copy; <?php echo e(date('Y')); ?> VetBook. All rights reserved.
                </td>
            </tr>
        </table>
    </div>
</body>
</html>

<?php /**PATH C:\Users\Moshi\vetbook-project\backend\resources\views/emails/verify_code.blade.php ENDPATH**/ ?>