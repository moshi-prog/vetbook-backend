<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password — VetBook</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            padding: 20px 16px;
            background-color: #FAF6F0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #2E3230;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card {
            width: 100%;
            max-width: 440px;
            background: #FFFFFF;
            border-radius: 24px;
            box-shadow: 0 8px 30px rgba(46, 50, 48, 0.08);
            border: 1px solid #EAE6DE;
            overflow: hidden;
        }
        .header {
            background-color: #4A7C59;
            padding: 28px 24px;
            text-align: center;
            color: #FFFFFF;
        }
        .header-logo {
            font-size: 32px;
            margin-bottom: 4px;
        }
        .header-title {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
        }
        .header-sub {
            margin: 4px 0 0;
            font-size: 12px;
            color: #C8E8D0;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            font-weight: 600;
        }
        .body {
            padding: 28px 24px 32px;
        }
        h2 {
            margin: 0 0 8px;
            font-size: 20px;
            color: #2E3230;
        }
        .subtitle {
            margin: 0 0 24px;
            font-size: 14px;
            color: #5F6460;
            line-height: 1.5;
        }
        .alert {
            padding: 14px 16px;
            border-radius: 12px;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 20px;
        }
        .alert-error {
            background-color: #FDECEA;
            color: #B83230;
            border: 1px solid #F5C6CB;
        }
        .alert-success {
            background-color: #E8F5E9;
            color: #2E7D32;
            border: 1px solid #C8E6C9;
            text-align: center;
            padding: 24px 20px;
        }
        .success-icon {
            font-size: 40px;
            margin-bottom: 12px;
        }
        .field {
            margin-bottom: 18px;
        }
        label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.7px;
            text-transform: uppercase;
            color: #6B6358;
            margin-bottom: 6px;
        }
        input {
            width: 100%;
            height: 46px;
            padding: 0 14px;
            border: 1px solid #DDD6C8;
            border-radius: 12px;
            font-size: 15px;
            color: #2E3230;
            background-color: #FFFFFF;
            outline: none;
            transition: border-color 0.2s;
        }
        input:focus {
            border-color: #4A7C59;
        }
        input[readonly] {
            background-color: #F5F1EA;
            color: #6B6358;
            cursor: not-allowed;
        }
        .btn {
            width: 100%;
            height: 50px;
            border: none;
            border-radius: 999px;
            background-color: #4A7C59;
            color: #FFFFFF;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 6px;
            box-shadow: 0 4px 12px rgba(74, 124, 89, 0.25);
        }
        .btn:hover {
            background-color: #3D684A;
        }
        .footer {
            background-color: #F5F1EA;
            border-top: 1px solid #EAE6DE;
            padding: 16px 24px;
            text-align: center;
            font-size: 12px;
            color: #6B6358;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <div class="header-logo">🐾</div>
            <h1 class="header-title">VetBook</h1>
            <p class="header-sub">BK Petaholic Animal Clinic</p>
        </div>

        <div class="body">
            @if(!empty($success))
                <div class="alert alert-success">
                    <div class="success-icon">✅</div>
                    <h2 style="color: #1B5E20; margin-bottom: 8px;">Password Reset Complete</h2>
                    <p style="margin: 0; font-size: 14px;">{{ $success }}</p>
                </div>
            @elseif(!empty($error))
                <div class="alert alert-error">
                    <strong>Invalid or Expired Link</strong><br>
                    {{ $error }}
                </div>
            @else
                <h2>Set New Password</h2>
                <p class="subtitle">Please enter a new password for your VetBook account.</p>

                @if($errors->any())
                    <div class="alert alert-error">
                        @foreach($errors->all() as $err)
                            <div>{{ $err }}</div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="field">
                        <label for="email">Email Address</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ $email }}"
                            readonly
                            required
                        >
                    </div>

                    <div class="field">
                        <label for="password">New Password</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="At least 6 characters"
                            minlength="6"
                            required
                            autofocus
                        >
                    </div>

                    <div class="field">
                        <label for="password_confirmation">Confirm Password</label>
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            placeholder="Re-enter your new password"
                            minlength="6"
                            required
                        >
                    </div>

                    <button type="submit" class="btn">Set New Password</button>
                </form>
            @endif
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} BK Petaholic Animal Clinic &bull; VetBook
        </div>
    </div>
</body>
</html>

