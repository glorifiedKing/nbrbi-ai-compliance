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
        Schema::table('regulatory_chunks', function (Blueprint $table) {
            if (! Schema::hasColumn('regulatory_chunks', 'clause_title')) {
                $table->string('clause_title')->nullable()->after('clause_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('regulatory_chunks', function (Blueprint $table) {
            $table->dropColumn('clause_title');
        });
    }
};
