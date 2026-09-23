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
        Schema::create('assets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->enum('type', ['laptop', 'printer', 'projector', 'computer', 'it_support_equipment']);
            $table->string('brand');
            $table->string('model');
            $table->string('serial_number')->unique();
            $table->string('ram', 50)->nullable();
            $table->decimal('ram_gb', 8, 2)->nullable();
            $table->string('storage', 100)->nullable();
            $table->string('asset_tag')->unique();
            $table->date('acquired_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index(['type', 'acquired_at']);
            $table->index('acquired_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
