<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Remove remaining duplicate unlinked FamilyMember records (case-insensitive email, or name match when email null).
     */
    public function up(): void
    {
        $ownerRoles = DB::table('family_user_roles')
            ->where('role', 'OWNER')
            ->get();

        foreach ($ownerRoles as $role) {
            $user = User::find($role->user_id);
            if (!$user) {
                continue;
            }

            $ownerHasLinked = DB::table('family_members')
                ->where('family_id', $role->family_id)
                ->where('user_id', $role->user_id)
                ->exists();

            if (!$ownerHasLinked) {
                continue;
            }

            $ownerEmailNorm = strtolower(trim($user->email ?? ''));
            $ownerNameNorm = strtolower(trim($user->name ?? ''));

            if ($ownerEmailNorm !== '') {
                DB::table('family_members')
                    ->where('family_id', $role->family_id)
                    ->whereNull('user_id')
                    ->whereRaw('LOWER(TRIM(COALESCE(email, ""))) = ?', [$ownerEmailNorm])
                    ->delete();
            }

            if ($ownerNameNorm !== '') {
                DB::table('family_members')
                    ->where('family_id', $role->family_id)
                    ->whereNull('user_id')
                    ->where(function ($query) {
                        $query->whereNull('email')->orWhere('email', '');
                    })
                    ->whereRaw('LOWER(TRIM(CONCAT(COALESCE(first_name,""), " ", COALESCE(last_name,"")))) = ?', [$ownerNameNorm])
                    ->delete();
            }
        }
    }

    public function down(): void
    {
        // Cannot restore
    }
};
