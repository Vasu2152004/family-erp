<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Root route - redirect to dashboard (will redirect to login if not authenticated)
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Authentication Routes
Route::middleware('guest')->group(function () {
    // Login Routes - Rate limit: 5 attempts per minute
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1');

    // Registration Routes - Rate limit: 3 attempts per hour
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->middleware('throttle:3,60');

    // Password Reset Routes - Rate limit: 3 attempts per hour
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotPassword'])->name('forgot-password');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->middleware('throttle:3,60');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('reset-password')->middleware('throttle:3,60');
});

// Authenticated Routes
Route::middleware(['auth', 'tenant'])->group(function () {
        // Profile
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

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
        ->except(['index']);

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
        Route::post('roles/approve-admin-request', [\App\Http\Controllers\FamilyRoleController::class, 'approveAdminRoleRequest'])->name('roles.approve-admin-request');
        Route::post('roles/reject-admin-request', [\App\Http\Controllers\FamilyRoleController::class, 'rejectAdminRoleRequest'])->name('roles.reject-admin-request');

        // Deceased verification votes
        Route::post('members/{member}/deceased/request', [\App\Http\Controllers\FamilyMemberController::class, 'requestDeceasedVerification'])->name('members.deceased.request');
        Route::post('members/{member}/deceased/vote', [\App\Http\Controllers\FamilyMemberController::class, 'voteDeceased'])->name('members.deceased.vote');
    });

    // Finance Routes (Standalone - not nested under families)
    Route::prefix('finance')->name('finance.')->group(function () {
        // Finance Dashboard/Index
        Route::get('/', [\App\Http\Controllers\FinanceController::class, 'index'])->name('index');
        
        // Finance Accounts
        Route::resource('accounts', \App\Http\Controllers\FinanceAccountController::class)->names([
            'index' => 'accounts.index',
            'create' => 'accounts.create',
            'store' => 'accounts.store',
            'edit' => 'accounts.edit',
            'update' => 'accounts.update',
            'destroy' => 'accounts.destroy',
        ]);
        
        // Transactions
        Route::resource('transactions', \App\Http\Controllers\TransactionController::class);
        
        // Budgets
        Route::resource('budgets', \App\Http\Controllers\BudgetController::class);
        // Analytics
        Route::get('analytics', [\App\Http\Controllers\FinanceAnalyticsController::class, 'dashboard'])->name('analytics.dashboard');
        Route::get('analytics/monthly-data', [\App\Http\Controllers\FinanceAnalyticsController::class, 'getMonthlyData'])->name('analytics.monthly-data');
        Route::get('analytics/member-wise-data', [\App\Http\Controllers\FinanceAnalyticsController::class, 'getMemberWiseData'])->name('analytics.member-wise-data');
    });

    // Assets (Standalone module)
    Route::prefix('assets')->name('assets.')->group(function () {
        Route::get('/', [\App\Http\Controllers\AssetController::class, 'index'])->name('index');
        Route::get('create', [\App\Http\Controllers\AssetController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\AssetController::class, 'store'])->name('store');
        Route::get('{asset}', [\App\Http\Controllers\AssetController::class, 'show'])->name('show');
        Route::get('{asset}/edit', [\App\Http\Controllers\AssetController::class, 'edit'])->name('edit');
        Route::patch('{asset}', [\App\Http\Controllers\AssetController::class, 'update'])->name('update');
        Route::delete('{asset}', [\App\Http\Controllers\AssetController::class, 'destroy'])->name('destroy');
        Route::post('{asset}/toggle-lock', [\App\Http\Controllers\AssetController::class, 'toggleLock'])->name('toggle-lock');
        Route::post('{asset}/unlock', [\App\Http\Controllers\AssetController::class, 'unlock'])->name('unlock');
        Route::post('{asset}/request-unlock', [\App\Http\Controllers\AssetController::class, 'requestUnlock'])->name('request-unlock');
    });

    // Investments (Standalone module)
    Route::prefix('investments')->name('investments.')->group(function () {
        Route::get('/', [\App\Http\Controllers\InvestmentController::class, 'index'])->name('index');
        Route::get('create', [\App\Http\Controllers\InvestmentController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\InvestmentController::class, 'store'])->name('store');
        Route::get('{investment}', [\App\Http\Controllers\InvestmentController::class, 'show'])->name('show');
        Route::get('{investment}/edit', [\App\Http\Controllers\InvestmentController::class, 'edit'])->name('edit');
        Route::patch('{investment}', [\App\Http\Controllers\InvestmentController::class, 'update'])->name('update');
        Route::delete('{investment}', [\App\Http\Controllers\InvestmentController::class, 'destroy'])->name('destroy');
        Route::post('{investment}/toggle-hidden', [\App\Http\Controllers\InvestmentController::class, 'toggleHidden'])->name('toggle-hidden');
        Route::post('{investment}/unlock', [\App\Http\Controllers\InvestmentController::class, 'unlock'])->name('unlock');
        Route::post('{investment}/request-unlock', [\App\Http\Controllers\InvestmentController::class, 'requestUnlock'])->name('request-unlock');
    });

    // Investment Unlock Requests
    Route::prefix('investment-unlock-requests')->name('investment-unlock-requests.')->group(function () {
        Route::post('{unlockRequest}/approve', [\App\Http\Controllers\InvestmentUnlockRequestController::class, 'approve'])->name('approve');
        Route::post('{unlockRequest}/reject', [\App\Http\Controllers\InvestmentUnlockRequestController::class, 'reject'])->name('reject');
    });

    // Legacy Finance Routes (nested under families) - Keep for backward compatibility
    Route::prefix('families/{family}')->name('families.')->group(function () {
        // Finance Accounts
        Route::resource('finance-accounts', \App\Http\Controllers\FinanceAccountController::class)->names([
            'index' => 'finance-accounts.index',
            'create' => 'finance-accounts.create',
            'store' => 'finance-accounts.store',
            'edit' => 'finance-accounts.edit',
            'update' => 'finance-accounts.update',
            'destroy' => 'finance-accounts.destroy',
        ]);
        
        // Transactions
        Route::resource('transactions', \App\Http\Controllers\TransactionController::class);
        
        // Budgets
        Route::resource('budgets', \App\Http\Controllers\BudgetController::class);
        
        // Analytics
        Route::get('finance-analytics', [\App\Http\Controllers\FinanceAnalyticsController::class, 'dashboard'])->name('finance-analytics.dashboard');
        Route::get('finance-analytics/monthly-data', [\App\Http\Controllers\FinanceAnalyticsController::class, 'getMonthlyData'])->name('finance-analytics.monthly-data');
        Route::get('finance-analytics/member-wise-data', [\App\Http\Controllers\FinanceAnalyticsController::class, 'getMemberWiseData'])->name('finance-analytics.member-wise-data');
    });

    // Family Member Request Routes
    Route::prefix('family-member-requests')->name('family-member-requests.')->group(function () {
        Route::get('/', [\App\Http\Controllers\FamilyMemberRequestController::class, 'index'])->name('index');
        Route::post('{request}/accept', [\App\Http\Controllers\FamilyMemberRequestController::class, 'accept'])->name('accept');
        Route::post('{request}/reject', [\App\Http\Controllers\FamilyMemberRequestController::class, 'reject'])->name('reject');
    });

    // Notification Routes
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [\App\Http\Controllers\NotificationController::class, 'index'])->name('index');
        Route::post('{notification}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('read');
        Route::post('read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('read-all');
    });

    // Inventory Routes (nested under families)
    Route::prefix('families/{family}')->name('families.')->group(function () {
        // Inventory Categories
        Route::prefix('inventory/categories')->name('inventory.categories.')->group(function () {
            Route::get('/', [\App\Http\Controllers\InventoryCategoryController::class, 'index'])->name('index');
            Route::get('create', [\App\Http\Controllers\InventoryCategoryController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\InventoryCategoryController::class, 'store'])->name('store');
            Route::get('{category}/edit', [\App\Http\Controllers\InventoryCategoryController::class, 'edit'])->name('edit');
            Route::patch('{category}', [\App\Http\Controllers\InventoryCategoryController::class, 'update'])->name('update');
            Route::delete('{category}', [\App\Http\Controllers\InventoryCategoryController::class, 'destroy'])->name('destroy');
        });

        // Inventory Items
        Route::prefix('inventory/items')->name('inventory.items.')->group(function () {
            Route::get('/', [\App\Http\Controllers\InventoryItemController::class, 'index'])->name('index');
            Route::get('create', [\App\Http\Controllers\InventoryItemController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\InventoryItemController::class, 'store'])->name('store');
            Route::get('{item}/edit', [\App\Http\Controllers\InventoryItemController::class, 'edit'])->name('edit');
            Route::patch('{item}', [\App\Http\Controllers\InventoryItemController::class, 'update'])->name('update');
            Route::delete('{item}', [\App\Http\Controllers\InventoryItemController::class, 'destroy'])->name('destroy');
            Route::patch('{item}/quantity', [\App\Http\Controllers\InventoryItemController::class, 'updateQuantity'])->name('update-quantity');
        });

        // Shopping List
        Route::prefix('shopping-list')->name('shopping-list.')->group(function () {
            Route::get('/', [\App\Http\Controllers\ShoppingListController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\ShoppingListController::class, 'store'])->name('store');
            Route::patch('{item}', [\App\Http\Controllers\ShoppingListController::class, 'update'])->name('update');
            Route::delete('{item}', [\App\Http\Controllers\ShoppingListController::class, 'destroy'])->name('destroy');
            Route::patch('{item}/purchased', [\App\Http\Controllers\ShoppingListController::class, 'markPurchased'])->name('mark-purchased');
            Route::patch('{item}/pending', [\App\Http\Controllers\ShoppingListController::class, 'markPending'])->name('mark-pending');
            Route::post('auto-add-low-stock', [\App\Http\Controllers\ShoppingListController::class, 'autoAddLowStock'])->name('auto-add-low-stock');
            Route::delete('purchased/clear', [\App\Http\Controllers\ShoppingListController::class, 'clearPurchased'])->name('clear-purchased');
        });

        // Calendar
        Route::prefix('calendar')->name('calendar.')->group(function () {
            Route::get('/', [\App\Http\Controllers\CalendarController::class, 'index'])->name('index');
            Route::get('create', [\App\Http\Controllers\CalendarController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\CalendarController::class, 'store'])->name('store');
            Route::get('{event}/edit', [\App\Http\Controllers\CalendarController::class, 'edit'])->name('edit');
            Route::patch('{event}', [\App\Http\Controllers\CalendarController::class, 'update'])->name('update');
            Route::delete('{event}', [\App\Http\Controllers\CalendarController::class, 'destroy'])->name('destroy');
        });

        // Health & Medical
        Route::prefix('health')->name('health.')->group(function () {
            Route::get('/', [\App\Http\Controllers\HealthController::class, 'index'])->name('index');

            Route::resource('records', \App\Http\Controllers\MedicalRecordController::class)
                ->names([
                    'index' => 'records.index',
                    'create' => 'records.create',
                    'store' => 'records.store',
                    'show' => 'records.show',
                    'edit' => 'records.edit',
                    'update' => 'records.update',
                    'destroy' => 'records.destroy',
                ]);

            Route::resource('visits', \App\Http\Controllers\DoctorVisitController::class)
                ->names([
                    'index' => 'visits.index',
                    'create' => 'visits.create',
                    'store' => 'visits.store',
                    'show' => 'visits.show',
                    'edit' => 'visits.edit',
                    'update' => 'visits.update',
                    'destroy' => 'visits.destroy',
                ]);

            Route::post('visits/{visit}/prescriptions', [\App\Http\Controllers\PrescriptionController::class, 'store'])
                ->name('visits.prescriptions.store');
            Route::patch('visits/{visit}/prescriptions/{prescription}', [\App\Http\Controllers\PrescriptionController::class, 'update'])
                ->name('visits.prescriptions.update');
            Route::delete('visits/{visit}/prescriptions/{prescription}', [\App\Http\Controllers\PrescriptionController::class, 'destroy'])
                ->name('visits.prescriptions.destroy');
            Route::get('visits/{visit}/prescriptions/{prescription}/download', [\App\Http\Controllers\PrescriptionController::class, 'download'])
                ->name('visits.prescriptions.download');

            Route::post('visits/{visit}/prescriptions/{prescription}/reminders', [\App\Http\Controllers\MedicineReminderController::class, 'store'])
                ->name('visits.prescriptions.reminders.store');
            Route::patch('visits/{visit}/prescriptions/{prescription}/reminders/{reminder}', [\App\Http\Controllers\MedicineReminderController::class, 'update'])
                ->name('visits.prescriptions.reminders.update');
            Route::delete('visits/{visit}/prescriptions/{prescription}/reminders/{reminder}', [\App\Http\Controllers\MedicineReminderController::class, 'destroy'])
                ->name('visits.prescriptions.reminders.destroy');
        });

        // Tasks
        Route::prefix('tasks')->name('tasks.')->group(function () {
            Route::get('/', [\App\Http\Controllers\TaskController::class, 'index'])->name('index');
            Route::get('create', [\App\Http\Controllers\TaskController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\TaskController::class, 'store'])->name('store');
            Route::get('{task}', [\App\Http\Controllers\TaskController::class, 'show'])->name('show');
            Route::get('{task}/edit', [\App\Http\Controllers\TaskController::class, 'edit'])->name('edit');
            Route::patch('{task}', [\App\Http\Controllers\TaskController::class, 'update'])->name('update');
            Route::delete('{task}', [\App\Http\Controllers\TaskController::class, 'destroy'])->name('destroy');
            Route::patch('{task}/status', [\App\Http\Controllers\TaskController::class, 'updateStatus'])->name('update-status');
            Route::get('{task}/logs', [\App\Http\Controllers\TaskLogController::class, 'index'])->name('logs.index');
        });

        // Vehicles
        Route::prefix('vehicles')->name('vehicles.')->group(function () {
            Route::get('/', [\App\Http\Controllers\VehicleController::class, 'index'])->name('index');
            Route::get('create', [\App\Http\Controllers\VehicleController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\VehicleController::class, 'store'])->name('store');
            Route::get('{vehicle}', [\App\Http\Controllers\VehicleController::class, 'show'])->name('show');
            Route::get('{vehicle}/edit', [\App\Http\Controllers\VehicleController::class, 'edit'])->name('edit');
            Route::patch('{vehicle}', [\App\Http\Controllers\VehicleController::class, 'update'])->name('update');
            Route::delete('{vehicle}', [\App\Http\Controllers\VehicleController::class, 'destroy'])->name('destroy');
            
            // Service Logs
            Route::prefix('{vehicle}/service-logs')->name('service-logs.')->group(function () {
                Route::get('/', [\App\Http\Controllers\ServiceLogController::class, 'index'])->name('index');
                Route::get('create', [\App\Http\Controllers\ServiceLogController::class, 'create'])->name('create');
                Route::post('/', [\App\Http\Controllers\ServiceLogController::class, 'store'])->name('store');
                Route::get('{serviceLog}/edit', [\App\Http\Controllers\ServiceLogController::class, 'edit'])->name('edit');
                Route::patch('{serviceLog}', [\App\Http\Controllers\ServiceLogController::class, 'update'])->name('update');
                Route::delete('{serviceLog}', [\App\Http\Controllers\ServiceLogController::class, 'destroy'])->name('destroy');
            });
            
            // Fuel Entries
            Route::prefix('{vehicle}/fuel-entries')->name('fuel-entries.')->group(function () {
                Route::get('/', [\App\Http\Controllers\FuelEntryController::class, 'index'])->name('index');
                Route::get('create', [\App\Http\Controllers\FuelEntryController::class, 'create'])->name('create');
                Route::post('/', [\App\Http\Controllers\FuelEntryController::class, 'store'])->name('store');
                Route::get('{fuelEntry}/edit', [\App\Http\Controllers\FuelEntryController::class, 'edit'])->name('edit');
                Route::patch('{fuelEntry}', [\App\Http\Controllers\FuelEntryController::class, 'update'])->name('update');
                Route::delete('{fuelEntry}', [\App\Http\Controllers\FuelEntryController::class, 'destroy'])->name('destroy');
            });
        });

        // Medicines
        Route::resource('medicines', \App\Http\Controllers\MedicineController::class)
            ->names([
                'index' => 'medicines.index',
                'create' => 'medicines.create',
                'store' => 'medicines.store',
                'show' => 'medicines.show',
                'edit' => 'medicines.edit',
                'update' => 'medicines.update',
                'destroy' => 'medicines.destroy',
            ]);

        Route::get('medicines/{medicine}/prescription/download', [\App\Http\Controllers\MedicineController::class, 'downloadPrescription'])
            ->name('medicines.prescription.download');

        Route::post('medicines/{medicine}/intake-reminders', [\App\Http\Controllers\MedicineIntakeReminderController::class, 'store'])
            ->name('medicines.intake-reminders.store');
        Route::patch('medicines/{medicine}/intake-reminders/{reminder}', [\App\Http\Controllers\MedicineIntakeReminderController::class, 'update'])
            ->name('medicines.intake-reminders.update');
        Route::delete('medicines/{medicine}/intake-reminders/{reminder}', [\App\Http\Controllers\MedicineIntakeReminderController::class, 'destroy'])
            ->name('medicines.intake-reminders.destroy');
        Route::patch('medicines/{medicine}/intake-reminders/{reminder}/toggle', [\App\Http\Controllers\MedicineIntakeReminderController::class, 'toggleStatus'])
            ->name('medicines.intake-reminders.toggle');

        // Notes
        Route::prefix('notes')->name('notes.')->group(function () {
            Route::get('/', [\App\Http\Controllers\NoteController::class, 'index'])->name('index');
            Route::get('create', [\App\Http\Controllers\NoteController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\NoteController::class, 'store'])->name('store');
            Route::get('{note}', [\App\Http\Controllers\NoteController::class, 'show'])->name('show');
            Route::get('{note}/edit', [\App\Http\Controllers\NoteController::class, 'edit'])->name('edit');
            Route::patch('{note}', [\App\Http\Controllers\NoteController::class, 'update'])->name('update');
            Route::delete('{note}', [\App\Http\Controllers\NoteController::class, 'destroy'])->name('destroy');
            Route::post('{note}/unlock', [\App\Http\Controllers\NoteController::class, 'unlock'])->name('unlock');
        });

        // Documents
        Route::prefix('documents')->name('documents.')->group(function () {
            Route::get('/', [\App\Http\Controllers\DocumentController::class, 'index'])->name('index');
            Route::get('create', [\App\Http\Controllers\DocumentController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\DocumentController::class, 'store'])->name('store');
            Route::post('{document}/verify-password', [\App\Http\Controllers\DocumentController::class, 'verifyPassword'])->name('verify-password');
            Route::get('{document}/download', [\App\Http\Controllers\DocumentController::class, 'download'])->name('download');
            Route::post('{document}/download', [\App\Http\Controllers\DocumentController::class, 'download'])->name('download.post');
            Route::patch('{document}', [\App\Http\Controllers\DocumentController::class, 'update'])->name('update');
            Route::delete('{document}', [\App\Http\Controllers\DocumentController::class, 'destroy'])->name('destroy');
        });

        // Document Types
        Route::prefix('document-types')->name('document-types.')->group(function () {
            Route::post('/', [\App\Http\Controllers\DocumentTypeController::class, 'store'])->name('store');
            Route::delete('{documentType}', [\App\Http\Controllers\DocumentTypeController::class, 'destroy'])->name('destroy');
        });
    });

    // Standalone Inventory Routes (for easier access)
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::prefix('categories')->name('categories.')->group(function () {
            Route::get('/', [\App\Http\Controllers\InventoryCategoryController::class, 'index'])->name('index');
            Route::get('create', [\App\Http\Controllers\InventoryCategoryController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\InventoryCategoryController::class, 'store'])->name('store');
            Route::get('{category}/edit', [\App\Http\Controllers\InventoryCategoryController::class, 'edit'])->name('edit');
            Route::patch('{category}', [\App\Http\Controllers\InventoryCategoryController::class, 'update'])->name('update');
            Route::delete('{category}', [\App\Http\Controllers\InventoryCategoryController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('items')->name('items.')->group(function () {
            Route::get('/', [\App\Http\Controllers\InventoryItemController::class, 'index'])->name('index');
            Route::get('create', [\App\Http\Controllers\InventoryItemController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\InventoryItemController::class, 'store'])->name('store');
            Route::get('{item}/edit', [\App\Http\Controllers\InventoryItemController::class, 'edit'])->name('edit');
            Route::patch('{item}', [\App\Http\Controllers\InventoryItemController::class, 'update'])->name('update');
            Route::patch('{item}/quantity', [\App\Http\Controllers\InventoryItemController::class, 'updateQuantity'])->name('update-quantity');
            Route::post('{item}/batches', [\App\Http\Controllers\InventoryItemController::class, 'storeBatch'])->name('store-batch');
            Route::delete('{item}', [\App\Http\Controllers\InventoryItemController::class, 'destroy'])->name('destroy');
        });
    });

    // Standalone Shopping List Routes
    Route::prefix('shopping-list')->name('shopping-list.')->group(function () {
        Route::get('/', [\App\Http\Controllers\ShoppingListController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\ShoppingListController::class, 'store'])->name('store');
        Route::patch('{item}', [\App\Http\Controllers\ShoppingListController::class, 'update'])->name('update');
        Route::delete('{item}', [\App\Http\Controllers\ShoppingListController::class, 'destroy'])->name('destroy');
        Route::patch('{item}/purchased', [\App\Http\Controllers\ShoppingListController::class, 'markPurchased'])->name('mark-purchased');
        Route::patch('{item}/pending', [\App\Http\Controllers\ShoppingListController::class, 'markPending'])->name('mark-pending');
        Route::post('auto-add-low-stock', [\App\Http\Controllers\ShoppingListController::class, 'autoAddLowStock'])->name('auto-add-low-stock');
        Route::delete('purchased/clear', [\App\Http\Controllers\ShoppingListController::class, 'clearPurchased'])->name('clear-purchased');
    });
});
