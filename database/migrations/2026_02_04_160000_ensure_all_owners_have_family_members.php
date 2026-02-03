<?php

declare(strict_types=1);

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ensure every family owner has a FamilyMember record. Run at deploy.
     * Idempotent - safe to run multiple times.
     */
    public function up(): void
    {
        $ownerRoles = DB::table('family_user_roles')
            ->where('role', 'OWNER')
            ->get();

        foreach ($ownerRoles as $role) {
            $family = Family::find($role->family_id);
            $user = User::find($role->user_id);
            if (!$family || !$user) {
                continue;
            }

            if (FamilyMember::where('family_id', $family->id)->where('user_id', $user->id)->exists()) {
                continue;
            }

            if (FamilyMember::where('user_id', $user->id)->exists()) {
                continue;
            }

            $nameParts = preg_split('/\s+/', trim($user->name), 2);
            $firstName = $nameParts[0] ?? 'Owner';
            $lastName = $nameParts[1] ?? '';

            try {
                FamilyMember::create([
                    'tenant_id' => $family->tenant_id,
                    'family_id' => $family->id,
                    'user_id' => $user->id,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'gender' => 'other',
                    'relation' => 'Owner',
                    'email' => $user->email,
                    'phone' => null,
                    'is_deceased' => false,
                ]);
            } catch (\Throwable $e) {
                if (!str_contains($e->getMessage(), 'Duplicate') && !str_contains($e->getMessage(), 'unique')) {
                    throw $e;
                }
            }
        }
    }

    public function down(): void
    {
        // No-op: cannot safely remove
    }
};
