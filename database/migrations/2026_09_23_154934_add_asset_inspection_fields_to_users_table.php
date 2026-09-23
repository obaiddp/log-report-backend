<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('department_id')->nullable()->after('password')->constrained()->restrictOnDelete();
            $table->string('designation')->nullable()->after('department_id');
            $table->string('territory')->nullable()->after('designation');
            $table->enum('status', ['active', 'inactive'])->default('active')->after('territory');
            $table->enum('role', ['admin', 'technician', 'user'])->default('user')->after('status');

            $table->index(['department_id', 'status']);
            $table->index(['role', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['department_id']);
            $table->dropIndex(['role', 'status']);
            $table->dropIndex(['department_id', 'status']);
            $table->dropColumn(['department_id', 'designation', 'territory', 'status', 'role']);
        });
    }
};
