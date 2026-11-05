<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\FeeCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Generate consolidated compliance audit report for an entire project across all disciplines.
     */
    public function projectReport(Request $request, Project $project): JsonResponse
    {
        $project->load([
            'owner:id,name,email,bims_id',
            'blocks.drawings.currentVersion.complianceResult',
        ]);

        $totalDrawings = 0;
        $processedDrawings = 0;
        $totalPass = 0;
        $totalWarning = 0;
        $totalFail = 0;
        $allDiscrepancies = [];
        $disciplineSummaries = [
            'architectural' => ['total' => 0, 'passed' => 0, 'failed' => 0, 'status' => 'PENDING'],
            'structural' => ['total' => 0, 'passed' => 0, 'failed' => 0, 'status' => 'PENDING'],
            'mechanical' => ['total' => 0, 'passed' => 0, 'failed' => 0, 'status' => 'PENDING'],
            'electrical' => ['total' => 0, 'passed' => 0, 'failed' => 0, 'status' => 'PENDING'],
        ];

        foreach ($project->blocks as $block) {
            foreach ($block->drawings as $drawing) {
                $totalDrawings++;
                $discipline = strtolower($drawing->discipline);
                if (isset($disciplineSummaries[$discipline])) {
                    $disciplineSummaries[$discipline]['total']++;
                }

                $currentVersion = $drawing->currentVersion;
                if ($currentVersion && $currentVersion->complianceResult) {
                    $processedDrawings++;
                    $result = $currentVersion->complianceResult;
                    $status = $result->overall_status;

                    if ($status === 'PASS') {
                        $totalPass++;
                        if (isset($disciplineSummaries[$discipline])) {
                            $disciplineSummaries[$discipline]['passed']++;
                        }
                    } elseif ($status === 'WARNING') {
                        $totalWarning++;
                    } else {
                        $totalFail++;
                        if (isset($disciplineSummaries[$discipline])) {
                            $disciplineSummaries[$discipline]['failed']++;
                        }
                    }

                    $discrepancies = $result->discrepancies ?? [];
                    foreach ($discrepancies as $d) {
                        $allDiscrepancies[] = array_merge($d, [
                            'block' => $block->name,
                            'floor' => $d['floor'] ?? $d['floor_name'] ?? null,
                            'discipline' => $drawing->discipline,
                            'version' => "v{$currentVersion->version_number}",
                        ]);
                    }
                }
            }
        }

        // Compute discipline statuses
        foreach ($disciplineSummaries as $disc => &$data) {
            if ($data['total'] === 0) {
                $data['status'] = 'NOT_SUBMITTED';
            } elseif ($data['failed'] > 0) {
                $data['status'] = 'NON_COMPLIANT';
            } elseif ($data['passed'] === $data['total']) {
                $data['status'] = 'COMPLIANT';
            } else {
                $data['status'] = 'IN_REVIEW';
            }
        }

        $overallProjectStatus = match (true) {
            $totalDrawings === 0 => 'NO_SUBMISSIONS',
            $totalFail > 0 => 'NON_COMPLIANT',
            $processedDrawings === $totalDrawings && $totalFail === 0 => 'COMPLIANT',
            default => 'IN_PROGRESS',
        };

        $blockFloorAreas = [];
        foreach ($project->blocks as $block) {
            $floorsData = [];
            $rawFloors = is_array($block->floors) ? $block->floors : [];
            foreach ($rawFloors as $index => $floor) {
                if (! is_array($floor)) {
                    continue;
                }

                $floorNumber = isset($floor['floor_number']) ? (int) $floor['floor_number'] : $index;
                $floorName = $floor['floor_name'] ?? $floor['name'] ?? "Floor {$floorNumber}";

                $floorsData[] = array_merge($floor, [
                    'floor_number' => $floorNumber,
                    'floor_name' => $floorName,
                    'name' => $floorName,
                    'gross_floor_area_sqm' => (float) ($floor['gross_floor_area_sqm'] ?? 0.0),
                ]);
            }
            $blockFloorAreas[] = [
                'block_id' => $block->id,
                'name' => $block->name,
                'total_floor_area_sqm' => (float) ($block->total_floor_area_sqm ?? 0.0),
                'floors' => $floorsData,
            ];
        }

        $feeRate = $project->fee_rate_per_sqm_ugx ?? FeeCalculationService::getRatePerSqm($project->occupancy_class);

        return response()->json([
            'status' => 'success',
            'data' => [
                'project_id' => $project->id,
                'project_name' => $project->name,
                'reference_number' => $project->reference_number,
                'occupancy_class' => $project->occupancy_class,
                'owner' => $project->owner,
                'overall_compliance_status' => $overallProjectStatus,
                'floor_area_analysis' => [
                    'total_project_floor_area_sqm' => (float) ($project->total_floor_area_sqm ?? 0.0),
                    'statutory_fee_rate_per_sqm_ugx' => (float) $feeRate,
                    'estimated_statutory_fee_ugx' => (float) ($project->estimated_statutory_fee_ugx ?? 0.0),
                    'currency' => 'UGX',
                    'blocks' => $blockFloorAreas,
                ],
                'audit_summary' => [
                    'total_drawings' => $totalDrawings,
                    'processed_drawings' => $processedDrawings,
                    'pass_count' => $totalPass,
                    'warning_count' => $totalWarning,
                    'fail_count' => $totalFail,
                ],
                'discipline_breakdown' => $disciplineSummaries,
                'discrepancies' => $allDiscrepancies,
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }
}
