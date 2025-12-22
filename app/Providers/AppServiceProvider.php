<?php

namespace App\Providers;

use App\Models\Family;
use App\Policies\FamilyRolePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use App\Models\Document;
use App\Policies\DocumentPolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Family::class => FamilyRolePolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register policies
        Gate::policy(Family::class, FamilyRolePolicy::class);
        Gate::policy(\App\Models\FinanceAccount::class, \App\Policies\FinanceAccountPolicy::class);
        Gate::policy(\App\Models\Transaction::class, \App\Policies\TransactionPolicy::class);
        Gate::policy(\App\Models\Budget::class, \App\Policies\BudgetPolicy::class);
        Gate::policy(\App\Models\CalendarEvent::class, \App\Policies\CalendarEventPolicy::class);
        Gate::policy(Document::class, DocumentPolicy::class);
        Gate::policy(\App\Models\MedicalRecord::class, \App\Policies\MedicalRecordPolicy::class);
        Gate::policy(\App\Models\DoctorVisit::class, \App\Policies\DoctorVisitPolicy::class);
        Gate::policy(\App\Models\Prescription::class, \App\Policies\PrescriptionPolicy::class);
        Gate::policy(\App\Models\MedicineReminder::class, \App\Policies\MedicineReminderPolicy::class);
        Gate::policy(\App\Models\Task::class, \App\Policies\TaskPolicy::class);
        Gate::policy(\App\Models\Note::class, \App\Policies\NotePolicy::class);
        Gate::policy(\App\Models\Vehicle::class, \App\Policies\VehiclePolicy::class);
        Gate::policy(\App\Models\Asset::class, \App\Policies\AssetPolicy::class);
        Gate::policy(\App\Models\Investment::class, \App\Policies\InvestmentPolicy::class);
        Gate::policy(\App\Models\Medicine::class, \App\Policies\MedicinePolicy::class);

        // Route model binding for Medicine - scope to family
        \Illuminate\Support\Facades\Route::bind('medicine', function ($value, $route) {
            $family = $route->parameter('family');
            if ($family instanceof \App\Models\Family) {
                return \App\Models\Medicine::where('id', $value)
                    ->where('family_id', $family->id)
                    ->firstOrFail();
            }
            return \App\Models\Medicine::findOrFail($value);
        });

        // Route model binding for MedicineIntakeReminder - scope to medicine and family
        \Illuminate\Support\Facades\Route::bind('reminder', function ($value, $route) {
            $medicine = $route->parameter('medicine');
            if ($medicine instanceof \App\Models\Medicine) {
                return \App\Models\MedicineIntakeReminder::where('id', $value)
                    ->where('medicine_id', $medicine->id)
                    ->firstOrFail();
            }
            return \App\Models\MedicineIntakeReminder::findOrFail($value);
        });
    }
}
