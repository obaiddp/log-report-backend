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
        Schema::create('support_log_issue_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_log_id')->constrained()->cascadeOnDelete();
            $table->foreignId('issue_type_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->unique(['support_log_id', 'issue_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_log_issue_types');
    }
};
