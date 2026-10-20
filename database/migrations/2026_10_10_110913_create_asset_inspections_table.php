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
        Schema::create('asset_inspections', function (Blueprint $table) {
            $table->id();

            // Person bringing the item
            $table->string('username');
            $table->string('designation')->nullable();
            $table->string('email')->nullable();
            $table->string('cell')->nullable();
            $table->foreignId('department_id')->constrained('departments')->restrictOnDelete();
            $table->string('territory')->nullable();

            // Item details
            $table->foreignId('item_type_id')->constrained('item_types')->restrictOnDelete();
            $table->string('model');
            $table->string('brand');
            $table->string('serial_number')->nullable();
            $table->string('item_tag')->nullable();
            $table->string('ram')->nullable();
            $table->string('generation')->nullable();
            $table->string('hard_drive')->nullable();
            $table->enum('status', ['handover_for_repairing', 'recieved_after_repairing', 'new_purchase', 'others',])->default('handover_for_repairing');

            
            // Inspection and recommendations
            $table->text('item_problem')->nullable();
            $table->text('it_remarks')->nullable();
            $table->text('admin_remarks')->nullable();

            $table->enum('approval_status', ['pending_admin', 'completed', 'returned'])->default('pending_admin');

            // Sign Off details
            $table->foreignId('prepared_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('admin_user')->constrained('users')->restrictOnDelete();

            $table->boolean('prepared_by_digital_signature')->default(false);
            $table->boolean('user_digital_signature')->default(false)->nullable();
            $table->boolean('admin_digital_signature')->default(false);

            $table->timestamp('prepared_signed_at')->nullable();
            $table->timestamp('user_signed_at')->nullable();
            $table->timestamp('admin_signed_at')->nullable();
            $table->text('return_reason')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_inspections');
    }
};
