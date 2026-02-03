<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Helpers\PerformanceHelper;
use App\Models\CalendarEvent;
use App\Models\DoctorVisit;
use App\Models\Family;
use App\Models\FamilyMemberRequest;
use App\Models\FinanceAccount;
use App\Models\InventoryItem;
use App\Models\ShoppingListItem;
use App\Models\Task;
use App\Models\Transaction;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with all data loaded in the controller (no DB in Blade).
     */
    public function __invoke(): View
    {
        $user = once(fn () => auth()->user());

        $key = 'dashboard_data_' . $user->id;
        if (!app()->bound($key)) {
            $accessibleFamilies = PerformanceHelper::getAccessibleFamilies($user->id);
            $familyIds = $accessibleFamilies->pluck('id');

            $familiesCount = $familyIds->count();
            $pendingRequestsCount = FamilyMemberRequest::where('requested_user_id', $user->id)
                ->where('status', 'pending')
                ->count();

            $unreadNotifications = $user->unreadNotifications()
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            $budgetAlerts = $unreadNotifications->filter(fn ($n) => in_array($n->type, ['budget_alert', 'budget_exceeded']));
            $otherNotifications = $unreadNotifications->filter(fn ($n) => !in_array($n->type, ['budget_alert', 'budget_exceeded']));

            $today = Carbon::today();
            $future = $today->copy()->addDays(30);
            $expiringVehicles = Vehicle::whereIn('family_id', $familyIds)
                ->where('tenant_id', $user->tenant_id)
                ->where(function ($query) use ($today, $future) {
                    $query->where(function ($q) use ($today, $future) {
                        $q->whereBetween('rc_expiry_date', [$today, $future])
                            ->orWhereBetween('insurance_expiry_date', [$today, $future])
                            ->orWhereBetween('puc_expiry_date', [$today, $future]);
                    });
                })
                ->with('family:id,name')
                ->limit(10)
                ->get();

            $budgetAlertFamilyIds = $budgetAlerts->pluck('data.family_id')->filter()->unique()->values()->all();
            $familiesById = empty($budgetAlertFamilyIds)
                ? collect()
                : Family::whereIn('id', $budgetAlertFamilyIds)->get()->keyBy('id');

            // Real data widgets - only when user has families
            $recentTransactions = collect();
            $lowStockItems = collect();
            $lowStockCount = 0;
            $upcomingEvents = collect();
            $upcomingDoctorVisits = collect();
            $taskCountsByStatus = ['pending' => 0, 'in_progress' => 0, 'done' => 0];
            $financeSummary = [];
            $firstFamily = null;
            $shoppingListPendingCount = 0;

            if ($familyIds->isNotEmpty()) {
                $recentTransactions = Transaction::whereIn('family_id', $familyIds)
                    ->where('tenant_id', $user->tenant_id)
                    ->orderBy('transaction_date', 'desc')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->with(['family:id,name', 'financeAccount:id,name'])
                    ->get();

                $lowStockItems = InventoryItem::whereIn('family_id', $familyIds)
                    ->where('tenant_id', $user->tenant_id)
                    ->lowStock()
                    ->with('family:id,name')
                    ->get();
                $lowStockCount = $lowStockItems->count();
                $lowStockItems = $lowStockItems->take(5)->values();

                $upcomingEvents = CalendarEvent::whereIn('family_id', $familyIds)
                    ->where('tenant_id', $user->tenant_id)
                    ->where('start_at', '>=', Carbon::now())
                    ->orderBy('start_at')
                    ->limit(5)
                    ->with('family:id,name')
                    ->get();

                $upcomingDoctorVisits = DoctorVisit::whereIn('family_id', $familyIds)
                    ->where('tenant_id', $user->tenant_id)
                    ->where('status', '!=', 'cancelled')
                    ->where(function ($q) use ($today) {
                        $q->where('visit_date', '>=', $today)
                            ->orWhere('next_visit_date', '>=', $today);
                    })
                    ->orderByRaw('COALESCE(next_visit_date, visit_date) ASC')
                    ->limit(5)
                    ->with(['family:id,name', 'familyMember:id,first_name,last_name'])
                    ->get();

                $taskCounts = Task::whereIn('family_id', $familyIds)
                    ->where('tenant_id', $user->tenant_id)
                    ->select('status', DB::raw('count(*) as count'))
                    ->groupBy('status')
                    ->pluck('count', 'status');
                $taskCountsByStatus = [
                    'pending' => (int) ($taskCounts['pending'] ?? 0),
                    'in_progress' => (int) ($taskCounts['in_progress'] ?? 0),
                    'done' => (int) ($taskCounts['done'] ?? 0),
                ];

                $currentMonthStart = Carbon::now()->startOfMonth();
                $currentMonthEnd = Carbon::now()->endOfMonth();
                $families = $accessibleFamilies;
                $familyIdsArr = $familyIds->all();

                $balancesByFamily = empty($familyIdsArr)
                    ? collect()
                    : FinanceAccount::whereIn('family_id', $familyIdsArr)
                        ->where('is_active', true)
                        ->selectRaw('family_id, SUM(current_balance) as total')
                        ->groupBy('family_id')
                        ->pluck('total', 'family_id');
                $incomeByFamily = empty($familyIdsArr)
                    ? collect()
                    : Transaction::whereIn('family_id', $familyIdsArr)
                        ->where('type', 'INCOME')
                        ->whereBetween('transaction_date', [$currentMonthStart, $currentMonthEnd])
                        ->selectRaw('family_id, SUM(amount) as total')
                        ->groupBy('family_id')
                        ->pluck('total', 'family_id');
                $expenseByFamily = empty($familyIdsArr)
                    ? collect()
                    : Transaction::whereIn('family_id', $familyIdsArr)
                        ->where('type', 'EXPENSE')
                        ->whereBetween('transaction_date', [$currentMonthStart, $currentMonthEnd])
                        ->selectRaw('family_id, SUM(amount) as total')
                        ->groupBy('family_id')
                        ->pluck('total', 'family_id');

                foreach ($families as $family) {
                    $financeSummary[] = [
                        'family' => $family,
                        'total_balance' => (float) ($balancesByFamily[$family->id] ?? 0),
                        'monthly_income' => (float) ($incomeByFamily[$family->id] ?? 0),
                        'monthly_expense' => (float) ($expenseByFamily[$family->id] ?? 0),
                    ];
                }
                $firstFamily = $families->first();
                $shoppingListPendingCount = ShoppingListItem::whereIn('family_id', $familyIds)
                    ->pending()
                    ->count();
            } else {
                $firstFamily = null;
            }

            app()->instance($key, [
                'user' => $user,
                'familiesCount' => $familiesCount,
                'pendingRequestsCount' => $pendingRequestsCount,
                'budgetAlerts' => $budgetAlerts,
                'otherNotifications' => $otherNotifications,
                'expiringVehicles' => $expiringVehicles,
                'familiesById' => $familiesById,
                'recentTransactions' => $recentTransactions,
                'lowStockItems' => $lowStockItems,
                'lowStockCount' => $lowStockCount,
                'upcomingEvents' => $upcomingEvents,
                'upcomingDoctorVisits' => $upcomingDoctorVisits,
                'taskCountsByStatus' => $taskCountsByStatus,
                'financeSummary' => $financeSummary,
                'firstFamily' => $firstFamily ?? null,
                'shoppingListPendingCount' => $shoppingListPendingCount,
            ]);
        }

        $data = app($key);

        return view('dashboard', [
            'user' => $data['user'],
            'familiesCount' => $data['familiesCount'],
            'pendingRequestsCount' => $data['pendingRequestsCount'],
            'budgetAlerts' => $data['budgetAlerts'],
            'otherNotifications' => $data['otherNotifications'],
            'expiringVehicles' => $data['expiringVehicles'],
            'familiesById' => $data['familiesById'],
            'recentTransactions' => $data['recentTransactions'],
            'lowStockItems' => $data['lowStockItems'],
            'lowStockCount' => $data['lowStockCount'],
            'upcomingEvents' => $data['upcomingEvents'],
            'upcomingDoctorVisits' => $data['upcomingDoctorVisits'],
            'taskCountsByStatus' => $data['taskCountsByStatus'],
            'financeSummary' => $data['financeSummary'],
            'firstFamily' => $data['firstFamily'] ?? null,
            'shoppingListPendingCount' => $data['shoppingListPendingCount'] ?? 0,
        ]);
    }
}
