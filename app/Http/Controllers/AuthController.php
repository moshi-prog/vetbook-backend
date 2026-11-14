<?php

namespace App\Http\Controllers;

use App\Mail\PasswordResetMail;
use App\Mail\VerifyEmailCodeMail;
use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Services\PushNotificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    /**
     * Register a new user and dispatch a 6-digit verification code.
     */
    public function register(Request $request): JsonResponse
    {
        $email = strtolower(trim($request->email ?? ''));

        // Check if an account already exists with this email
        $existingUser = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if ($existingUser) {
            // If the account is ALREADY verified, reject duplicate registration
            if ($existingUser->email_verified_at !== null) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors'  => ['email' => ['This email is already registered and verified. Please sign in instead.']],
                ], 400);
            }

            // If account exists but is UNVERIFIED: validate without unique:users, and update the existing user
            $validator = Validator::make($request->all(), [
                'name'         => 'required|string|max:255',
                'email'        => 'required|string|email|max:255',
                'password'     => 'required|string|min:6|confirmed',
                'phone_number' => 'required|digits:11',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 400);
            }

            $existingUser->update([
                'name'         => trim($request->name),
                'password'     => Hash::make($request->password),
                'phone_number' => $request->phone_number,
            ]);

            $user = $existingUser;
        } else {
            // Fresh new user registration
            $validator = Validator::make($request->all(), [
                'name'         => 'required|string|max:255',
                'email'        => 'required|string|email|max:255|unique:users',
                'password'     => 'required|string|min:6|confirmed',
                'phone_number' => 'required|digits:11',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 400);
            }

            $user = User::create([
                'name'              => trim($request->name),
                'email'             => $email,
                'password'          => Hash::make($request->password),
                'phone_number'      => $request->phone_number,
                'role'              => 'pet_owner',
                'email_verified_at' => null,
            ]);
        }

        // Generate fresh 6-digit code
        $code = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        EmailVerificationCode::where('email', $email)->delete();

        EmailVerificationCode::create([
            'email'      => $email,
            'code'       => $code,
            'expires_at' => now()->addMinutes(15),
        ]);

        \Log::info("Verification code for {$email}: {$code}");

        $mailSent = false;
        if (config('mail.default') !== 'log') {
            try {
                Mail::to($user->email)->send(new VerifyEmailCodeMail($code, $user->name));
                $mailSent = true;
            } catch (\Throwable $e) {
                \Log::error('Failed to send verification email: ' . $e->getMessage());
            }
        }

        $responseData = [
            'message'            => 'Registration successful. Please check your email for your 6-digit verification code.',
            'email'              => $user->email,
            'needs_verification' => true,
        ];

        // Include debug_code in local/debug environments so testing is never blocked
        if (config('app.debug') || app()->environment('local', 'testing')) {
            $responseData['debug_code'] = $code;
        }

        return response()->json($responseData, 201);
    }

    /**
     * Verify the 6-digit email verification code and return a JWT token.
     * POST /api/verify-email
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'code'  => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 400);
        }

        $email = strtolower(trim($request->email));
        $code = trim((string) $request->code);

        $codeRecord = EmailVerificationCode::where('email', $email)
            ->where('code', $code)
            ->where('expires_at', '>=', now())
            ->first();

        $isDemoBypass = config('mail.default') === 'log' && $code === '123456';
        if (!$codeRecord && !$isDemoBypass) {
            return response()->json([
                'message' => 'Invalid or expired verification code.',
            ], 400);
        }

        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if (!$user) {
            return response()->json([
                'message' => 'User account not found.',
            ], 404);
        }

        $user->email_verified_at = now();
        $user->save();

        // Invalidate used code
        EmailVerificationCode::where('email', $email)->delete();

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'message' => 'Email verified successfully. Welcome to VetBook!',
            'token'   => $token,
            'user'    => $user,
        ], 200);
    }

    /**
     * Resend a new 6-digit verification code with a 60-second cooldown rate limit.
     * POST /api/resend-verification-code
     */
    public function resendVerificationCode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 400);
        }

        $email = strtolower(trim($request->email));
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if (!$user) {
            return response()->json([
                'message' => 'User account not found.',
            ], 404);
        }

        if ($user->email_verified_at) {
            return response()->json([
                'message' => 'Your email is already verified. You can log in directly.',
            ], 400);
        }

        // 60-second cooldown rate limit check
        $recentCode = EmailVerificationCode::where('email', $email)
            ->where('created_at', '>=', now()->subSeconds(60))
            ->latest('created_at')
            ->first();

        if ($recentCode) {
            $secondsPassed = now()->diffInSeconds($recentCode->created_at);
            $secondsLeft = max(1, 60 - $secondsPassed);

            return response()->json([
                'message'     => "Please wait {$secondsLeft} second(s) before requesting a new code.",
                'retry_after' => $secondsLeft,
            ], 429);
        }

        // Generate new code and replace old ones
        EmailVerificationCode::where('email', $email)->delete();

        $code = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        EmailVerificationCode::create([
            'email'      => $email,
            'code'       => $code,
            'expires_at' => now()->addMinutes(15),
        ]);

        \Log::info("Verification code for {$email}: {$code}");

        if (config('mail.default') !== 'log') {
            try {
                Mail::to($user->email)->send(new VerifyEmailCodeMail($code, $user->name));
            } catch (\Throwable $e) {
                \Log::error('Failed to resend verification email: ' . $e->getMessage());
            }
        }

        $responseData = [
            'message' => 'A new 6-digit verification code has been sent to your email.',
        ];

        // Include debug_code in local/debug environments so testing is never blocked
        if (config('app.debug') || app()->environment('local', 'testing')) {
            $responseData['debug_code'] = $code;
        }

        return response()->json($responseData, 200);
    }

    /**
     * Authenticate a user and return a JWT token.
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 400);
        }

        $credentials = $request->only('email', 'password');

        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'message' => 'Invalid email or password',
                ], 401);
            }
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'Could not create token. Please try again.',
            ], 500);
        }

        $user = JWTAuth::user();

        // Check if user has verified their email address
        if (!$user->email_verified_at) {
            try {
                JWTAuth::invalidate($token);
            } catch (\Throwable $e) {}

            // Send/ensure an active verification code is available
            $activeCode = EmailVerificationCode::where('email', $user->email)
                ->where('expires_at', '>=', now())
                ->first();

            if (!$activeCode) {
                $code = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
                EmailVerificationCode::create([
                    'email'      => $user->email,
                    'code'       => $code,
                    'expires_at' => now()->addMinutes(15),
                ]);

                try {
                    Mail::to($user->email)->send(new VerifyEmailCodeMail($code, $user->name));
                } catch (\Throwable $e) {
                    \Log::error('Failed to send verification email on login attempt: ' . $e->getMessage());
                }
            }

            return response()->json([
                'message'            => 'Please verify your email address before logging in.',
                'needs_verification' => true,
                'email'              => $user->email,
            ], 403);
        }

        return response()->json([
            'message' => 'Login successful',
            'token'   => $token,
            'user'    => $user,
        ]);
    }

    /**
     * Invalidate the current JWT token (logout).
     */
    public function logout(): JsonResponse
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'Failed to logout. Token may already be invalid.',
            ], 500);
        }

        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }

    /**
     * Return the authenticated user with their pets.
     */
    public function me(): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        return response()->json([
            'user' => $user->load('pets'),
        ]);
    }

    /**
     * Update authenticated user's avatar.
     * Supports both multipart image uploads and base64 data URLs.
     *
     * POST /api/user/avatar
     */
    public function updateAvatar(Request $request): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        [$success, $avatarUrl, $error] = \App\Services\AvatarService::processAvatar($request, 'avatars', 'user_' . $user->id);

        if (!$success) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => ['avatar' => [$error]],
            ], 422);
        }

        $user->avatar_url = $avatarUrl;
        $user->save();

        return response()->json([
            'message'    => 'Avatar updated successfully',
            'avatar_url' => $avatarUrl,
            'user'       => $user->load('pets'),
        ]);
    }

    /**
     * Update the authenticated user's profile information.
     *
     * PUT /api/user/profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = auth()->user() ?? JWTAuth::parseToken()->authenticate();

        $validator = Validator::make($request->all(), [
            'name'         => 'required|string|max:255',
            'phone_number' => 'required|string|size:11|regex:/^09\d{9}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $existingAvatar = $user->avatar_url;

        $user->name = trim($request->name);
        $user->phone_number = trim($request->phone_number);
        if (empty($user->avatar_url) && !empty($existingAvatar)) {
            $user->avatar_url = $existingAvatar;
        }
        $user->save();
        $user->refresh();

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user'    => $user->load('pets'),
        ]);
    }

    /**
     * Change the authenticated user's password.
     *
     * PUT /api/user/password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = auth()->user() ?? JWTAuth::parseToken()->authenticate();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect.',
                'errors'  => [
                    'current_password' => ['Current password is incorrect.'],
                ],
            ], 422);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'message' => 'Password changed successfully.',
        ]);
    }

    /**
     * Public password recovery verifying email and registered 11-digit mobile number.
     *
     * POST /api/reset-password
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email'                 => 'required|string|email|max:255',
            'phone_number'          => 'required|string|size:11|regex:/^09\d{9}$/',
            'password'              => 'required|string|min:6|confirmed',
        ], [
            'phone_number.regex'    => 'Mobile number must be 11 digits starting with 09.',
            'phone_number.size'     => 'Mobile number must be 11 digits starting with 09.',
            'password.confirmed'    => 'Password confirmation does not match.',
            'password.min'          => 'Password must be at least 6 characters.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first() ?: 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $email = mb_strtolower(trim($request->email));
        $phoneNumber = trim($request->phone_number);

        $user = User::whereRaw('LOWER(email) = ?', [$email])
            ->where('phone_number', $phoneNumber)
            ->first();

        if (!$user) {
            return response()->json([
                'message' => 'No account matches the provided email and phone number.',
                'errors'  => [
                    'email' => ['No account matches the provided email and phone number.'],
                ],
            ], 422);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'message' => 'Password has been reset successfully. You can now log in with your new password.',
        ], 200);
    }

    /**
     * Send a password reset link to the user's email.
     * POST /api/forgot-password
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first() ?: 'Please enter a valid email address.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $email = mb_strtolower(trim($request->email));
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if (!$user) {
            return response()->json([
                'message' => 'If this email is registered, a password reset link has been sent.',
            ], 200);
        }

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token'      => Hash::make($token),
                'created_at' => now(),
            ]
        );

        try {
            Mail::to($user->email)->send(new PasswordResetMail($token, $email, $user->name ?? 'Pet Owner'));
        } catch (\Throwable $e) {
            \Log::error('Failed to send password reset email: ' . $e->getMessage());
            return response()->json([
                'message' => 'Unable to send reset email right now. Please try again later.',
            ], 500);
        }

        return response()->json([
            'message' => 'A password reset link has been sent to your email.',
        ], 200);
    }

    /**
     * Show the web password reset form from an email link.
     * GET /password-reset/{token}
     */
    public function showResetForm(Request $request, string $token)
    {
        $email = mb_strtolower(trim((string) $request->query('email', '')));

        $record = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        $isValid = $record
            && Hash::check($token, $record->token)
            && Carbon::parse($record->created_at)->addMinutes(60)->isFuture();

        if (!$isValid) {
            return view('auth.reset_password', [
                'error' => 'Invalid or expired link. Please request a new password reset from the VetBook app.',
            ]);
        }

        return view('auth.reset_password', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    /**
     * Handle the web password reset form submission.
     * POST /password-reset
     */
    public function handleWebPasswordReset(Request $request)
    {
        $request->validate([
            'token'    => 'required|string',
            'email'    => 'required|email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $email = mb_strtolower(trim($request->email));

        $record = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        $isValid = $record
            && Hash::check($request->token, $record->token)
            && Carbon::parse($record->created_at)->addMinutes(60)->isFuture();

        if (!$isValid) {
            return view('auth.reset_password', [
                'error' => 'Invalid or expired link. Please request a new password reset from the VetBook app.',
            ]);
        }

        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
        if (!$user) {
            return view('auth.reset_password', [
                'error' => 'Account not found. Please contact support.',
            ]);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        DB::table('password_reset_tokens')->where('email', $email)->delete();

        return view('auth.reset_password', [
            'success' => 'Your password has been successfully reset! You can now open the VetBook app and sign in.',
        ]);
    }

    /**
     * Store or update Expo push token for the authenticated user.
     */
    public function updatePushToken(Request $request)
    {
        $request->validate([
            'push_token' => 'nullable|string',
        ]);

        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user->push_token = $request->input('push_token');
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Push token updated successfully.',
        ]);
    }

    /**
     * Dispatch an immediate test push notification to the authenticated user's device.
     */
    public function testPushNotification(Request $request): JsonResponse
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (!$user->push_token) {
            return response()->json([
                'status' => 'error',
                'message' => 'No push token registered for your account. Please enable notifications on your device.',
            ], 400);
        }

        $result = PushNotificationService::send(
            $user->push_token,
            'Test Notification 🐾',
            'Push notifications are working! You will receive updates here even when the app is closed.',
            ['type' => 'test_push', 'timestamp' => now()->toIso8601String()]
        );

        return response()->json([
            'status'  => $result['success'] ? 'success' : 'error',
            'message' => $result['success']
                ? 'Push notification dispatched! Check your lockscreen.'
                : ($result['error'] ?? 'Push notification delivery failed.'),
            'response' => $result['response'] ?? null,
        ], $result['success'] ? 200 : 500);
    }

    /**
     * Return VAPID public key for Web Push PWA subscriptions.
     */
    public function getVapidPublicKey(): JsonResponse
    {
        return response()->json([
            'status'     => 'success',
            'public_key' => config('services.vapid.public_key'),
        ]);
    }
}
