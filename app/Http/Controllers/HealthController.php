<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Family;
use App\Models\MedicalRecord;
use App\Models\DoctorVisit;
use App\Models\Prescription;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class HealthController extends Controller
{
    /**
     * Display the health dashboard.
     */
    public function index(Request $request, Family $family): View
    {
        $user = $request->user();
        
        $hasAccess = $family->roles()->where('user_id', $user->id)->exists()
            || $family->members()->where('user_id', $user->id)->exists();
        
        if (!$hasAccess) {
            abort(403, 'You do not have access to this family.');
        }

        // Get stats
        $totalRecords = MedicalRecord::where('family_id', $family->id)
            ->where('tenant_id', $family->tenant_id)
            ->count();

        $totalVisits = DoctorVisit::where('family_id', $family->id)
            ->where('tenant_id', $family->tenant_id)
            ->count();

        $activePrescriptions = Prescription::where('family_id', $family->id)
            ->where('tenant_id', $family->tenant_id)
            ->where('status', 'active')
            ->count();

        // Recent visits (past visits only - exclude future visits)
        $recentVisitsQuery = DoctorVisit::where('family_id', $family->id)
            ->where('tenant_id', $family->tenant_id)
            ->where(function ($query) {
                $query->where('visit_date', '<', Carbon::today())
                    ->orWhere(function ($q) {
                        // If visit_date is today, only include if visit_time is in the past or null
                        $q->where('visit_date', Carbon::today())
                            ->where(function ($timeQuery) {
                                if (Schema::hasColumn('doctor_visits', 'visit_time')) {
                                    $timeQuery->whereNull('visit_time')
                                        ->orWhere('visit_time', '<=', Carbon::now()->format('H:i:s'));
                                } else {
                                    // If visit_time column doesn't exist, include all today's visits
                                    $timeQuery->whereRaw('1=1');
                                }
                            });
                    });
            })
            ->with(['familyMember', 'medicalRecord'])
            ->orderBy('visit_date', 'desc');
        
        if (Schema::hasColumn('doctor_visits', 'visit_time')) {
            $recentVisitsQuery->orderBy('visit_time', 'desc');
        }
        
        $recentVisits = $recentVisitsQuery->limit(3)->get();

        // Upcoming visits (future visits - including today's future visits)
        $upcomingVisitsQuery = DoctorVisit::where('family_id', $family->id)
            ->where('tenant_id', $family->tenant_id)
            ->where(function ($query) {
                $query->where('visit_date', '>', Carbon::today())
                    ->orWhere(function ($q) {
                        // Include today's visits if visit_time is in the future
                        $q->where('visit_date', Carbon::today());
                        if (Schema::hasColumn('doctor_visits', 'visit_time')) {
                            $q->whereNotNull('visit_time')
                                ->where('visit_time', '>', Carbon::now()->format('H:i:s'));
                        }
                    });
            })
            ->with(['familyMember', 'medicalRecord'])
            ->orderBy('visit_date', 'asc');
        
        if (Schema::hasColumn('doctor_visits', 'visit_time')) {
            $upcomingVisitsQuery->orderBy('visit_time', 'asc');
        }
        
        $upcomingVisits = $upcomingVisitsQuery->limit(3)->get();

        // Active prescriptions
        $activePrescriptionsList = Prescription::where('family_id', $family->id)
            ->where('tenant_id', $family->tenant_id)
            ->where('status', 'active')
            ->with(['familyMember', 'doctorVisit'])
            ->orderBy('start_date', 'desc')
            ->limit(3)
            ->get();

        return view('health.dashboard', [
            'family' => $family,
            'totalRecords' => $totalRecords,
            'totalVisits' => $totalVisits,
            'activePrescriptions' => $activePrescriptions,
            'recentVisits' => $recentVisits,
            'upcomingVisits' => $upcomingVisits,
            'activePrescriptionsList' => $activePrescriptionsList,
        ]);
    }
}
