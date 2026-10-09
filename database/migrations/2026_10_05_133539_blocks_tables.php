<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blocks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // e.g., Block A, Tower 1
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('floors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('block_id')->constrained()->cascadeOnDelete();
            $table->integer('floor_number'); // e.g., -1 (Basement), 0 (Ground), 1 (Level 1)
            $table->string('name')->nullable(); // e.g., Ground Floor, Mezzanine
            $table->timestamps();

            $table->unique(['block_id', 'floor_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('floors');
        Schema::dropIfExists('blocks');
    }
};
