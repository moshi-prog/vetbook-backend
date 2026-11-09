<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VetBook Admin - Login</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Literata:ital,opsz,wght@0,7..72,600;0,7..72,700;1,7..72,600&family=Nunito+Sans:ital,opsz,wght@0,6..12,400;0,6..12,600;0,6..12,700;1,6..12,400&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        body {
            background-color: #FAF6F0;
            font-family: 'Nunito Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin: 0;
            color: #4A453E;
        }

        .login-card {
            background: #FFFFFF;
            width: 100%;
            max-width: 420px;
            border-radius: 20px;
            box-shadow: 0 12px 36px rgba(74, 124, 89, 0.08), 0 4px 12px rgba(0, 0, 0, 0.04);
            overflow: hidden;
            border: 1px solid rgba(220, 215, 205, 0.6);
        }

        .decorative-bar {
            height: 6px;
            background: linear-gradient(90deg, #4A7C59 0%, #78A886 100%);
            width: 100%;
        }

        .login-body {
            padding: 36px 32px 32px;
        }

        .logo-box {
            width: 56px;
            height: 56px;
            background-color: #F0E8DB;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            margin-bottom: 16px;
        }

        .logo-box i {
            color: #4A7C59;
        }

        .brand-title {
            font-family: 'Literata', serif;
            font-size: 18px;
            font-weight: 700;
            color: #4A7C59;
            margin-bottom: 4px;
        }

        .brand-subtitle {
            font-family: 'Nunito Sans', sans-serif;
            font-size: 14px;
            color: #6B6358;
            margin-bottom: 24px;
        }

        .form-label-custom {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: #6B6358;
            text-transform: uppercase;
            margin-bottom: 8px;
            display: block;
        }

        .input-group-custom {
            position: relative;
            background-color: #F5F1EA;
            border: 1px solid rgba(196, 200, 188, 0.6);
            border-radius: 24px;
            display: flex;
            align-items: center;
            padding: 2px 16px;
            transition: all 0.2s ease-in-out;
        }

        .input-group-custom:focus-within {
            border-color: #4A7C59;
            box-shadow: 0 0 0 3px rgba(74, 124, 89, 0.15);
            background-color: #FFFFFF;
        }

        .input-group-custom .input-icon {
            color: #8C857B;
            font-size: 14px;
            margin-right: 12px;
        }

        .input-group-custom input {
            border: none;
            background: transparent;
            width: 100%;
            padding: 10px 0;
            font-size: 14px;
            color: #333333;
            outline: none;
        }

        .input-group-custom input::placeholder {
            color: #A0988D;
            font-size: 14px;
        }

        .toggle-password {
            background: none;
            border: none;
            color: #8C857B;
            cursor: pointer;
            padding: 0 4px;
            font-size: 14px;
            transition: color 0.15s;
        }

        .toggle-password:hover {
            color: #4A7C59;
        }

        .form-check-input:checked {
            background-color: #4A7C59;
            border-color: #4A7C59;
        }

        .form-check-label {
            font-size: 13px;
            color: #6B6358;
        }

        .forgot-link {
            font-size: 13px;
            color: #4A7C59;
            text-decoration: none;
            font-weight: 600;
        }

        .forgot-link:hover {
            text-decoration: underline;
            color: #385E43;
        }

        .btn-submit {
            background-color: #4A7C59;
            border-color: #4A7C59;
            color: #FFFFFF;
            font-weight: 700;
            font-size: 15px;
            border-radius: 24px;
            padding: 12px;
            width: 100%;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(74, 124, 89, 0.25);
        }

        .btn-submit:hover {
            background-color: #3D6649;
            border-color: #3D6649;
            color: #FFFFFF;
            box-shadow: 0 6px 16px rgba(74, 124, 89, 0.35);
        }

        .btn-submit:active {
            transform: translateY(1px);
        }

        .footer-notice {
            font-size: 12px;
            color: #8C8275;
            text-align: center;
            margin-top: 24px;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .alert-custom-error {
            background-color: #FDF2F2;
            color: #9B1C1C;
            border: 1px solid #F8B4B4;
            border-radius: 12px;
            font-size: 13px;
            padding: 12px 14px;
        }

        .alert-custom-success {
            background-color: #F3FAF7;
            color: #0E6245;
            border: 1px solid #84E1BC;
            border-radius: 12px;
            font-size: 13px;
            padding: 12px 14px;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="decorative-bar"></div>
    <div class="login-body">
        <div class="text-center">
            <div class="logo-box">
                <i class="fa-solid fa-leaf" style="font-size: 16px;"></i>
                <i class="fa-solid fa-paw" style="font-size: 18px;"></i>
            </div>
            <div class="brand-title">VetBook Admin</div>
            <div class="brand-subtitle">Sign in to manage the clinic</div>
        </div>

        <?php if(session('error')): ?>
            <div class="alert alert-custom-error d-flex align-items-center mb-3" role="alert">
                <i class="fa-solid fa-circle-exclamation me-2 flex-shrink-0"></i>
                <div><?php echo e(session('error')); ?></div>
            </div>
        <?php endif; ?>

        <?php if($errors->any()): ?>
            <div class="alert alert-custom-error mb-3" role="alert">
                <div class="d-flex align-items-center mb-1">
                    <i class="fa-solid fa-circle-exclamation me-2 flex-shrink-0"></i>
                    <strong>Please check your input:</strong>
                </div>
                <ul class="mb-0 ps-4">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if(session('success')): ?>
            <div class="alert alert-custom-success d-flex align-items-center mb-3" role="alert">
                <i class="fa-solid fa-circle-check me-2 flex-shrink-0"></i>
                <div><?php echo e(session('success')); ?></div>
            </div>
        <?php endif; ?>

        <form action="<?php echo e(route('admin.login.post')); ?>" method="POST" novalidate>
            <?php echo csrf_field(); ?>

            <!-- Email Address -->
            <div class="mb-3">
                <label for="email" class="form-label-custom">EMAIL ADDRESS</label>
                <div class="input-group-custom">
                    <span class="input-icon"><i class="fa-regular fa-envelope"></i></span>
                    <input type="email" id="email" name="email" value="<?php echo e(old('email')); ?>" placeholder="admin@vetbook.com" required autofocus>
                </div>
            </div>

            <!-- Password -->
            <div class="mb-3">
                <label for="password" class="form-label-custom">PASSWORD</label>
                <div class="input-group-custom">
                    <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                    <button type="button" class="toggle-password" id="togglePasswordBtn" aria-label="Toggle password visibility">
                        <i class="fa-regular fa-eye" id="togglePasswordIcon"></i>
                    </button>
                </div>
            </div>

            <!-- Remember me & Forgot password -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember" <?php echo e(old('remember') ? 'checked' : ''); ?>>
                    <label class="form-check-label" for="remember">
                        Remember me
                    </label>
                </div>
                <a href="#" class="forgot-link" onclick="event.preventDefault(); alert('Please contact the system administrator to reset your password.');">Forgot password?</a>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-submit">
                Sign In
            </button>
        </form>

        <!-- Footer Notice -->
        <p class="footer-notice">
            <i class="fa-solid fa-shield-halved"></i>
            <span>Only authorized clinic staff</span>
        </p>
    </div>
</div>

<script>
    const toggleBtn = document.getElementById('togglePasswordBtn');
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('togglePasswordIcon');

    if (toggleBtn && passwordInput && toggleIcon) {
        toggleBtn.addEventListener('click', function () {
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
            toggleIcon.classList.toggle('fa-eye', !isPassword);
            toggleIcon.classList.toggle('fa-eye-slash', isPassword);
        });
    }
</script>
</body>
</html>
<?php /**PATH C:\Users\Moshi\vetbook-project\backend\resources\views/admin/auth/login.blade.php ENDPATH**/ ?>