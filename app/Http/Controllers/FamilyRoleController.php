<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Family;
use App\Services\FamilyRoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
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

        $roles = $family->roles()->with('user')->get();
        $membersWithoutUser = $family->members()->whereNull('user_id')->get();
        $usersWithoutRole = \App\Models\User::where('tenant_id', Auth::user()->tenant_id)
            ->whereDoesntHave('familyRoles', function ($query) use ($family) {
                $query->where('family_id', $family->id);
            })
            ->get();

        return view('family-roles.index', compact('family', 'roles', 'membersWithoutUser', 'usersWithoutRole'));
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
            $validated['user_id'],
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

        $this->familyRoleService->assignBackupAdmin($validated['user_id'], $family->id);

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

        $this->familyRoleService->removeBackupAdmin($validated['user_id'], $family->id);

        return redirect()->route('families.roles.index', $family)
            ->with('success', 'Backup admin removed successfully.');
    }

    /**
     * Request admin role.
     */
    public function requestAdminRole(Request $request, Family $family): RedirectResponse
    {
        $user = Auth::user();
        $this->familyRoleService->requestAdminRole($user->id, $family->id);

        return redirect()->route('families.roles.index', $family)
            ->with('success', 'Admin role request submitted. You will be promoted if no active admins exist after 3 requests.');
    }
}
