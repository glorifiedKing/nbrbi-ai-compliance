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
        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('reference_number')->unique(); // e.g., NBRB-KLA-2026-001
            $table->string('occupancy_class')->nullable(); // Class A, B, C under Uganda Building Control
            $table->string('share_token', 64)->unique();
            $table->boolean('is_public')->default(false);
            $table->string('status')->default('draft'); // draft, in_progress, compliant, non_compliant
            $table->timestamps();
            $table->softDeletes();
        });

        // Pivot table for multi-professional collaboration
        Schema::create('project_users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('viewer'); // lead, architect, structural_eng, mep_eng, reviewer
            $table->string('status')->default('accepted'); // pending, accepted, rejected
            $table->timestamps();

            $table->unique(['project_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_users');
        Schema::dropIfExists('projects');
    }
};
