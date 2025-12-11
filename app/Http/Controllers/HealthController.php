<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DoctorVisit;
use App\Models\Family;
use App\Models\MedicineReminder;
use App\Models\MedicalRecord;
use App\Models\Prescription;
use Illuminate\View\View;
use App\Services\HealthService;

class HealthController extends Controller
{
    public function __construct(private readonly HealthService $healthService)
    {
    }

    public function index(Family $family): View
    {
        $this->authorize('viewAny', [MedicalRecord::class, $family]);

        $tenantId = auth()->user()->tenant_id;

        $recentVisits = DoctorVisit::with(['familyMember', 'medicalRecord'])
            ->where('family_id', $family->id)
            ->forTenant($tenantId)
            ->where('status', 'completed')
            ->whereDate('visit_date', '<=', now())
            ->orderByDesc('visit_date')
            ->limit(5)
            ->get();

        $upcomingVisits = DoctorVisit::with(['familyMember', 'medicalRecord'])
            ->where('family_id', $family->id)
            ->forTenant($tenantId)
            ->where('status', 'scheduled')
            ->whereDate('visit_date', '>', now())
            ->orderBy('visit_date')
            ->limit(5)
            ->get();

        $rawReminders = MedicineReminder::with(['prescription', 'familyMember'])
            ->where('family_id', $family->id)
            ->forTenant($tenantId)
            ->active()
            ->get();

        $upcomingReminders = $rawReminders
            ->map(function (MedicineReminder $reminder) {
                $next = $reminder->next_run_at ?: $this->healthService->calculateNextRunAt(
                    $reminder->frequency,
                    $reminder->reminder_time ?? '08:00',
                    $reminder->start_date->toDateString(),
                    $reminder->end_date?->toDateString(),
                    $reminder->days_of_week ?? []
                );

                // persist next_run_at if it was missing so future loads are faster
                if ($next && !$reminder->next_run_at) {
                    $reminder->next_run_at = $next;
                    $reminder->saveQuietly();
                }

                $reminder->calculated_next_run_at = $next;
                return $reminder;
            })
            ->filter(function (MedicineReminder $reminder) {
                $next = $reminder->calculated_next_run_at;
                return $next !== null
                    && $next->greaterThanOrEqualTo(now())
                    && $next->lessThanOrEqualTo(now()->addDays(30));
            })
            ->sortBy('calculated_next_run_at')
            ->take(20)
            ->values();

        $recordCounts = MedicalRecord::where('family_id', $family->id)
            ->forTenant($tenantId)
            ->selectRaw('record_type, COUNT(*) as total')
            ->groupBy('record_type')
            ->pluck('total', 'record_type');

        $visitStats = DoctorVisit::where('family_id', $family->id)
            ->forTenant($tenantId)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $activePrescriptions = Prescription::with('familyMember')
            ->where('family_id', $family->id)
            ->forTenant($tenantId)
            ->where('status', 'active')
            ->orderByDesc('start_date')
            ->limit(5)
            ->get();

        return view('health.dashboard', compact(
            'family',
            'recentVisits',
            'upcomingVisits',
            'upcomingReminders',
            'recordCounts',
            'visitStats',
            'activePrescriptions'
        ));
    }
}

