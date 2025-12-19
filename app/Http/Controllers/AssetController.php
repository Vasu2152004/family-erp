<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasFamilyContext;
use App\Models\Family;
use App\Models\Asset;
use App\Models\AssetUnlockAccess;
use App\Models\AssetUnlockRequest;
use App\Models\FamilyMember;
use App\Services\AssetService;
use App\Services\AssetUnlockRequestService;
use App\Services\AssetAnalyticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Carbon;

class AssetController extends Controller
{
    use HasFamilyContext;

    public function __construct(
        private AssetService $assetService,
        private AssetUnlockRequestService $assetUnlockRequestService,
        private AssetAnalyticsService $analyticsService
    ) {
    }

    /**
     * Display a listing of assets.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        
        if (!$family) {
            return redirect()->route('families.index')
                ->with('info', 'Please select a family to view assets.');
        }

        $this->authorize('viewAny', [Asset::class, $family]);

        $query = Asset::where('family_id', $family->id)
            ->with(['familyMember', 'createdBy', 'createdBy.familyMember']);

        // Filter by asset type
        if ($request->filled('asset_type')) {
            $query->where('asset_type', $request->input('asset_type'));
        }

        // Filter by family member
        if ($request->filled('family_member_id')) {
            $query->where('family_member_id', $request->input('family_member_id'));
        }

        $assets = $query->orderBy('created_at', 'desc')
            ->paginate(10);

        $members = FamilyMember::where('family_id', $family->id)
            ->orderBy('first_name')
            ->get();

        // Get analytics data for charts (excluding locked assets)
        $typeDistributionData = $this->analyticsService->getAssetTypeDistribution($family->id);
        $ownerDistributionData = $this->analyticsService->getOwnerWiseDistribution($family->id);

        return view('assets.index', compact(
            'family',
            'assets',
            'members',
            'typeDistributionData',
            'ownerDistributionData'
        ));
    }

    /**
     * Show the form for creating a new asset.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        
        if (!$family) {
            return redirect()->route('families.index')
                ->with('info', 'Please select a family to create assets.');
        }

        $this->authorize('create', [Asset::class, $family]);

        $members = FamilyMember::where('family_id', $family->id)
            ->orderBy('first_name')
            ->get();

        return view('assets.create', compact('family', 'members'));
    }

    /**
     * Store a newly created asset.
     */
    public function store(Request $request): RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        
        if (!$family) {
            return redirect()->route('families.index')
                ->with('error', 'Please select a family to create assets.');
        }

        $this->authorize('create', [Asset::class, $family]);

        $validated = $request->validate([
            'family_member_id' => ['nullable', 'exists:family_members,id'],
            'asset_type' => ['required', 'in:PROPERTY,GOLD,JEWELRY,LAND'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'purchase_date' => ['nullable', 'date'],
            'purchase_value' => ['nullable', 'numeric', 'min:0'],
            'current_value' => ['nullable', 'numeric', 'min:0'],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'is_locked' => ['sometimes', 'boolean'],
            'pin' => ['nullable', 'string', 'min:4', 'max:20', 'required_if:is_locked,1'],
        ]);

        $this->assetService->createAsset(
            array_merge($validated, ['created_by' => Auth::id()]),
            $family->tenant_id,
            $family->id
        );

        return redirect()->route('assets.index', ['family_id' => $family->id])
            ->with('success', 'Asset created successfully.');
    }

    /**
     * Display the specified asset.
     */
    public function show(Request $request, Asset $asset): View|RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        if (!$family) {
            $family = $asset->family;
        }

        $this->authorize('view', $asset);

        // Eager load relations for display
        $asset->load(['familyMember', 'createdBy', 'createdBy.familyMember']);

        // Auto-unlock check on view when owner is deceased (mirrors investments)
        if ($asset->is_locked && $asset->isOwnerDeceased()) {
            $this->assetUnlockRequestService->checkAndAutoUnlock($asset->id);
            $asset->refresh();
        }

        $user = Auth::user();

        // Treat as unlocked only if asset is not locked or current session PIN unlock
        $isUnlocked = !$asset->is_locked
            || $this->isAssetUnlockedForUser($asset)
            || ($user && $asset->isUnlockedFor($user));

        if ($isUnlocked && !empty($asset->encrypted_notes) && empty($asset->notes)) {
            $asset->decryptNotes();
        }

        $pendingRequest = AssetUnlockRequest::where('asset_id', $asset->id)
            ->where('requested_by', Auth::id())
            ->where('status', 'pending')
            ->first();

        $pendingTotal = AssetUnlockRequest::where('asset_id', $asset->id)
            ->where('status', 'pending')
            ->count();

        $latestPending = AssetUnlockRequest::where('asset_id', $asset->id)
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

        return view('assets.show', [
            'family' => $family,
            'asset' => $asset,
            'isUnlocked' => $isUnlocked,
            'pendingRequest' => $pendingRequest,
            'pendingTotal' => $pendingTotal,
            'canRequestUnlock' => $asset->canBeRequestedForUnlock($user),
            'cooldownActive' => $cooldownActive,
            'cooldownDays' => $cooldownDays,
        ]);
    }

    /**
     * Show the form for editing the specified asset.
     */
    public function edit(Request $request, Asset $asset): View|RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        if (!$family) {
            $family = $asset->family;
        }

        $this->authorize('update', $asset);

        if ($asset->is_locked && !$this->isAssetUnlockedForUser($asset)) {
            return redirect()
                ->route('assets.show', ['asset' => $asset->id, 'family_id' => $family->id])
                ->with('error', 'Unlock this asset with PIN before editing.');
        }

        $members = FamilyMember::where('family_id', $family->id)
            ->orderBy('first_name')
            ->get();

        return view('assets.edit', compact('family', 'asset', 'members'));
    }

    /**
     * Update the specified asset.
     */
    public function update(Request $request, Asset $asset): RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        if (!$family) {
            $family = $asset->family;
        }

        $this->authorize('update', $asset);

        if ($asset->is_locked && !$this->isAssetUnlockedForUser($asset)) {
            return redirect()
                ->route('assets.show', ['asset' => $asset->id, 'family_id' => $family->id])
                ->with('error', 'Unlock this asset with PIN before making changes.');
        }

        $validated = $request->validate([
            'family_member_id' => ['nullable', 'exists:family_members,id'],
            'asset_type' => ['required', 'in:PROPERTY,GOLD,JEWELRY,LAND'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'purchase_date' => ['nullable', 'date'],
            'purchase_value' => ['nullable', 'numeric', 'min:0'],
            'current_value' => ['nullable', 'numeric', 'min:0'],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->assetService->updateAsset($asset->id, $validated);

        return redirect()->route('assets.show', ['asset' => $asset->id, 'family_id' => $family->id])
            ->with('success', 'Asset updated successfully.');
    }

    /**
     * Remove the specified asset.
     */
    public function destroy(Request $request, Asset $asset): RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        if (!$family) {
            $family = $asset->family;
        }

        $this->authorize('delete', $asset);

        if ($asset->is_locked && !$this->isAssetUnlockedForUser($asset)) {
            return redirect()
                ->route('assets.show', ['asset' => $asset->id, 'family_id' => $family->id])
                ->with('error', 'Unlock this asset with PIN before deleting.');
        }

        $this->assetService->deleteAsset($asset->id);

        return redirect()->route('assets.index', ['family_id' => $family->id])
            ->with('success', 'Asset deleted successfully.');
    }

    /**
     * Lock or unlock an asset using a PIN.
     */
    public function toggleLock(Request $request, Asset $asset): RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        if (!$family) {
            $family = $asset->family;
        }

        $this->authorize('update', $asset);

        $validated = $request->validate([
            'is_locked' => ['required', 'boolean'],
            'pin' => ['nullable', 'string', 'min:4', 'max:20'],
        ]);

        $isLocking = (bool) $validated['is_locked'];
        $pin = $validated['pin'] ?? null;

        if ($isLocking && empty($pin)) {
            return redirect()->back()
                ->with('error', 'PIN is required to lock this asset.');
        }

        if (!$isLocking && $asset->pin_hash && empty($pin)) {
            return redirect()->back()
                ->with('error', 'PIN is required to unlock this asset.');
        }

        try {
            $this->assetService->toggleLock($asset->id, $isLocking, $pin);

            // Clear any session unlock on lock or unlock change
            $this->clearAssetUnlockSession($asset->id);

            return redirect()
                ->route('assets.show', ['asset' => $asset->id, 'family_id' => $family->id])
                ->with('success', $isLocking ? 'Asset locked successfully.' : 'Asset unlocked successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Request unlock for a locked asset when owner is deceased (ADMIN/OWNER only).
     */
    public function requestUnlock(Request $request, Asset $asset): RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        if (!$family) {
            $family = $asset->family;
        }

        $this->authorize('view', $asset);

        try {
            $this->assetUnlockRequestService->createUnlockRequest($asset->id, Auth::id());

            return redirect()->route('assets.show', ['asset' => $asset->id, 'family_id' => $family->id])
                ->with('success', 'Unlock request submitted. Asset will be unlocked automatically after enough approvals/requests.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }
    }

    /**
     * Unlock an asset for the current session using PIN (does not change DB lock state).
     */
    public function unlock(Request $request, Asset $asset): RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        if (!$family) {
            $family = $asset->family;
        }

        $this->authorize('view', $asset);

        if (!$asset->is_locked) {
            return redirect()->route('assets.show', ['asset' => $asset->id, 'family_id' => $family->id]);
        }

        $validated = $request->validate([
            'pin' => ['required', 'string', 'min:4', 'max:20'],
        ]);

        if (!$asset->verifyPin($validated['pin'])) {
            return redirect()->back()->with('error', 'Invalid PIN provided.');
        }

        $this->setAssetUnlockedForSession($asset->id);

        return redirect()
            ->route('assets.show', ['asset' => $asset->id, 'family_id' => $family->id])
            ->with('success', 'Asset unlocked for this session. It will lock again when you leave.');
    }

    private function isAssetUnlockedForUser(Asset $asset): bool
    {
        return Session::get($this->getAssetSessionKey($asset->id), false) === true;
    }

    private function setAssetUnlockedForSession(int $assetId): void
    {
        Session::put($this->getAssetSessionKey($assetId), true);
    }

    private function clearAssetUnlockSession(int $assetId): void
    {
        Session::forget($this->getAssetSessionKey($assetId));
    }

    private function getAssetSessionKey(int $assetId): string
    {
        return "asset_unlocked.{$assetId}";
    }
}

