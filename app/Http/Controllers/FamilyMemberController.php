<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Services\FamilyMemberRequestService;
use App\Services\FamilyMemberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FamilyMemberController extends Controller
{
    public function __construct(
        private FamilyMemberService $familyMemberService,
        private FamilyMemberRequestService $requestService
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Family $family): View
    {
        $this->authorize('view', $family);

        $members = FamilyMember::where('family_id', $family->id)
            ->with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('family-members.index', compact('family', 'members'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Family $family): View
    {
        $this->authorize('manageFamily', $family);
        return view('family-members.create', compact('family'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Family $family): RedirectResponse
    {
        $this->authorize('manageFamily', $family);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'in:male,female,other'],
            'date_of_birth' => ['nullable', 'date'],
            'relation' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['required_without:user_id', 'nullable', 'email', 'max:255'],
            'user_id' => ['nullable', 'integer', 'exists:users,id', 'required_without:email'],
        ], [
            'email.required_without' => 'Please provide either a user ID or an email address.',
            'user_id.required_without' => 'Please provide either a user ID or an email address.',
            'user_id.exists' => 'The selected user does not exist in the system.',
        ]);

        try {
            // Find user by email or use provided user_id
            $requestedUserId = $validated['user_id'] ?? null;
            if (!$requestedUserId && isset($validated['email'])) {
                // Search for user by email across all tenants (allow cross-tenant family members)
                $user = \App\Models\User::where('email', $validated['email'])->first();

                if (!$user) {
                    throw ValidationException::withMessages([
                        'email' => ['No user account found with this email address. The user must register and create an account in the system before you can send them a family member request.'],
                    ]);
                }
                $requestedUserId = $user->id;
            }

            $this->requestService->createRequest(
                $validated,
                Auth::user()->tenant_id,
                $family->id,
                Auth::id(),
                $requestedUserId
            );

            return redirect()->route('families.show', $family)
                ->with('success', 'Request sent successfully. The user will be added once they accept the request.');
        } catch (ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Family $family, FamilyMember $member): View
    {
        $this->authorize('view', $family);
        $member->load('user:id,name,email');

        return view('family-members.show', compact('family', 'member'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Family $family, FamilyMember $member): View
    {
        $this->authorize('manageFamily', $family);
        return view('family-members.edit', compact('family', 'member'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Family $family, FamilyMember $member): RedirectResponse
    {
        $this->authorize('manageFamily', $family);

        $this->familyMemberService->updateMember($member->id, $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'in:male,female,other'],
            'date_of_birth' => ['nullable', 'date'],
            'relation' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_deceased' => ['sometimes', 'boolean'],
            'date_of_death' => ['nullable', 'date', 'required_if:is_deceased,true'],
        ]));

        return redirect()->route('families.members.show', [$family, $member])
            ->with('success', 'Family member updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Family $family, FamilyMember $member): RedirectResponse
    {
        $this->authorize('manageFamily', $family);
        $member->delete();

        return redirect()->route('families.members.index', $family)
            ->with('success', 'Family member deleted successfully.');
    }

    /**
     * Link a family member to a system user.
     */
    public function linkToUser(Request $request, Family $family, FamilyMember $member): RedirectResponse
    {
        $this->authorize('manageFamily', $family);

        $this->familyMemberService->linkToUser(
            $member->id,
            $request->validate(['user_id' => ['required', 'exists:users,id']])['user_id']
        );

        return redirect()->route('families.members.show', [$family, $member])
            ->with('success', 'Family member linked to user successfully.');
    }
}
