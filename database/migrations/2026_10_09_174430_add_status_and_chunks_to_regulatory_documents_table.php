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
        Schema::table('regulatory_documents', function (Blueprint $table) {
            if (! Schema::hasColumn('regulatory_documents', 'total_chunks')) {
                $table->unsignedInteger('total_chunks')->default(0);
            }
            if (! Schema::hasColumn('regulatory_documents', 'status')) {
                $table->string('status')->default('pending');
            }
            if (! Schema::hasColumn('regulatory_documents', 'error_message')) {
                $table->text('error_message')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('regulatory_documents', function (Blueprint $table) {
            $table->dropColumn(['total_chunks', 'status', 'error_message']);
        });
    }
};
