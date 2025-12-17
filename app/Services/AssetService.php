<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Asset;
use Illuminate\Support\Facades\DB;

class AssetService
{
    /**
     * Create a new asset.
     */
    public function createAsset(array $data, int $tenantId, int $familyId): Asset
    {
        return DB::transaction(function () use ($data, $tenantId, $familyId) {
            $asset = Asset::create([
                'tenant_id' => $tenantId,
                'family_id' => $familyId,
                'family_member_id' => $data['family_member_id'] ?? null,
                'asset_type' => $data['asset_type'] ?? 'PROPERTY',
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'purchase_date' => $data['purchase_date'] ?? null,
                'purchase_value' => $data['purchase_value'] ?? null,
                'current_value' => $data['current_value'] ?? null,
                'location' => $data['location'] ?? null,
                'notes' => $data['notes'] ?? null,
                'is_locked' => $data['is_locked'] ?? false,
                'created_by' => $data['created_by'] ?? auth()->id(),
            ]);

            if ($asset->is_locked) {
                if (!empty($data['pin'])) {
                    $asset->setPinHash($data['pin']);
                }

                if (!empty($asset->notes)) {
                    $asset->encryptNotes();
                }

                $asset->save();
            }

            return $asset->fresh();
        });
    }

    /**
     * Update an existing asset.
     */
    public function updateAsset(int $assetId, array $data): Asset
    {
        return DB::transaction(function () use ($assetId, $data) {
            $asset = Asset::findOrFail($assetId);

            $asset->update([
                'family_member_id' => $data['family_member_id'] ?? $asset->family_member_id,
                'asset_type' => $data['asset_type'] ?? $asset->asset_type,
                'name' => $data['name'] ?? $asset->name,
                'description' => $data['description'] ?? $asset->description,
                'purchase_date' => $data['purchase_date'] ?? $asset->purchase_date,
                'purchase_value' => $data['purchase_value'] ?? $asset->purchase_value,
                'current_value' => $data['current_value'] ?? $asset->current_value,
                'location' => $data['location'] ?? $asset->location,
                'updated_by' => auth()->id(),
            ]);

            if (array_key_exists('notes', $data)) {
                $asset->notes = $data['notes'];

                if ($asset->is_locked) {
                    $asset->encryptNotes();
                } else {
                    $asset->encrypted_notes = null;
                }

                $asset->save();
            }

            return $asset->fresh();
        });
    }

    /**
     * Delete an asset.
     */
    public function deleteAsset(int $assetId): void
    {
        DB::transaction(function () use ($assetId) {
            $asset = Asset::findOrFail($assetId);
            $asset->delete();
        });
    }

    /**
     * Toggle lock status of an asset with optional PIN verification.
     *
     * @throws \InvalidArgumentException
     */
    public function toggleLock(int $assetId, bool $isLocked, ?string $pin = null): Asset
    {
        return DB::transaction(function () use ($assetId, $isLocked, $pin) {
            $asset = Asset::findOrFail($assetId);

            if ($isLocked) {
                if (!$pin) {
                    throw new \InvalidArgumentException('PIN is required to lock this asset.');
                }

                $asset->setPinHash($pin);
                $asset->is_locked = true;

                if (!empty($asset->notes)) {
                    $asset->encryptNotes();
                }
            } else {
                if ($asset->pin_hash && !$asset->verifyPin($pin)) {
                    throw new \InvalidArgumentException('Invalid PIN provided.');
                }

                if (!empty($asset->encrypted_notes)) {
                    $asset->decryptNotes();
                    $asset->encrypted_notes = null;
                }

                $asset->is_locked = false;
                $asset->pin_hash = null;
            }

            $asset->save();

            return $asset->fresh();
        });
    }
}

