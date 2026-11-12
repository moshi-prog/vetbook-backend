<?php

namespace App\Http\Controllers;

use App\Mail\VerifyEmailCodeMail;
use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    /**
     * Register a new user and dispatch a 6-digit verification code.
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|string|email|max:255|unique:users',
            'password'              => 'required|string|min:6|confirmed',
            'phone_number'          => 'required|digits:11',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 400);
        }

        $email = strtolower(trim($request->email));

        $user = User::create([
            'name'              => trim($request->name),
            'email'             => $email,
            'password'          => Hash::make($request->password),
            'phone_number'      => $request->phone_number,
            'role'              => 'pet_owner',
            'email_verified_at' => null,
        ]);

        // Generate a 6-digit verification code
        $code = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        EmailVerificationCode::where('email', $email)->delete();

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
                \Log::error('Failed to send verification email: ' . $e->getMessage());
            }
        }

        return response()->json([
            'message'            => 'Registration successful. Please check your email for your 6-digit verification code.',
            'email'              => $user->email,
            'needs_verification' => true,
        ], 201);
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

        return response()->json([
            'message' => 'A new 6-digit verification code has been sent to your email.',
        ], 200);
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
     *
     * POST /api/user/avatar
     */
    public function updateAvatar(Request $request): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,webp|max:4096',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $avatarUrl = url('storage/' . $path);

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

        $user->name = $request->name;
        $user->phone_number = $request->phone_number;
        $user->save();

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
}
