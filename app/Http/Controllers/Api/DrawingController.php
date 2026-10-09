<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessDrawingJob;
use App\Models\Drawing;
use App\Models\DrawingVersion;
use App\Models\Floor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DrawingController extends Controller
{
    /**
     * Upload an engineering drawing PDF for a specific floor and discipline.
     * Automatically handles versioning (v1.0 -> v1.1) and triggers AI compliance check.
     */
    public function upload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'floor_id' => ['required', 'uuid', 'exists:floors,id'],
            'discipline' => ['required', 'string', 'in:architectural,structural,mechanical,electrical'],
            'drawing_file' => ['required', 'file', 'mimes:pdf', 'max:51200'], // max 50MB
        ]);

        $file = $request->file('drawing_file');
        $fileHash = hash_file('sha256', $file->getRealPath());
        $fileSize = $file->getSize();
        $originalName = $file->getClientOriginalName();
        $user = $request->user();

        $drawingVersion = DB::transaction(function () use ($validated, $file, $fileHash, $fileSize, $originalName, $user) {
            // Find or create the Drawing record for this floor & discipline
            $drawing = Drawing::firstOrCreate([
                'floor_id' => $validated['floor_id'],
                'discipline' => strtolower($validated['discipline']),
            ]);

            // Determine next incremental version number
            $latestVersion = DrawingVersion::where('drawing_id', $drawing->id)
                ->orderByDesc('version_number')
                ->first();

            $nextVersionNumber = $latestVersion ? ($latestVersion->version_number + 1) : 1;

            // Check if exact same file hash was already uploaded on the latest version
            if ($latestVersion && $latestVersion->file_hash === $fileHash && $latestVersion->status === 'completed') {
                return $latestVersion;
            }

            // Store file
            $storedPath = $file->storeAs(
                "drawings/{$drawing->id}",
                "v{$nextVersionNumber}_".time()."_{$originalName}",
                'local'
            );

            // Create new drawing version record
            $version = DrawingVersion::create([
                'drawing_id' => $drawing->id,
                'uploaded_by' => $user->id,
                'version_number' => $nextVersionNumber,
                'file_name' => $storedPath,
                'file_size_bytes' => $fileSize,
                'file_hash' => $fileHash,
                'status' => 'pending',
            ]);

            return $version;
        });

        // Dispatch background job to trigger FastAPI analysis
        if ($drawingVersion->status === 'pending') {
            ProcessDrawingJob::dispatch($drawingVersion);
        }

        $drawingVersion->load(['drawing.floor.block.project', 'uploader:id,name,email']);

        return response()->json([
            'status' => 'success',
            'message' => 'Drawing uploaded and queued for automated AI compliance assessment.',
            'data' => [
                'drawing_id' => $drawingVersion->drawing_id,
                'version_id' => $drawingVersion->id,
                'version_number' => $drawingVersion->version_number,
                'status' => $drawingVersion->status,
                'file_name' => $originalName,
                'discipline' => $drawingVersion->drawing->discipline,
            ],
        ], 202);
    }

    /**
     * Get version history for a drawing with compliance metrics.
     */
    public function versionHistory(Drawing $drawing): JsonResponse
    {
        $versions = $drawing->versions()
            ->with(['complianceResult', 'uploader:id,name,email'])
            ->orderByDesc('version_number')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'drawing_id' => $drawing->id,
                'discipline' => $drawing->discipline,
                'current_version_id' => $drawing->current_version_id,
                'versions' => $versions,
            ],
        ]);
    }

    /**
     * View detailed compliance analysis report for a specific drawing version.
     */
    public function complianceReport(DrawingVersion $version): JsonResponse
    {
        $version->load([
            'drawing.floor.block.project',
            'complianceResult',
            'uploader:id,name,email',
        ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'version_id' => $version->id,
                'version_number' => $version->version_number,
                'file_name' => $version->file_name,
                'status' => $version->status,
                'error_message' => $version->error_message,
                'discipline' => $version->drawing?->discipline,
                'floor' => $version->drawing?->floor?->name,
                'block' => $version->drawing?->floor?->block?->name,
                'project' => $version->drawing?->floor?->block?->project?->name,
                'compliance_result' => $version->complianceResult,
            ],
        ]);
    }
}
