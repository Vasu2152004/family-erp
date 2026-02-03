<?php

declare(strict_types=1);

use App\Models\FamilyUserRole;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Remove duplicate unlinked FamilyMember records where email matches an owner.
     * These are duplicates of the owner who now has a linked FamilyMember record.
     */
    public function up(): void
    {
        $ownerRoles = DB::table('family_user_roles')
            ->where('role', 'OWNER')
            ->get();

        foreach ($ownerRoles as $role) {
            $user = User::find($role->user_id);
            if (!$user || !$user->email) {
                continue;
            }

            // Only remove duplicates when owner already has a linked FamilyMember in this family
            $ownerHasLinked = DB::table('family_members')
                ->where('family_id', $role->family_id)
                ->where('user_id', $role->user_id)
                ->exists();

            if ($ownerHasLinked) {
                $ownerEmailNorm = strtolower(trim($user->email));
                $ownerNameNorm = strtolower(trim($user->name));

                DB::table('family_members')
                    ->where('family_id', $role->family_id)
                    ->whereNull('user_id')
                    ->where(function ($query) use ($ownerEmailNorm, $ownerNameNorm) {
                        $query->whereRaw('LOWER(TRIM(COALESCE(email, ""))) = ?', [$ownerEmailNorm])
                            ->orWhere(function ($q2) use ($ownerNameNorm) {
                                $q2->where(function ($q3) {
                                    $q3->whereNull('email')->orWhere('email', '');
                                })
                                    ->whereRaw('LOWER(TRIM(CONCAT(COALESCE(first_name,""), " ", COALESCE(last_name,"")))) = ?', [$ownerNameNorm]);
                            });
                    })
                    ->delete();
            }
        }
    }

    public function down(): void
    {
        // Cannot restore deleted records
    }
};
