<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PetController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\MedicalRecordController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ClinicController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned the "api" middleware group, making them stateless and
| prefixed with /api/ automatically.
|
*/

// -------------------------------------------------------------------------
// Public routes — no authentication required
// -------------------------------------------------------------------------
Route::post('/register',                [AuthController::class, 'register']);
Route::post('/login',                   [AuthController::class, 'login']);
Route::post('/verify-email',            [AuthController::class, 'verifyEmail']);
Route::post('/resend-verification-code',[AuthController::class, 'resendVerificationCode']);
Route::post('/reset-password',          [AuthController::class, 'resetPassword']);
Route::get('/branches',        [ClinicController::class, 'getBranches']);
Route::get('/clinic',          [ClinicController::class, 'getClinicInfo']);

// -------------------------------------------------------------------------
// Protected routes — require a valid JWT via auth:api middleware
// -------------------------------------------------------------------------
Route::middleware('auth:api')->group(function () {

    // Auth
    Route::post('/logout',        [AuthController::class, 'logout']);
    Route::get('/me',             [AuthController::class, 'me']);
    Route::put('/user/profile',   [AuthController::class, 'updateProfile']);
    Route::put('/user/password',  [AuthController::class, 'changePassword']);
    Route::post('/user/avatar',   [AuthController::class, 'updateAvatar']);
    Route::post('/user/push-token', [AuthController::class, 'updatePushToken']);
    Route::post('/user/test-push',   [AuthController::class, 'testPushNotification']);

    // -----------------------------------------------------------------------
    // Pet management
    // -----------------------------------------------------------------------
    Route::get('/user/pets',         [PetController::class, 'index']);
    Route::post('/pets',             [PetController::class, 'store']);
    Route::get('/pets/{id}',         [PetController::class, 'show']);
    Route::put('/pets/{id}',         [PetController::class, 'update']);
    Route::delete('/pets/{id}',      [PetController::class, 'destroy']);
    Route::post('/pets/{id}/avatar', [PetController::class, 'updateAvatar']);

    // -----------------------------------------------------------------------
    // Appointments
    // -----------------------------------------------------------------------
    Route::get('/user/appointments',         [AppointmentController::class, 'index']);
    Route::post('/appointments',             [AppointmentController::class, 'store']);
    Route::get('/appointments/{id}',         [AppointmentController::class, 'show']);
    Route::put('/appointments/{id}/reschedule', [AppointmentController::class, 'reschedule']);
    Route::put('/appointments/{id}/cancel',  [AppointmentController::class, 'cancel']);
    Route::put('/appointments/{id}/status',  [AppointmentController::class, 'updateStatus']);

    // -----------------------------------------------------------------------
    // Medical Records
    // -----------------------------------------------------------------------
    Route::get('/pets/{petId}/medical-records',  [MedicalRecordController::class, 'indexByPet']);
    Route::post('/pets/{petId}/medical-records', [MedicalRecordController::class, 'store']);
    Route::get('/medical-records/{id}',          [MedicalRecordController::class, 'show']);
    Route::put('/medical-records/{id}',          [MedicalRecordController::class, 'update']);

    // -----------------------------------------------------------------------
    // Chatbot
    // -----------------------------------------------------------------------
    Route::post('/chatbot/message', [\App\Http\Controllers\ChatbotController::class, 'sendMessage']);

    // -----------------------------------------------------------------------
    // Notifications
    // -----------------------------------------------------------------------
    Route::get('/notifications',              [NotificationController::class, 'index']);
    Route::put('/notifications/{id}/read',    [NotificationController::class, 'markAsRead']);
    Route::put('/notifications/read-all',     [NotificationController::class, 'markAllAsRead']);
});
