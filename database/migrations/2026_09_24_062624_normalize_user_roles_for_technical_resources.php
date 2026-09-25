<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Convert the legacy role column to a portable string and normalize old
     * technician/user values to the canonical technical_resource value.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE "users" ALTER COLUMN "role" TYPE VARCHAR(50) USING "role"::VARCHAR(50)');
            DB::statement('ALTER TABLE "users" ALTER COLUMN "role" SET DEFAULT \'technical_resource\'');
        } else {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('role', 50)->default('technical_resource')->change();
            });
        }

        DB::table('users')
            ->whereIn('role', ['technician', 'user'])
            ->update(['role' => 'technical_resource']);

        // The legacy application had no verification workflow. Treat its
        // existing active accounts as verified so the restored login remains
        // usable; newly created unverified accounts are still rejected.
        DB::table('users')
            ->where('status', 'active')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);
    }

    /**
     * Keep the normalized values safe if this additive migration is rolled back.
     */
    public function down(): void
    {
        DB::table('users')
            ->where('role', 'technical_resource')
            ->update(['role' => 'technician']);
    }
};
