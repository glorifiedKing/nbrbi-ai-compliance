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
        Schema::create('quick_audits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('file_name');
            $table->string('original_name')->nullable();
            $table->unsignedBigInteger('file_size_bytes')->nullable();
            $table->string('file_hash', 64)->nullable();
            $table->string('discipline'); // architectural, structural, mechanical, electrical
            $table->string('occupancy_class')->default('Class A (Residential)');
            $table->string('status')->default('pending'); // pending, analyzing, completed, failed
            $table->string('batch_job_id')->nullable();
            $table->text('error_message')->nullable();
            $table->jsonb('preflight_metrics')->nullable();
            $table->jsonb('extracted_geometry')->nullable();
            $table->jsonb('discrepancies')->nullable();
            $table->jsonb('summary_metrics')->nullable();
            $table->string('overall_status')->nullable(); // PASS, WARNING, FAIL, REJECTED_PREFLIGHT
            $table->timestamp('analyzed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quick_audits');
    }
};
