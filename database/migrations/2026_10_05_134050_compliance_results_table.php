<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compliance_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('drawing_version_id')->unique()->constrained()->cascadeOnDelete();

            // Preflight check results (Scale detection, DPI, blurriness)
            $table->jsonb('preflight_metrics');

            // Extracted vector measurements and AI contextual room mapping
            $table->jsonb('extracted_geometry')->nullable();

            // Detailed array of issues, violations, and rule citations
            $table->jsonb('discrepancies')->nullable();

            // High-level summary metrics (pass_count, fail_count, warning_count)
            $table->jsonb('summary_metrics');

            $table->string('overall_status'); // ['PASS', 'WARNING', 'FAIL', 'REJECTED_PREFLIGHT']);
            $table->timestamp('analyzed_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_results');
    }
};
