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
        Schema::create('support_logs', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();
            
            $table->date('issue_date');
            
            $table->string('initiated_by');

            $table->foreignId('department_id')->constrained('departments')->restrictOnDelete();

            // --- issue type is missing idk 
            $table->foreignId('item_type_id')->constrained('item_types')->restrictOnDelete();

            /*solved, indoor repairing, outdoor repairing*/;
            $table->enum('status', ['indoor_repairing', 'outdoor_repairing', 'solved'])->default('indoor_repairing');

            $table->text('issue_details')->nullable();

            // --- assigned_to and created_by, idk use for now
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();

            $table->timestamps();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
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
