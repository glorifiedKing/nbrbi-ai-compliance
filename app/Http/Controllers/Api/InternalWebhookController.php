<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ComplianceResult;
use App\Models\Drawing;
use App\Models\DrawingVersion;
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
        ]);

        $drawingVersion = DrawingVersion::with('drawing.floor.block.project')->find($validated['drawing_version_id']);

        if (! $drawingVersion) {
            Log::error("Drawing version [{$validated['drawing_version_id']}] not found for webhook.");

            return response()->json(['error' => 'Drawing version not found'], 404);
        }

        $overallStatus = strtoupper((string) $validated['overall_status']);
        $isCompleted = in_array($overallStatus, ['PASS', 'WARNING', 'FAIL'], true);

        DB::transaction(function () use ($drawingVersion, $validated, $overallStatus, $isCompleted): void {
            // Update or create compliance result
            ComplianceResult::updateOrCreate(
                ['drawing_version_id' => $drawingVersion->id],
                [
                    'preflight_metrics' => $validated['preflight_metrics'] ?? [],
                    'extracted_geometry' => $validated['extracted_geometry'] ?? [],
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

            // Update drawing version status
            $status = $isCompleted ? 'completed' : 'failed';
            $errorMessage = $overallStatus === 'REJECTED_PREFLIGHT'
                ? ($validated['preflight_metrics']['reason'] ?? 'Rejected during preflight quality check.')
                : null;

            $drawingVersion->update([
                'status' => $status,
                'error_message' => $errorMessage,
            ]);

            // If completed, promote as current version of the drawing
            if ($isCompleted && $drawingVersion->drawing_id) {
                Drawing::where('id', $drawingVersion->drawing_id)->update([
                    'current_version_id' => $drawingVersion->id,
                ]);
            }
        });

        Log::info("Compliance result webhook processed successfully for drawing version [{$drawingVersion->id}] with status [{$overallStatus}].");

        return response()->json([
            'status' => 'success',
            'drawing_version_id' => $drawingVersion->id,
            'overall_status' => $overallStatus,
        ]);
    }
}
