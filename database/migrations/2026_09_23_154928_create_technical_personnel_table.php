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
        Schema::create('technical_personnel', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('department_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('name')->unique();
            $table->string('email')->nullable()->unique();
            $table->string('phone', 30)->nullable();
            $table->string('designation')->nullable();
            $table->string('specialization')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->index(['department_id', 'status']);
            $table->index(['status', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technical_personnel');
    }
};
