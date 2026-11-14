<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminNotificationController;
use App\Http\Controllers\Admin\AppointmentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MedicalRecordController;
use App\Http\Controllers\Admin\PetOwnerController;
use App\Http\Controllers\Admin\PetController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SmsController;
use App\Http\Controllers\Admin\VeterinarianController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Password reset web routes (for email links)
Route::get('/password-reset/{token}', [AuthController::class, 'showResetForm'])->name('password.reset');
Route::post('/password-reset', [AuthController::class, 'handleWebPasswordReset'])->name('password.update');

// Admin authentication routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.post');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
});

// Admin protected routes
Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    // Appointments
    Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::get('/appointments/latest-id', [AppointmentController::class, 'latestId'])->name('appointments.latest-id');
    Route::get('/appointments/export', [AppointmentController::class, 'export'])->name('appointments.export');
    Route::match(['post', 'delete'], '/appointments/bulk-delete', [AppointmentController::class, 'bulkDestroy'])->name('appointments.bulk-delete');
    Route::get('/appointments/{id}', [AppointmentController::class, 'show'])->name('appointments.show');
    Route::delete('/appointments/{id}', [AppointmentController::class, 'destroy'])->name('appointments.destroy');
    Route::post('/appointments/{id}/approve', [AppointmentController::class, 'approve'])->name('appointments.approve');
    Route::post('/appointments/{id}/complete', [AppointmentController::class, 'complete'])->name('appointments.complete');
    Route::post('/appointments/{id}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');
    Route::post('/appointments/{id}/reschedule', [AppointmentController::class, 'reschedule'])->name('appointments.reschedule');
    Route::post('/appointments/{id}/assign-vet', [AppointmentController::class, 'assignVeterinarian'])->name('appointments.assign-vet');

    // Pets
    Route::get('/pets', [PetController::class, 'index'])->name('pets.index');
    Route::get('/pets/export', [PetController::class, 'export'])->name('pets.export');
    Route::match(['post', 'delete'], '/pets/bulk-delete', [PetController::class, 'bulkDestroy'])->name('pets.bulk-delete');
    Route::get('/pets/{id}', [PetController::class, 'show'])->name('pets.show');
    Route::post('/pets/{id}/health-status', [PetController::class, 'updateHealthStatus'])->name('pets.update-health-status');
    Route::delete('/pets/{id}', [PetController::class, 'destroy'])->name('pets.destroy');

    // Medical records (static paths must be declared before /{id})
    Route::get('/medical-records', [MedicalRecordController::class, 'index'])->name('medical-records.index');
    Route::get('/medical-records/export', [MedicalRecordController::class, 'export'])->name('medical-records.export');
    Route::match(['post', 'delete'], '/medical-records/bulk-delete', [MedicalRecordController::class, 'bulkDestroy'])->name('medical-records.bulk-delete');
    Route::get('/medical-records/create', [MedicalRecordController::class, 'create'])->name('medical-records.create');
    Route::post('/medical-records', [MedicalRecordController::class, 'store'])->name('medical-records.store');
    Route::get('/medical-records/pet/{petId}', [MedicalRecordController::class, 'petRecords'])->name('medical-records.pet');
    Route::get('/medical-records/{id}', [MedicalRecordController::class, 'show'])->name('medical-records.show');
    Route::get('/medical-records/{id}/edit', [MedicalRecordController::class, 'edit'])->name('medical-records.edit');
    Route::put('/medical-records/{id}', [MedicalRecordController::class, 'update'])->name('medical-records.update');
    Route::delete('/medical-records/{id}', [MedicalRecordController::class, 'destroy'])->name('medical-records.destroy');

    // Pet owners
    Route::get('/pet-owners', [PetOwnerController::class, 'index'])->name('pet-owners.index');
    Route::get('/pet-owners/export', [PetOwnerController::class, 'export'])->name('pet-owners.export');
    Route::match(['post', 'delete'], '/pet-owners/bulk-delete', [PetOwnerController::class, 'bulkDestroy'])->name('pet-owners.bulk-delete');
    Route::get('/pet-owners/{id}', [PetOwnerController::class, 'show'])->name('pet-owners.show');
    Route::delete('/pet-owners/{id}', [PetOwnerController::class, 'destroy'])->name('pet-owners.destroy');

    // Admin Real-time Alerts & Notifications
    Route::get('/notifications/unread-alerts', [AdminNotificationController::class, 'unreadAlerts'])->name('notifications.unread-alerts');
    Route::post('/notifications/{id}/mark-read', [AdminNotificationController::class, 'markRead'])->name('notifications.mark-read');

    // Veterinarians
    Route::get('/veterinarians', [VeterinarianController::class, 'index'])->name('veterinarians.index');
    Route::post('/veterinarians', [VeterinarianController::class, 'store'])->name('veterinarians.store');
    Route::put('/veterinarians/{id}', [VeterinarianController::class, 'update'])->name('veterinarians.update');
    Route::post('/veterinarians/{id}/toggle-status', [VeterinarianController::class, 'toggleStatus'])->name('veterinarians.toggle-status');
    Route::delete('/veterinarians/{id}', [VeterinarianController::class, 'destroy'])->name('veterinarians.destroy');

    // SMS notifications (UI shell — PhilSMS integration in Phase 5)
    Route::get('/sms', [SmsController::class, 'index'])->name('sms.index');
    Route::post('/sms/test', [SmsController::class, 'sendTest'])->name('sms.test');
    Route::post('/sms/compose', [SmsController::class, 'compose'])->name('sms.compose');

    // Settings & Profile
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/clinic', [SettingsController::class, 'updateClinic'])->name('settings.clinic');
    Route::post('/settings/branches/{branch}', [SettingsController::class, 'updateBranch'])->name('settings.branch');
    Route::post('/settings/chatbot', [SettingsController::class, 'updateChatbot'])->name('settings.chatbot');
    Route::post('/settings/sms', [SettingsController::class, 'updateSms'])->name('settings.sms');
    Route::post('/settings/preferences', [SettingsController::class, 'updatePreferences'])->name('settings.preferences');
    Route::post('/profile/update', [SettingsController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profile/password', [SettingsController::class, 'changePassword'])->name('profile.password');
    Route::post('/users/{id}/update', [SettingsController::class, 'updateAdmin'])->name('users.update');
    Route::post('/users/{id}/reset-password', [SettingsController::class, 'resetAdminPassword'])->name('users.reset-password');
});
