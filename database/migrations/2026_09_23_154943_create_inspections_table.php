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
        Schema::create('inspections', function (Blueprint $table): void {
            $table->id();
            $table->string('problem_id')->unique();
            $table->foreignId('asset_id')->constrained()->restrictOnDelete();
            $table->text('remarks')->nullable();
            $table->enum('status', ['sold', 'in_progress', 'indoor_repair', 'outdoor_repair']);
            $table->enum('category', ['new_purchase', 'repair']);
            $table->enum('sub_category', ['in_house', 'out_house'])->nullable();
            $table->foreignId('technical_personnel_id')->nullable()->constrained('technical_personnel')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->date('inspection_date');
            $table->timestamps();

            $table->index(['asset_id', 'inspection_date']);
            $table->index(['status', 'inspection_date']);
            $table->index(['category', 'inspection_date']);
            $table->index(['technical_personnel_id', 'inspection_date']);
            $table->index(['created_by', 'created_at']);
            $table->index('inspection_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspections');
    }
};
