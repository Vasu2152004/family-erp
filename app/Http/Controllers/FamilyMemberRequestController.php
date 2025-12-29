<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\FamilyMemberRequest;
use App\Services\FamilyMemberRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FamilyMemberRequestController extends Controller
{
    public function __construct(
        private FamilyMemberRequestService $requestService
    ) {
    }

    /**
     * Show pending requests for the authenticated user.
     */
    public function index(): View
    {
        $pendingRequests = FamilyMemberRequest::where('requested_user_id', Auth::id())
            ->where('status', 'pending')
            ->with(['family', 'requestedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('family-member-requests.index', compact('pendingRequests'));
    }

    /**
     * Accept a family member request.
     */
    public function accept(FamilyMemberRequest $request): RedirectResponse
    {
        try {
            $member = $this->requestService->acceptRequest($request->id, Auth::id());

            return redirect()->route('family-member-requests.index')
                ->with('success', 'Request accepted. Family member has been added successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('family-member-requests.index')
                ->withErrors($e->errors());
        }
    }

    /**
     * Reject a family member request.
     */
    public function reject(FamilyMemberRequest $request): RedirectResponse
    {
        try {
            $this->requestService->rejectRequest($request->id, Auth::id());

            return redirect()->route('family-member-requests.index')
                ->with('success', 'Request rejected.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('family-member-requests.index')
                ->withErrors($e->errors());
        }
    }
}
