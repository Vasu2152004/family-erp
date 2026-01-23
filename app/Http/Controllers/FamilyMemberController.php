<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyMemberDeceasedVote;
use App\Services\FamilyMemberRequestService;
use App\Services\FamilyMemberService;
use App\Services\FamilyMemberDeceasedService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FamilyMemberController extends Controller
{
    public function __construct(
        private FamilyMemberService $familyMemberService,
        private FamilyMemberRequestService $requestService,
        private FamilyMemberDeceasedService $deceasedService
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Family $family): View
    {
        $this->authorize('view', $family);

        $family->load([
            'members' => fn($q) => $q->orderBy('created_at', 'desc'),
            'roles.user',
        ]);

        // Get owner(s) to include in member list
        $owners = $family->roles()
            ->where('role', 'OWNER')
            ->with('user')
            ->get()
            ->map(function ($role) {
                return (object) [
                    'id' => 'owner_' . $role->user_id,
                    'first_name' => $role->user->name ?? 'Owner',
                    'last_name' => '',
                    'relation' => 'Owner',
                    'is_deceased' => false,
                    'is_owner' => true,
                    'user' => $role->user,
                    'created_at' => $role->created_at,
                ];
            });

        $members = FamilyMember::where('family_id', $family->id)
            ->with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('family-members.index', compact('family', 'members', 'owners'));
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
            'email' => ['required', 'email', 'max:255'],
        ], [
            'email.required' => 'Please provide an email address.',
        ]);

        try {
            // Find user by email
            $user = \App\Models\User::where('email', $validated['email'])->first();

            if (!$user) {
                throw ValidationException::withMessages([
                    'email' => ['No user account found with this email address. The user must register and create an account in the system before you can send them a family member request.'],
                ]);
            }
            $requestedUserId = $user->id;

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

        $votes = FamilyMemberDeceasedVote::where('family_member_id', $member->id)->get();
        $voteCounts = $this->deceasedService->counts($member);
        $myVote = $votes->firstWhere('user_id', Auth::id());

        return view('family-members.show', [
            'family' => $family,
            'member' => $member,
            'votes' => $votes,
            'voteCounts' => $voteCounts,
            'myVote' => $myVote,
        ]);
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

    public function requestDeceasedVerification(Request $request, Family $family, FamilyMember $member): RedirectResponse
    {
        $this->authorize('manageFamily', $family);

        $data = $request->validate([
            'date_of_death' => ['nullable', 'date'],
        ]);

        try {
            $this->deceasedService->startVerification($member, $request->user(), $data['date_of_death'] ?? null);
            return redirect()->route('families.members.show', [$family, $member])
                ->with('success', 'Deceased verification started. All family users must vote.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('families.members.show', [$family, $member])
                ->withErrors($e->errors());
        }
    }

    public function voteDeceased(Request $request, Family $family, FamilyMember $member): RedirectResponse
    {
        $this->authorize('view', $family);

        $data = $request->validate([
            'decision' => ['required', 'in:approved,denied'],
        ]);

        try {
            $this->deceasedService->castVote($member, $request->user(), $data['decision']);
            return redirect()->route('families.members.show', [$family, $member])
                ->with('success', 'Your vote has been recorded.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('families.members.show', [$family, $member])
                ->withErrors($e->errors());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Family $family, FamilyMember $member): RedirectResponse
    {
        $this->authorize('manageFamily', $family);
        $member->delete();

        return redirect()->route('families.show', $family)
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
