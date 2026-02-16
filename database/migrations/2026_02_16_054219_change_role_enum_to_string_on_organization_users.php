<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE organization_users
            DROP CONSTRAINT organization_users_role_check
        ");
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE organization_users 
            ALTER COLUMN role TYPE VARCHAR(50)
            USING role::text
        ");
    }
};
