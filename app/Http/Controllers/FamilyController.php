<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Family;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FamilyController extends Controller
{
    /**
     * Display a listing of the resource.
     * Only show families where the user has a role or is a member.
     */
    public function index(): View
    {
        $user = Auth::user();
        
        // Get unique family IDs where user has a role
        $familyIdsFromRoles = \App\Models\FamilyUserRole::where('user_id', $user->id)
            ->pluck('family_id')
            ->unique();
        
        // Get unique family IDs where user is a member
        $familyIdsFromMembers = \App\Models\FamilyMember::where('user_id', $user->id)
            ->pluck('family_id')
            ->unique();
        
        // Merge and get unique family IDs
        $familyIds = $familyIdsFromRoles->merge($familyIdsFromMembers)->unique()->values();
        
        // Get families by IDs with counts
        $families = Family::whereIn('id', $familyIds)
            ->withCount(['members', 'roles'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Add owner count to each family (owners are in roles table)
        $families->getCollection()->transform(function ($family) {
            $ownerCount = \App\Models\FamilyUserRole::where('family_id', $family->id)
                ->where('role', 'OWNER')
                ->count();
            $family->members_count = $family->members_count + $ownerCount;
            return $family;
        });

        // Check if user is already part of any family
        $hasFamily = $familyIds->isNotEmpty();

        return view('families.index', compact('families', 'hasFamily'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $user = Auth::user();
        
        // Check if user is already part of a family
        $hasFamily = \App\Models\FamilyUserRole::where('user_id', $user->id)
            ->orWhereHas('family', function ($query) use ($user) {
                $query->whereHas('members', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            })
            ->exists() || \App\Models\FamilyMember::where('user_id', $user->id)->exists();
        
        if ($hasFamily) {
            abort(403, 'You can only be part of one family.');
        }
        
        return view('families.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        // Check if user is already part of a family
        $hasFamily = \App\Models\FamilyUserRole::where('user_id', $user->id)
            ->orWhereHas('family', function ($query) use ($user) {
                $query->whereHas('members', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            })
            ->exists() || \App\Models\FamilyMember::where('user_id', $user->id)->exists();
        
        if ($hasFamily) {
            return redirect()->route('families.index')
                ->with('error', 'You can only be part of one family.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        // Check if user already has a family with the same name
        $existingFamily = Family::where('tenant_id', $user->tenant_id)
            ->where('name', $validated['name'])
            ->whereHas('roles', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->first();

        if ($existingFamily) {
            return redirect()->back()
                ->withErrors(['name' => ['You already have a family with this name.']])
                ->withInput();
        }

        $family = Family::create([
            'tenant_id' => $user->tenant_id,
            'name' => $validated['name'],
        ]);

        // Assign user as OWNER of the family by default
        $family->roles()->create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'role' => 'OWNER',
            'is_backup_admin' => false,
        ]);

        // Seed predefined transaction categories for this family
        $seeder = new \Database\Seeders\TransactionCategorySeeder();
        $seeder->seedForFamily($user->tenant_id, $family->id);

        return redirect()->route('families.show', $family)
            ->with('success', 'Family created successfully. You are now the owner.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Family $family): View
    {
        $this->authorize('view', $family);
        
        // Check if user should be auto-promoted (if they have 3+ requests)
        $this->checkAndPromoteIfNeeded($family);

        $family->load([
            'members' => fn($q) => $q->orderBy('created_at', 'desc'),
            'roles.user',
        ]);

        // Optimize: Load all data in parallel with eager loading
        $userRole = \App\Models\FamilyUserRole::where('family_id', $family->id)
            ->where('user_id', Auth::id())
            ->first();
        $isOwnerOrAdmin = $userRole && ($userRole->role === 'OWNER' || $userRole->role === 'ADMIN');

        // Get owner(s) to include in member count and list - use already loaded roles
        $owners = $family->roles()
            ->where('role', 'OWNER')
            ->with('user:id,name')
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

        // Get pending family member requests for this family (where current user is the requested user)
        $pendingMemberRequests = \App\Models\FamilyMemberRequest::where('family_id', $family->id)
            ->where('requested_user_id', Auth::id())
            ->where('status', 'pending')
            ->with(['family:id,name', 'requestedBy:id,name'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get pending admin role requests for this family (where current user is the requester)
        $pendingAdminRequests = \App\Models\AdminRoleRequest::where('family_id', $family->id)
            ->where('user_id', Auth::id())
            ->where('status', 'pending')
            ->first();

        // Get admin role requests that need admin/owner attention (for admins/owners only)
        $adminRequestsToReview = collect();
        if ($isOwnerOrAdmin) {
            // Get all pending admin role requests for this family (excluding current user's own requests)
            $adminRequestsToReview = \App\Models\AdminRoleRequest::where('family_id', $family->id)
                ->where('user_id', '!=', Auth::id())
                ->where('status', 'pending')
                ->with('user:id,name')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return view('families.show', compact('family', 'pendingMemberRequests', 'pendingAdminRequests', 'adminRequestsToReview', 'isOwnerOrAdmin', 'owners'));
    }
    
    /**
     * Check if user should be auto-promoted and promote if needed.
     */
    private function checkAndPromoteIfNeeded(Family $family): void
    {
        $userRole = \App\Models\FamilyUserRole::where('family_id', $family->id)
            ->where('user_id', Auth::id())
            ->first();
        
        $isOwnerOrAdmin = $userRole && ($userRole->role === 'OWNER' || $userRole->role === 'ADMIN');
        
        if (!$isOwnerOrAdmin) {
            $userAdminRequest = \App\Models\AdminRoleRequest::where('family_id', $family->id)
                ->where('user_id', Auth::id())
                ->whereIn('status', ['pending', 'auto_promoted'])
                ->first();
            
            // If user has auto_promoted status, ensure they have ADMIN role
            if ($userAdminRequest && $userAdminRequest->status === 'auto_promoted') {
                $userRole = \App\Models\FamilyUserRole::where('family_id', $family->id)
                    ->where('user_id', Auth::id())
                    ->first();
                
                if (!$userRole || ($userRole->role !== 'OWNER' && $userRole->role !== 'ADMIN')) {
                    // Role missing or wrong - create/update it
                    $familyRoleService = app(\App\Services\FamilyRoleService::class);
                    $familyRoleService->assignRole(Auth::id(), $family->id, 'ADMIN');
                    \Illuminate\Support\Facades\Cache::forget("user_role_" . Auth::id() . "_{$family->id}");
                }
            }
            // If user has 3+ pending requests, try to promote
            elseif ($userAdminRequest && $userAdminRequest->status === 'pending' && $userAdminRequest->request_count >= 3) {
                $familyRoleService = app(\App\Services\FamilyRoleService::class);
                $promotedRole = $familyRoleService->checkAndAutoPromote($family->id, $userAdminRequest->id);
                if ($promotedRole) {
                    \Illuminate\Support\Facades\Cache::forget("user_role_" . Auth::id() . "_{$family->id}");
                }
            }
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Family $family): View
    {
        $this->authorize('update', $family);
        return view('families.edit', compact('family'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Family $family): RedirectResponse
    {
        $this->authorize('update', $family);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $family->update($validated);

        return redirect()->route('families.show', $family)
            ->with('success', 'Family updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Family $family): RedirectResponse
    {
        $this->authorize('delete', $family);

        $family->delete();

        return redirect()->route('families.index')
            ->with('success', 'Family deleted successfully.');
    }

    /**
     * Leave the family (remove user from family).
     */
    public function leave(Family $family): RedirectResponse
    {
        $this->authorize('view', $family);

        $user = Auth::user();
        
        // Check if user is the owner
        $userRole = \App\Models\FamilyUserRole::where('family_id', $family->id)
            ->where('user_id', $user->id)
            ->first();
        
        if ($userRole && $userRole->role === 'OWNER') {
            return redirect()->route('families.show', $family)
                ->withErrors(['family' => ['You cannot leave the family as you are the owner. Please transfer ownership to another member first or delete the family.']]);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($family, $user) {
            // Remove family member record
            \App\Models\FamilyMember::where('family_id', $family->id)
                ->where('user_id', $user->id)
                ->delete();

            // Remove family user roles
            \App\Models\FamilyUserRole::where('family_id', $family->id)
                ->where('user_id', $user->id)
                ->delete();

            // Clear cache
            \Illuminate\Support\Facades\Cache::forget("user_role_{$user->id}_{$family->id}");
        });

        return redirect()->route('families.index')
            ->with('success', 'You have successfully left the family.');
    }
}
