<?php

use App\Enums\BuildingClass;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('quick_audits', function (Blueprint $table): void {
            $table->string('occupancy_class')->default(BuildingClass::CLASS_B->value)->change();
        });

        // Migrate any existing quick_audit records to official Uganda Building Control classes
        DB::table('quick_audits')
            ->where('occupancy_class', 'like', '%Commercial%')
            ->orWhere('occupancy_class', 'like', '%Residential%')
            ->update(['occupancy_class' => BuildingClass::CLASS_B->value]);

        DB::table('projects')
            ->where('occupancy_class', 'like', '%Residential%')
            ->update(['occupancy_class' => BuildingClass::CLASS_B->value]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quick_audits', function (Blueprint $table): void {
            $table->string('occupancy_class')->default('Class A (Residential)')->change();
        });
    }
};
