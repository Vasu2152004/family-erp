<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyMemberRequest;
use App\Models\FamilyUserRole;
use App\Models\Vehicle;
use Carbon\Carbon;
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
            $familyIds = FamilyUserRole::where('user_id', $user->id)
                ->pluck('family_id')
                ->merge(FamilyMember::where('user_id', $user->id)->pluck('family_id'))
                ->unique()
                ->values();

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

            app()->instance($key, [
                'user' => $user,
                'familiesCount' => $familiesCount,
                'pendingRequestsCount' => $pendingRequestsCount,
                'budgetAlerts' => $budgetAlerts,
                'otherNotifications' => $otherNotifications,
                'expiringVehicles' => $expiringVehicles,
                'familiesById' => $familiesById,
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
        ]);
    }
}
