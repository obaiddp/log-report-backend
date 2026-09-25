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
        Schema::create('support_log_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('support_log_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('assigned_at');
            $table->timestamp('unassigned_at')->nullable();
            $table->timestamps();

            $table->index(['support_log_id', 'assigned_at']);
            $table->index(['assigned_to', 'unassigned_at']);
            $table->index(['assigned_by', 'assigned_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_log_assignments');
    }
};
