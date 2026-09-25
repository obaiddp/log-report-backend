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
        Schema::create('support_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('ticket_number', 40)->unique();
            $table->date('issue_date');
            $table->text('initiated_by');
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->foreignId('item_type_id')->constrained()->restrictOnDelete();
            $table->text('description');
            $table->enum('status', [
                'open',
                'in_progress',
                'indoor_repair',
                'outdoor_repair',
                'resolved',
                'closed',
                'cancelled',
            ])->default('open');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->text('resolution_notes')->nullable();
            $table->text('internal_remarks')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['issue_date', 'status']);
            $table->index(['status', 'priority']);
            $table->index(['department_id', 'issue_date']);
            $table->index(['item_type_id', 'issue_date']);
            $table->index(['assigned_to', 'status']);
            $table->index(['created_by', 'created_at']);
            $table->index('initiated_by');
            $table->index('priority');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_logs');
    }
};
