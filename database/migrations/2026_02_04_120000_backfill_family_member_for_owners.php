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
     * Create FamilyMember records for family owners who don't have one.
     * Ensures owners appear in member selection lists (tasks, investments, assets, etc.).
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

            $exists = FamilyMember::where('family_id', $family->id)
                ->where('user_id', $user->id)
                ->exists();

            if ($exists) {
                continue;
            }

            // User can only have one FamilyMember globally (user_id unique)
            if (FamilyMember::where('user_id', $user->id)->exists()) {
                continue;
            }

            $nameParts = preg_split('/\s+/', trim($user->name), 2);
            $firstName = $nameParts[0] ?? 'Owner';
            $lastName = $nameParts[1] ?? '';

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
        }
    }

    /**
     * Reverse the migration - we cannot safely remove owner FamilyMember records
     * as we cannot distinguish backfilled ones from manually created ones.
     */
    public function down(): void
    {
        // No-op: removing would break member lists
    }
};
