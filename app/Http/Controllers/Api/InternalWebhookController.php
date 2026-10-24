<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ComplianceResult;
use App\Models\Drawing;
use App\Models\DrawingVersion;
use App\Models\QuickAudit;
use App\Services\FeeCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InternalWebhookController extends Controller
{
    /**
     * Handle incoming compliance result webhooks from the Python FastAPI worker.
     */
    public function handleComplianceWebhook(Request $request): JsonResponse
    {
        $expectedSecret = (string) config('services.fastapi.secret', 'bims-secure-internal-secret');
        $receivedSecret = (string) $request->header('X-Internal-Secret');

        if (! hash_equals($expectedSecret, $receivedSecret)) {
            Log::warning('Unauthorized webhook attempt to compliance webhook endpoint.');

            return response()->json(['error' => 'Unauthorized internal call'], 403);
        }

        $validated = $request->validate([
            'drawing_version_id' => ['required', 'string', 'uuid'],
            'overall_status' => ['required', 'string'],
            'preflight_metrics' => ['nullable', 'array'],
            'summary_metrics' => ['nullable', 'array'],
            'discrepancies' => ['nullable', 'array'],
            'extracted_geometry' => ['nullable', 'array'],
            'floor_area_metrics' => ['nullable', 'array'],
        ]);

        $id = $validated['drawing_version_id'];
        $overallStatus = strtoupper((string) $validated['overall_status']);
        $isCompleted = in_array($overallStatus, ['PASS', 'WARNING', 'FAIL'], true);

        // 1. Check if this webhook belongs to a Project Drawing Version
        $drawingVersion = DrawingVersion::with('drawing.floor.block.project')->find($id);

        if ($drawingVersion) {
            DB::transaction(function () use ($drawingVersion, $validated, $overallStatus, $isCompleted): void {
                ComplianceResult::updateOrCreate(
                    ['drawing_version_id' => $drawingVersion->id],
                    [
                        'preflight_metrics' => $validated['preflight_metrics'] ?? [],
                        'extracted_geometry' => $validated['extracted_geometry'] ?? [],
                        'floor_area_metrics' => $validated['floor_area_metrics'] ?? null,
                        'discrepancies' => $validated['discrepancies'] ?? [],
                        'summary_metrics' => $validated['summary_metrics'] ?? [
                            'pass_count' => $overallStatus === 'PASS' ? 1 : 0,
                            'fail_count' => $overallStatus === 'FAIL' || $overallStatus === 'REJECTED_PREFLIGHT' ? 1 : 0,
                            'warning_count' => $overallStatus === 'WARNING' ? 1 : 0,
                        ],
                        'overall_status' => $overallStatus,
                        'analyzed_at' => now(),
                    ]
                );

                $status = $isCompleted ? 'completed' : 'failed';
                $errorMessage = $overallStatus === 'REJECTED_PREFLIGHT'
                    ? ($validated['preflight_metrics']['reason'] ?? 'Rejected during preflight quality check.')
                    : null;

                $drawingVersion->update([
                    'status' => $status,
                    'error_message' => $errorMessage,
                ]);

                if ($isCompleted && $drawingVersion->drawing_id) {
                    Drawing::where('id', $drawingVersion->drawing_id)->update([
                        'current_version_id' => $drawingVersion->id,
                    ]);
                }

                // If architectural floor plan returned gross floor area, update the floor and roll up to project
                $floorArea = isset($validated['floor_area_metrics']['gross_floor_area_sqm'])
                    ? (float) $validated['floor_area_metrics']['gross_floor_area_sqm']
                    : null;

                if ($floorArea !== null && $floorArea > 0 && $drawingVersion->drawing?->floor) {
                    $floor = $drawingVersion->drawing->floor;
                    $floor->update([
                        'gross_floor_area_sqm' => $floorArea,
                    ]);

                    if ($floor->block?->project) {
                        FeeCalculationService::recalculateProjectTotals($floor->block->project);
                    }
                }
            });

            Log::info("Compliance result webhook processed for DrawingVersion [{$drawingVersion->id}] with status [{$overallStatus}].");

            return response()->json([
                'status' => 'success',
                'type' => 'drawing_version',
                'drawing_version_id' => $drawingVersion->id,
                'overall_status' => $overallStatus,
            ]);
        }

        // 2. Check if this webhook belongs to a Standalone Quick Audit
        $quickAudit = QuickAudit::find($id);

        if ($quickAudit) {
            $status = $isCompleted ? 'completed' : 'failed';
            $errorMessage = $overallStatus === 'REJECTED_PREFLIGHT'
                ? ($validated['preflight_metrics']['reason'] ?? 'Rejected during preflight quality check.')
                : null;

            $quickArea = isset($validated['floor_area_metrics']['gross_floor_area_sqm'])
                ? (float) $validated['floor_area_metrics']['gross_floor_area_sqm']
                : null;

            $quickFee = ($quickArea !== null && $quickArea > 0)
                ? FeeCalculationService::calculateFee($quickArea, $quickAudit->occupancy_class)
                : 0.00;

            $quickAudit->update([
                'status' => $status,
                'error_message' => $errorMessage,
                'gross_floor_area_sqm' => $quickArea,
                'estimated_statutory_fee_ugx' => $quickFee,
                'preflight_metrics' => $validated['preflight_metrics'] ?? [],
                'extracted_geometry' => $validated['extracted_geometry'] ?? [],
                'floor_area_metrics' => $validated['floor_area_metrics'] ?? null,
                'discrepancies' => $validated['discrepancies'] ?? [],
                'summary_metrics' => $validated['summary_metrics'] ?? [
                    'pass_count' => $overallStatus === 'PASS' ? 1 : 0,
                    'fail_count' => $overallStatus === 'FAIL' || $overallStatus === 'REJECTED_PREFLIGHT' ? 1 : 0,
                    'warning_count' => $overallStatus === 'WARNING' ? 1 : 0,
                ],
                'overall_status' => $overallStatus,
                'analyzed_at' => now(),
            ]);

            Log::info("Compliance result webhook processed for QuickAudit [{$quickAudit->id}] with status [{$overallStatus}].");

            return response()->json([
                'status' => 'success',
                'type' => 'quick_audit',
                'quick_audit_id' => $quickAudit->id,
                'overall_status' => $overallStatus,
                'gross_floor_area_sqm' => $quickArea,
                'estimated_statutory_fee_ugx' => $quickFee,
            ]);
        }

        Log::error("Entity ID [{$id}] not found in drawing_versions or quick_audits for webhook.");

        return response()->json(['error' => 'Audit or drawing version not found'], 404);
    }
}
