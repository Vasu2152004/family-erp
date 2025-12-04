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
        
        // Get families by IDs
        $families = Family::whereIn('id', $familyIds)
            ->withCount(['members', 'roles'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('families.index', compact('families'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $this->authorize('create', Family::class);
        return view('families.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Family::class);

        $user = Auth::user();

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

        return redirect()->route('families.show', $family)
            ->with('success', 'Family created successfully. You are now the owner.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Family $family): View
    {
        $this->authorize('view', $family);

        $family->load([
            'members' => fn($q) => $q->orderBy('created_at', 'desc'),
            'roles.user',
        ]);

        return view('families.show', compact('family'));
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

        $family->update($request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]));

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
}
