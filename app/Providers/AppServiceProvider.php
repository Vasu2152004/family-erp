<?php

namespace App\Providers;

use App\Models\Family;
use App\Policies\FamilyRolePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
    }
}
