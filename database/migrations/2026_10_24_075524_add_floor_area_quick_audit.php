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
        Schema::table('quick_audits', function (Blueprint $table) {
            $table->jsonb('floor_area_metrics')->nullable()->after('extracted_geometry');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quick_audits', function (Blueprint $table) {
            //
        });
    }
};
