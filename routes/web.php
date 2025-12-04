<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;

// Root route - redirect to dashboard (will redirect to login if not authenticated)
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Authentication Routes
Route::middleware('guest')->group(function () {
    // Login Routes
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    // Registration Routes
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);

    // Password Reset Routes
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotPassword'])->name('forgot-password');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink']);
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('reset-password');
});

// Authenticated Routes
Route::middleware(['auth', 'tenant'])->group(function () {
    // Logout Route
    Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');

    // Dashboard Route
    Route::get('/dashboard', function () {
        return view('dashboard', [
            'user' => auth()->user(),
        ]);
    })->name('dashboard');

    // Family Management Routes
    Route::resource('families', \App\Http\Controllers\FamilyController::class);

    // Family Member Routes (nested under families)
    Route::resource('families.members', \App\Http\Controllers\FamilyMemberController::class)
        ->except(['index'])
        ->shallow();

    Route::get('families/{family}/members', [\App\Http\Controllers\FamilyMemberController::class, 'index'])
        ->name('families.members.index');

    Route::post('families/{family}/members/{member}/link-user', [\App\Http\Controllers\FamilyMemberController::class, 'linkToUser'])
        ->name('families.members.link-user');

    // Family Role Routes
    Route::prefix('families/{family}')->name('families.')->group(function () {
        Route::get('roles', [\App\Http\Controllers\FamilyRoleController::class, 'getRoles'])->name('roles.index');
        Route::post('roles/assign', [\App\Http\Controllers\FamilyRoleController::class, 'assignRole'])->name('roles.assign');
        Route::post('roles/backup-admin', [\App\Http\Controllers\FamilyRoleController::class, 'assignBackupAdmin'])->name('roles.backup-admin');
        Route::delete('roles/backup-admin', [\App\Http\Controllers\FamilyRoleController::class, 'removeBackupAdmin'])->name('roles.remove-backup-admin');
        Route::post('roles/request-admin', [\App\Http\Controllers\FamilyRoleController::class, 'requestAdminRole'])->name('roles.request-admin');
    });

    // Family Member Request Routes
    Route::prefix('family-member-requests')->name('family-member-requests.')->group(function () {
        Route::get('/', [\App\Http\Controllers\FamilyMemberRequestController::class, 'index'])->name('index');
        Route::post('{request}/accept', [\App\Http\Controllers\FamilyMemberRequestController::class, 'accept'])->name('accept');
        Route::post('{request}/reject', [\App\Http\Controllers\FamilyMemberRequestController::class, 'reject'])->name('reject');
    });
});
