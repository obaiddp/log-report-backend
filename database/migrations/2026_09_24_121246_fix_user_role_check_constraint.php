<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replace the legacy PostgreSQL role check with the canonical application roles.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE "users" DROP CONSTRAINT IF EXISTS "users_role_check"');

        DB::table('users')
            ->whereIn('role', ['technician', 'user'])
            ->update(['role' => 'technical_resource']);

        DB::statement(<<<'SQL'
            ALTER TABLE "users"
            ADD CONSTRAINT "users_role_check"
            CHECK ("role" IN ('admin', 'technical_resource'))
            SQL);
    }

    /**
     * Restore the legacy PostgreSQL role check when rolling back this migration.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE "users" DROP CONSTRAINT IF EXISTS "users_role_check"');

        DB::table('users')
            ->where('role', 'technical_resource')
            ->update(['role' => 'technician']);

        DB::statement(<<<'SQL'
            ALTER TABLE "users"
            ADD CONSTRAINT "users_role_check"
            CHECK ("role" IN ('admin', 'technician', 'user'))
            SQL);
    }
};
