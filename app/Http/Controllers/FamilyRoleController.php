<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Family;
use App\Services\FamilyRoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FamilyRoleController extends Controller
{
    public function __construct(
        private FamilyRoleService $familyRoleService
    ) {
    }

    /**
     * Get all roles for a family.
     */
    public function getRoles(Family $family): View
    {
        $this->authorize('view', $family);

        $roles = $family->roles()->with('user')->orderByRaw("CASE WHEN role = 'OWNER' THEN 1 WHEN role = 'ADMIN' THEN 2 WHEN role = 'MEMBER' THEN 3 ELSE 4 END")->get();
        $membersWithoutUser = $family->members()->whereNull('user_id')->get();
        
        // Get all users who are family members of THIS specific family (excluding current user)
        // Users must be linked to a family member record in this family
        $familyMemberUserIds = $family->members()
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->unique()
            ->toArray();
        
        $allUsers = \App\Models\User::whereIn('id', $familyMemberUserIds)
            ->where('id', '!=', Auth::id()) // Exclude current user
            ->orderBy('name')
            ->get();
        
        // Also get all family members (even if they don't have a role yet)
        $allFamilyMembers = $family->members()->with('user')->get();

        // Get user's admin role request if exists
        $userAdminRequest = \App\Models\AdminRoleRequest::where('family_id', $family->id)
            ->where('user_id', Auth::id())
            ->where('status', 'pending')
            ->first();

        return view('family-roles.index', compact('family', 'roles', 'membersWithoutUser', 'userAdminRequest', 'allFamilyMembers', 'allUsers'));
    }

    /**
     * Assign a role to a user.
     */
    public function assignRole(Request $request, Family $family): RedirectResponse
    {
        $this->authorize('manageFamily', $family);

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'role' => ['required', Rule::in(['OWNER', 'ADMIN', 'MEMBER', 'VIEWER'])],
            'is_backup_admin' => ['boolean'],
        ]);

        $this->familyRoleService->assignRole(
            (int) $validated['user_id'],
            $family->id,
            $validated['role'],
            $validated['is_backup_admin'] ?? false
        );

        return redirect()->route('families.roles.index', $family)
            ->with('success', 'Role assigned successfully.');
    }

    /**
     * Assign backup admin status.
     */
    public function assignBackupAdmin(Request $request, Family $family): RedirectResponse
    {
        $this->authorize('update', $family);

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $this->familyRoleService->assignBackupAdmin((int) $validated['user_id'], $family->id);

        return redirect()->route('families.roles.index', $family)
            ->with('success', 'Backup admin assigned successfully.');
    }

    /**
     * Remove backup admin status.
     */
    public function removeBackupAdmin(Request $request, Family $family): RedirectResponse
    {
        $this->authorize('update', $family);

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $this->familyRoleService->removeBackupAdmin((int) $validated['user_id'], $family->id);

        return redirect()->route('families.roles.index', $family)
            ->with('success', 'Backup admin removed successfully.');
    }

    /**
     * Request admin role.
     */
    public function requestAdminRole(Request $request, Family $family): RedirectResponse
    {
        try {
            $user = Auth::user();
            $adminRequest = $this->familyRoleService->requestAdminRole($user->id, $family->id);

            $message = "Admin role request submitted. This is request #{$adminRequest->request_count} of 3.";
            
            if ($adminRequest->request_count >= 3) {
                // Check if user was auto-promoted
                if ($adminRequest->status === 'auto_promoted') {
                    $message = "Congratulations! You have been automatically promoted to ADMIN role after 3 requests.";
                } else {
                    $message .= " You will be automatically promoted if no active admins exist.";
                }
            } else {
                $message .= " You can request again after 2 days from your last request.";
            }

            return redirect()->route('families.roles.index', $family)
                ->with('success', $message);
        } catch (ValidationException $e) {
            return redirect()->route('families.roles.index', $family)
                ->withErrors($e->errors());
        }
    }
}
