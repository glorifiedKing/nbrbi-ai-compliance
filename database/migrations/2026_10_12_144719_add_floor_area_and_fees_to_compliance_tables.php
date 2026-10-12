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
        Schema::table('floors', function (Blueprint $table): void {
            $table->decimal('gross_floor_area_sqm', 10, 2)->nullable()->after('name');
        });

        Schema::table('blocks', function (Blueprint $table): void {
            $table->decimal('total_floor_area_sqm', 12, 2)->default(0.00)->after('name');
        });

        Schema::table('projects', function (Blueprint $table): void {
            $table->decimal('total_floor_area_sqm', 12, 2)->default(0.00)->after('occupancy_class');
            $table->decimal('fee_rate_per_sqm_ugx', 10, 2)->nullable()->after('total_floor_area_sqm');
            $table->decimal('estimated_statutory_fee_ugx', 14, 2)->default(0.00)->after('fee_rate_per_sqm_ugx');
        });

        Schema::table('quick_audits', function (Blueprint $table): void {
            $table->decimal('gross_floor_area_sqm', 10, 2)->nullable()->after('occupancy_class');
            $table->decimal('estimated_statutory_fee_ugx', 14, 2)->default(0.00)->after('gross_floor_area_sqm');
        });

        Schema::table('compliance_results', function (Blueprint $table): void {
            $table->jsonb('floor_area_metrics')->nullable()->after('extracted_geometry');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compliance_results', function (Blueprint $table): void {
            $table->dropColumn('floor_area_metrics');
        });

        Schema::table('quick_audits', function (Blueprint $table): void {
            $table->dropColumn(['gross_floor_area_sqm', 'estimated_statutory_fee_ugx']);
        });

        Schema::table('projects', function (Blueprint $table): void {
            $table->dropColumn(['total_floor_area_sqm', 'fee_rate_per_sqm_ugx', 'estimated_statutory_fee_ugx']);
        });

        Schema::table('blocks', function (Blueprint $table): void {
            $table->dropColumn('total_floor_area_sqm');
        });

        Schema::table('floors', function (Blueprint $table): void {
            $table->dropColumn('gross_floor_area_sqm');
        });
    }
};
