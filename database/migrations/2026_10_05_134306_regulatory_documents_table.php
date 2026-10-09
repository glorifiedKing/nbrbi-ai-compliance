<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regulatory_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title'); // e.g., Uganda National Building Code 2019
            $table->string('category'); // architectural, structural, mep, fire_safety
            $table->string('edition_year', 4);
            $table->string('file_path');
            $table->string('classification')->nullable(); // eg A,B,C
            $table->unsignedInteger('total_chunks')->default(0);
            $table->string('status')->default('pending'); // pending, processing, indexed, failed
            $table->text('error_message')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignUuid('uploaded_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('regulatory_chunks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('regulatory_document_id')->constrained()->cascadeOnDelete();
            $table->string('clause_number')->nullable(); // e.g., Section 14.3.2
            $table->string('clause_title')->nullable();
            $table->text('content');
            $table->jsonb('metadata')->nullable(); // { "occupancy": "Residential", "topic": "Ventilation" }
            $table->timestamps();
        });

        // Add 768-dimensional vector column for Gemini text-embedding-004
        DB::statement('ALTER TABLE regulatory_chunks ADD COLUMN embedding vector(768);');

        // Add HNSW index for cosine distance vector search
        DB::statement('CREATE INDEX idx_regulatory_chunks_embedding ON regulatory_chunks USING hnsw (embedding vector_cosine_ops);');
    }

    public function down(): void
    {
        Schema::dropIfExists('regulatory_chunks');
        Schema::dropIfExists('regulatory_documents');
    }
};
