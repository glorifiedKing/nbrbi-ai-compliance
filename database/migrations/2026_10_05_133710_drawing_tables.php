<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drawings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('floor_id')->constrained()->cascadeOnDelete();
            $table->string('discipline'); // ['architectural', 'structural', 'mechanical', 'electrical']);
            $table->uuid('current_version_id')->nullable(); // updated when a new version completes processing
            $table->timestamps();

            $table->unique(['floor_id', 'discipline']);
        });

        Schema::create('drawing_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('drawing_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->integer('version_number')->default(1);
            $table->string('file_name');
            $table->unsignedBigInteger('file_size_bytes')->nullable();
            $table->string('file_hash', 64); // SHA-256 to prevent duplicate uploads
            $table->string('status'); // ['pending', 'preflight', 'analyzing', 'completed', 'failed'])->default('pending');
            $table->string('batch_job_id')->nullable()->index(); // tracks Laravel/Celery batch
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['drawing_id', 'version_number']);
        });

        // Add foreign key constraint to drawings table for current_version_id
        Schema::table('drawings', function (Blueprint $table) {
            $table->foreign('current_version_id')
                ->references('id')
                ->on('drawing_versions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('drawings', function (Blueprint $table) {
            $table->dropForeign(['current_version_id']);
        });
        Schema::dropIfExists('drawing_versions');
        Schema::dropIfExists('drawings');
    }
};
