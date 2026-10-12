<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessQuickAuditJob;
use App\Models\QuickAudit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuickAuditController extends Controller
{
    /**
     * Submit a single drawing document for rapid compliance analysis without project hierarchy.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'drawing_file' => ['required', 'file', 'mimes:pdf', 'max:51200'], // max 50MB
            'discipline' => ['required', 'string', 'in:architectural,structural,mechanical,electrical'],
            'occupancy_class' => ['nullable', 'string', 'max:100'],
        ]);

        $file = $request->file('drawing_file');
        $originalName = $file->getClientOriginalName();
        $fileSize = $file->getSize();
        $fileHash = hash_file('sha256', $file->getRealPath());
        $user = $request->user();

        $storedPath = $file->storeAs(
            'quick_audits',
            'quick_'.time().'_'.$originalName,
            'local'
        );

        $quickAudit = QuickAudit::create([
            'user_id' => $user?->id,
            'file_name' => $storedPath,
            'original_name' => $originalName,
            'file_size_bytes' => $fileSize,
            'file_hash' => $fileHash,
            'discipline' => strtolower($validated['discipline']),
            'occupancy_class' => $validated['occupancy_class'] ?? 'Class A (Residential)',
            'status' => 'pending',
        ]);

        // Dispatch background analysis job
        ProcessQuickAuditJob::dispatch($quickAudit);

        return response()->json([
            'status' => 'success',
            'message' => 'Single drawing document queued for quick AI compliance audit.',
            'data' => [
                'quick_audit_id' => $quickAudit->id,
                'status' => $quickAudit->status,
                'original_name' => $quickAudit->original_name,
                'discipline' => $quickAudit->discipline,
                'occupancy_class' => $quickAudit->occupancy_class,
                'created_at' => $quickAudit->created_at,
            ],
        ], 202);
    }

    /**
     * Get the live compliance status and full audit results for a quick audit.
     */
    public function show(QuickAudit $quickAudit): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'quick_audit_id' => $quickAudit->id,
                'status' => $quickAudit->status,
                'overall_status' => $quickAudit->overall_status,
                'original_name' => $quickAudit->original_name,
                'discipline' => $quickAudit->discipline,
                'occupancy_class' => $quickAudit->occupancy_class,
                'gross_floor_area_sqm' => (float) ($quickAudit->gross_floor_area_sqm ?? 0.0),
                'estimated_statutory_fee_ugx' => (float) ($quickAudit->estimated_statutory_fee_ugx ?? 0.0),
                'currency' => 'UGX',
                'error_message' => $quickAudit->error_message,
                'preflight_metrics' => $quickAudit->preflight_metrics,
                'extracted_geometry' => $quickAudit->extracted_geometry,
                'summary_metrics' => $quickAudit->summary_metrics,
                'discrepancies' => $quickAudit->discrepancies,
                'analyzed_at' => $quickAudit->analyzed_at,
                'created_at' => $quickAudit->created_at,
            ],
        ]);
    }

    /**
     * List user's recent quick audits.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;

        $audits = QuickAudit::when($userId, fn ($q) => $q->where('user_id', $userId))
            ->latest()
            ->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $audits,
        ]);
    }
}
