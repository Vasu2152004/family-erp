<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add the family_member_id column if it doesn't exist
        if (!Schema::hasColumn('budgets', 'family_member_id')) {
            Schema::table('budgets', function (Blueprint $table) {
                $table->foreignId('family_member_id')->nullable()->after('category_id')->constrained('family_members')->onDelete('cascade');
                $table->index('family_member_id');
            });
        }

        // Drop the old unique constraint if it exists
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();
        
        // Find and drop the old unique constraint
        $constraints = $connection->select(
            "SELECT CONSTRAINT_NAME 
             FROM information_schema.TABLE_CONSTRAINTS 
             WHERE TABLE_SCHEMA = ? 
             AND TABLE_NAME = 'budgets' 
             AND CONSTRAINT_TYPE = 'UNIQUE'
             AND CONSTRAINT_NAME != 'budgets_unique'",
            [$database]
        );
        
        foreach ($constraints as $constraint) {
            $keyColumns = $connection->select(
                "SELECT COLUMN_NAME 
                 FROM information_schema.KEY_COLUMN_USAGE 
                 WHERE TABLE_SCHEMA = ? 
                 AND TABLE_NAME = 'budgets' 
                 AND CONSTRAINT_NAME = ?
                 ORDER BY ORDINAL_POSITION",
                [$database, $constraint->CONSTRAINT_NAME]
            );
            
            $columns = array_map(fn($col) => $col->COLUMN_NAME, $keyColumns);
            // Check if this is the old unique constraint (4 columns: family_id, category_id, month, year)
            if (count($columns) === 4 && 
                in_array('family_id', $columns) && 
                in_array('category_id', $columns) && 
                in_array('month', $columns) && 
                in_array('year', $columns) &&
                !in_array('family_member_id', $columns)) {
                Schema::table('budgets', function (Blueprint $table) use ($constraint) {
                    $table->dropUnique($constraint->CONSTRAINT_NAME);
                });
                break;
            }
        }

        // Add the new unique constraint if it doesn't exist
        $newConstraintExists = $connection->select(
            "SELECT CONSTRAINT_NAME 
             FROM information_schema.TABLE_CONSTRAINTS 
             WHERE TABLE_SCHEMA = ? 
             AND TABLE_NAME = 'budgets' 
             AND CONSTRAINT_NAME = 'budgets_unique'",
            [$database]
        );
        
        if (empty($newConstraintExists)) {
            Schema::table('budgets', function (Blueprint $table) {
                $table->unique(['family_id', 'family_member_id', 'category_id', 'month', 'year'], 'budgets_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->dropUnique('budgets_unique');
            $table->unique(['family_id', 'category_id', 'month', 'year']);
            $table->dropForeign(['family_member_id']);
            $table->dropIndex(['family_member_id']);
            $table->dropColumn('family_member_id');
        });
    }
};
