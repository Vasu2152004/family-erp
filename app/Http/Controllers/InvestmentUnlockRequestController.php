<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasFamilyContext;
use App\Models\Family;
use App\Models\InvestmentUnlockRequest;
use App\Services\InvestmentUnlockRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvestmentUnlockRequestController extends Controller
{
    use HasFamilyContext;

    public function __construct(
        private InvestmentUnlockRequestService $unlockRequestService
    ) {
    }

    /**
     * Approve an unlock request.
     */
    public function approve(Request $request, InvestmentUnlockRequest $unlockRequest): RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        if (!$family) {
            $family = $unlockRequest->family;
        }

        $this->authorize('update', $unlockRequest->investment);

        try {
            $this->unlockRequestService->approveUnlockRequest(
                $unlockRequest->id,
                Auth::id()
            );

            return redirect()->back()
                ->with('success', 'Unlock request approved. Investment is now accessible to the requester.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors());
        }
    }

    /**
     * Reject an unlock request.
     */
    public function reject(Request $request, InvestmentUnlockRequest $unlockRequest): RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        if (!$family) {
            $family = $unlockRequest->family;
        }

        $this->authorize('update', $unlockRequest->investment);

        $this->unlockRequestService->rejectUnlockRequest(
            $unlockRequest->id,
            Auth::id()
        );

        return redirect()->back()
            ->with('success', 'Unlock request rejected.');
    }
}




