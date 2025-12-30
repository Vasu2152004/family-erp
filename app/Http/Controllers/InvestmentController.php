<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasFamilyContext;
use App\Models\Family;
use App\Models\Investment;
use App\Models\FamilyMember;
use App\Services\InvestmentService;
use App\Services\InvestmentUnlockRequestService;
use App\Services\InvestmentAnalyticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class InvestmentController extends Controller
{
    use HasFamilyContext;

    public function __construct(
        private InvestmentService $investmentService,
        private InvestmentUnlockRequestService $unlockRequestService,
        private InvestmentAnalyticsService $analyticsService
    ) {
    }

    /**
     * Display a listing of investments.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        
        if (!$family) {
            return redirect()->route('families.index')
                ->with('info', 'Please select a family to view investments.');
        }

        $this->authorize('viewAny', [Investment::class, $family]);

        $query = Investment::where('family_id', $family->id)
            ->with(['familyMember', 'createdBy', 'createdBy.familyMember']);

        // Filter by visibility
        if ($request->filled('show_hidden')) {
            $query->hidden();
        } else {
            // Show visible investments, unlocked investments, or ones owned by the current user (including creator fallback)
            $user = Auth::user();
            $role = $user?->getFamilyRole($family->id);
            $unlockedInvestmentIds = \App\Models\InvestmentUnlockAccess::where('user_id', $user->id)
                ->pluck('investment_id');
            
            $query->where(function ($q) use ($unlockedInvestmentIds, $user, $role) {
                $q->where('is_hidden', false)
                  ->orWhereIn('id', $unlockedInvestmentIds);

                if ($user) {
                    // Effective owner: linked family member
                    $q->orWhere(function ($sub) use ($user) {
                        $sub->whereNotNull('family_member_id')
                            ->whereHas('familyMember', function ($fm) use ($user) {
                                $fm->where('user_id', $user->id);
                            });
                    });

                    // Effective owner: creator when no family member assigned
                    $q->orWhere(function ($sub) use ($user) {
                        $sub->whereNull('family_member_id')
                            ->where('created_by', $user->id);
                    });

                    // Admin/Owner can see in list even if hidden (still needs unlock to view details)
                    if ($role && in_array($role->role, ['OWNER', 'ADMIN'])) {
                        $q->orWhere('is_hidden', true);
                    }
                }
            });
        }

        // Filter by investment type
        if ($request->filled('investment_type')) {
            $query->where('investment_type', $request->input('investment_type'));
        }

        // Filter by family member
        if ($request->filled('family_member_id')) {
            $query->where('family_member_id', $request->input('family_member_id'));
        }

        $investments = $query->orderBy('created_at', 'desc')
            ->paginate(10);

        $members = FamilyMember::where('family_id', $family->id)
            ->orderBy('first_name')
            ->get();

        // Get analytics data for charts (excluding hidden investments)
        $typeDistributionData = $this->analyticsService->getInvestmentTypeDistribution($family->id);
        $profitLossTrendData = $this->analyticsService->getProfitLossTrend($family->id);
        $ownerDistributionData = $this->analyticsService->getOwnerWiseDistribution($family->id);
        $countByTypeData = $this->analyticsService->getInvestmentCountByType($family->id);
        $valueTrendData = $this->analyticsService->getInvestmentValueTrend($family->id);

        return view('investments.index', compact(
            'family',
            'investments',
            'members',
            'typeDistributionData',
            'profitLossTrendData',
            'ownerDistributionData',
            'countByTypeData',
            'valueTrendData'
        ));
    }

    /**
     * Show the form for creating a new investment.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        
        if (!$family) {
            return redirect()->route('families.index')
                ->with('info', 'Please select a family to create investments.');
        }

        $this->authorize('create', [Investment::class, $family]);

        $members = FamilyMember::where('family_id', $family->id)
            ->orderBy('first_name')
            ->get();

        return view('investments.create', compact('family', 'members'));
    }

    /**
     * Store a newly created investment.
     */
    public function store(Request $request): RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        
        if (!$family) {
            return redirect()->route('families.index')
                ->with('error', 'Please select a family to create investments.');
        }

        $this->authorize('create', [Investment::class, $family]);

        $validated = $request->validate([
            'family_member_id' => ['nullable', 'exists:family_members,id'],
            'investment_type' => ['required', 'in:FD,RD,SIP,MUTUAL_FUND,STOCK,CRYPTO,OTHER'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['required_unless:investment_type,SIP', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'interest_rate' => ['nullable', 'exclude_if:investment_type,OTHER', 'numeric', 'min:0', 'max:100'],
            'interest_period' => ['nullable', 'in:YEARLY,MONTHLY,QUARTERLY'],
            'monthly_premium' => ['nullable', 'numeric', 'min:0'],
            'current_value' => ['nullable', 'numeric', 'min:0'],
            'is_hidden' => ['sometimes', 'boolean'],
            'pin' => ['nullable', 'string', 'min:4', 'max:20', 'required_if:is_hidden,1'],
            'details' => ['nullable', 'string'],
        ]);

        // Family Investments (no owner) cannot be hidden
        if (empty($validated['family_member_id']) && !empty($validated['is_hidden'])) {
            $validated['is_hidden'] = false;
        }

        $this->investmentService->createInvestment(
            array_merge($validated, ['created_by' => Auth::id()]),
            $family->tenant_id,
            $family->id
        );

        return redirect()->route('investments.index', ['family_id' => $family->id])
            ->with('success', 'Investment created successfully.');
    }

    /**
     * Display the specified investment.
     */
    public function show(Request $request, Investment $investment): View|RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        if (!$family) {
            $family = $investment->family;
        }

        $this->authorize('view', $investment);

        $investment->load(['familyMember', 'createdBy', 'createdBy.familyMember']);

        $user = Auth::user();
        $isUnlocked = !$investment->is_hidden || $investment->isUnlockedFor($user);
        $canRequestUnlock = $investment->canBeRequestedForUnlock($user);
        $unlockRequests = $investment->getUnlockRequestsFor($user);

        $latestPending = \App\Models\InvestmentUnlockRequest::where('investment_id', $investment->id)
            ->where('status', 'pending')
            ->orderByDesc('last_requested_at')
            ->first();

        $cooldownActive = false;
        $cooldownDays = null;
        if ($latestPending && $latestPending->last_requested_at) {
            $cooldownUntil = Carbon::parse($latestPending->last_requested_at)->addDays(2);
            $secondsLeft = now()->diffInSeconds($cooldownUntil, false);
            if ($secondsLeft > 0) {
                $cooldownActive = true;
                $cooldownDays = (int) ceil($secondsLeft / 86400);
            }
        }

        // If investment is unlocked for user, decrypt details on-the-fly for display
        if ($isUnlocked && !empty($investment->encrypted_details) && empty($investment->details)) {
            try {
                $investment->details = \Illuminate\Support\Facades\Crypt::decryptString($investment->encrypted_details);
            } catch (\Exception $e) {
                $investment->details = '[Unable to decrypt details]';
            }
        }

        // Log access
        $this->investmentService->logAccess($investment->id, $user, 'view');

        $isOwner = $investment->isEffectiveOwner($user);

        return view('investments.show', compact(
            'family',
            'investment',
            'isUnlocked',
            'canRequestUnlock',
            'unlockRequests',
            'isOwner',
            'cooldownActive',
            'cooldownDays'
        ));
    }

    /**
     * Show the form for editing the specified investment.
     */
    public function edit(Request $request, Investment $investment): View|RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        if (!$family) {
            $family = $investment->family;
        }

        $this->authorize('update', $investment);

        $members = FamilyMember::where('family_id', $family->id)
            ->orderBy('first_name')
            ->get();

        return view('investments.edit', compact('family', 'investment', 'members'));
    }

    /**
     * Update the specified investment.
     */
    public function update(Request $request, Investment $investment): RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        if (!$family) {
            $family = $investment->family;
        }

        $this->authorize('update', $investment);

        $validated = $request->validate([
            'family_member_id' => ['nullable', 'exists:family_members,id'],
            'investment_type' => ['required', 'in:FD,RD,SIP,MUTUAL_FUND,STOCK,CRYPTO,OTHER'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['required_unless:investment_type,SIP', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'interest_rate' => ['nullable', 'exclude_if:investment_type,OTHER', 'numeric', 'min:0', 'max:100'],
            'interest_period' => ['nullable', 'in:YEARLY,MONTHLY,QUARTERLY'],
            'monthly_premium' => ['nullable', 'numeric', 'min:0'],
            'current_value' => ['nullable', 'numeric', 'min:0'],
            'details' => ['nullable', 'string'],
        ]);

        // Family Investments (no owner) cannot be hidden
        // If removing owner from a hidden investment, make it visible
        if (empty($validated['family_member_id']) && $investment->is_hidden) {
            // This will be handled in the service, but we ensure it's not set to hidden
            $validated['is_hidden'] = false;
        }

        $this->investmentService->updateInvestment($investment->id, $validated);

        // Log access
        $this->investmentService->logAccess($investment->id, Auth::user(), 'edit');

        return redirect()->route('investments.show', ['investment' => $investment->id, 'family_id' => $family->id])
            ->with('success', 'Investment updated successfully.');
    }

    /**
     * Remove the specified investment.
     */
    public function destroy(Request $request, Investment $investment): RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        if (!$family) {
            $family = $investment->family;
        }

        $this->authorize('delete', $investment);

        $this->investmentService->deleteInvestment($investment->id);

        return redirect()->route('investments.index', ['family_id' => $family->id])
            ->with('success', 'Investment deleted successfully.');
    }

    /**
     * Toggle hidden status of investment.
     */
    public function toggleHidden(Request $request, Investment $investment): RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        if (!$family) {
            $family = $investment->family;
        }

        $this->authorize('update', $investment);

        $validated = $request->validate([
            'is_hidden' => ['required', 'boolean'],
            'pin' => ['nullable', 'string', 'min:4', 'max:20', 'required_if:is_hidden,1'],
        ]);

        $this->investmentService->toggleHidden(
            $investment->id,
            $validated['is_hidden'],
            $validated['pin'] ?? null
        );

        return redirect()->back()
            ->with('success', 'Investment visibility updated successfully.');
    }

    /**
     * Unlock investment with PIN.
     */
    public function unlock(Request $request, Investment $investment): RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        if (!$family) {
            $family = $investment->family;
        }

        $this->authorize('unlock', $investment);

        $validated = $request->validate([
            'pin' => ['required', 'string', 'min:4', 'max:20'],
        ]);

        try {
            $this->investmentService->unlockInvestment($investment->id, Auth::user(), $validated['pin']);

            // Create manual unlock access
            \App\Models\InvestmentUnlockAccess::create([
                'investment_id' => $investment->id,
                'user_id' => Auth::id(),
                'unlocked_at' => now(),
                'unlocked_via' => 'manual',
                'request_id' => null,
            ]);

            return redirect()->back()
                ->with('success', 'Investment unlocked successfully.');
        } catch (\InvalidArgumentException $e) {
            // Log failed attempt
            $this->investmentService->logAccess($investment->id, Auth::user(), 'unlock_attempt', [
                'notes' => 'Failed PIN attempt'
            ]);

            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Create unlock request for hidden investment.
     */
    public function requestUnlock(Request $request, Investment $investment): RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        if (!$family) {
            $family = $investment->family;
        }

        $this->authorize('requestUnlock', $investment);

        try {
            $unlockRequest = $this->unlockRequestService->createUnlockRequest(
                $investment->id,
                Auth::id()
            );

            $message = $unlockRequest->request_count >= 3
                ? 'Unlock request created. Investment has been automatically unlocked after 3 requests.'
                : "Unlock request created. This is request #{$unlockRequest->request_count} of 3.";

            return redirect()->back()
                ->with('success', $message);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors());
        }
    }
}

