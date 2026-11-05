<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Block;
use App\Models\Project;
use App\Services\FeeCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlockFloorController extends Controller
{
    /**
     * Create a block for a project.
     */
    public function storeBlock(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'floors' => ['nullable', 'array'],
            'floors.*.floor_number' => ['required_with:floors', 'integer'],
            'floors.*.floor_name' => ['nullable', 'string', 'max:100'],
            'floors.*.name' => ['nullable', 'string', 'max:100'],
            'floors.*.gross_floor_area_sqm' => ['nullable', 'numeric', 'min:0'],
        ]);

        $floors = [];
        if (! empty($validated['floors']) && is_array($validated['floors'])) {
            foreach ($validated['floors'] as $index => $floor) {
                $floorNumber = (int) ($floor['floor_number'] ?? $index);
                $floorName = $floor['floor_name'] ?? $floor['name'] ?? "Floor {$floorNumber}";
                $floors[] = array_merge($floor, [
                    'floor_number' => $floorNumber,
                    'floor_name' => $floorName,
                    'name' => $floorName,
                    'gross_floor_area_sqm' => isset($floor['gross_floor_area_sqm']) ? (float) $floor['gross_floor_area_sqm'] : 0.0,
                ]);
            }
        }

        $block = $project->blocks()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'floors' => $floors,
        ]);

        if (! empty($floors)) {
            FeeCalculationService::recalculateProjectTotals($project);
            $block->refresh();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Block created successfully',
            'data' => $block,
        ], 201);
    }

    /**
     * Update an existing block's name, description, and floor details JSONB.
     */
    public function updateBlock(Request $request, Block $block): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'floors' => ['nullable', 'array'],
            'floors.*.floor_number' => ['nullable', 'integer'],
            'floors.*.floor_name' => ['nullable', 'string', 'max:100'],
            'floors.*.name' => ['nullable', 'string', 'max:100'],
            'floors.*.gross_floor_area_sqm' => ['nullable', 'numeric', 'min:0'],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ];

        if ($request->has('floors')) {
            $floors = [];
            if (is_array($validated['floors'])) {
                foreach ($validated['floors'] as $index => $floor) {
                    $floorName = trim((string) ($floor['floor_name'] ?? $floor['name'] ?? ''));
                    if ($floorName === '' && ! isset($floor['floor_number']) && empty($floor['gross_floor_area_sqm'])) {
                        continue;
                    }
                    $floorNumber = isset($floor['floor_number']) ? (int) $floor['floor_number'] : $index;
                    $resolvedName = $floorName !== '' ? $floorName : "Floor {$floorNumber}";
                    $floors[] = array_merge($floor, [
                        'floor_number' => $floorNumber,
                        'floor_name' => $resolvedName,
                        'name' => $resolvedName,
                        'gross_floor_area_sqm' => isset($floor['gross_floor_area_sqm']) ? (float) $floor['gross_floor_area_sqm'] : 0.0,
                    ]);
                }
                usort($floors, fn (array $a, array $b): int => ((int) ($a['floor_number'] ?? 0)) <=> ((int) ($b['floor_number'] ?? 0)));
            }
            $updateData['floors'] = $floors;
        }

        $block->update($updateData);

        if ($block->project) {
            FeeCalculationService::recalculateProjectTotals($block->project);
            $block->refresh();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Block updated successfully',
            'data' => $block,
        ]);
    }

    /**
     * Delete a block and recalculate project totals.
     */
    public function destroyBlock(Block $block): JsonResponse
    {
        $project = $block->project;
        $block->delete();

        if ($project) {
            FeeCalculationService::recalculateProjectTotals($project);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Block deleted successfully',
        ]);
    }

    /**
     * Add a single floor detail entry to a block's floors JSONB.
     */
    public function storeFloor(Request $request, Block $block): JsonResponse
    {
        $validated = $request->validate([
            'floor_number' => ['required', 'integer'],
            'floor_name' => ['nullable', 'string', 'max:100'],
            'name' => ['nullable', 'string', 'max:100'],
            'gross_floor_area_sqm' => ['nullable', 'numeric', 'min:0'],
        ]);

        $floors = is_array($block->floors) ? array_values($block->floors) : [];

        foreach ($floors as $existingFloor) {
            if (isset($existingFloor['floor_number']) && (int) $existingFloor['floor_number'] === (int) $validated['floor_number']) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Floor number {$validated['floor_number']} already exists in this block.",
                ], 422);
            }
        }

        $floorNumber = (int) $validated['floor_number'];
        $floorName = $validated['floor_name'] ?? $validated['name'] ?? "Floor {$floorNumber}";
        $newFloor = [
            'floor_number' => $floorNumber,
            'floor_name' => $floorName,
            'name' => $floorName,
            'gross_floor_area_sqm' => isset($validated['gross_floor_area_sqm']) ? (float) $validated['gross_floor_area_sqm'] : 0.0,
        ];

        $floors[] = $newFloor;
        usort($floors, fn (array $a, array $b): int => ((int) ($a['floor_number'] ?? 0)) <=> ((int) ($b['floor_number'] ?? 0)));

        $block->update(['floors' => $floors]);

        if ($block->project) {
            FeeCalculationService::recalculateProjectTotals($block->project);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Floor added to block successfully',
            'data' => $newFloor,
        ], 201);
    }

    /**
     * Batch generate multiple floor entries in a block's floors JSONB (e.g., from -1 to 5).
     */
    public function batchCreateFloors(Request $request, Block $block): JsonResponse
    {
        $validated = $request->validate([
            'start_floor' => ['required', 'integer', 'min:-5', 'max:100'],
            'end_floor' => ['required', 'integer', 'gte:start_floor', 'max:100'],
        ]);

        $floors = is_array($block->floors) ? array_values($block->floors) : [];
        $indexedByNumber = [];
        foreach ($floors as $f) {
            if (is_array($f) && isset($f['floor_number'])) {
                $indexedByNumber[(int) $f['floor_number']] = $f;
            }
        }

        $createdFloors = [];

        for ($num = $validated['start_floor']; $num <= $validated['end_floor']; $num++) {
            $name = match (true) {
                $num < 0 => 'Basement Level '.abs($num),
                $num === 0 => 'Ground Floor',
                $num === 1 => 'First Floor',
                $num === 2 => 'Second Floor',
                default => "Floor {$num}",
            };

            if (! isset($indexedByNumber[$num])) {
                $indexedByNumber[$num] = [
                    'floor_number' => $num,
                    'floor_name' => $name,
                    'name' => $name,
                    'gross_floor_area_sqm' => 0.0,
                ];
            }

            $createdFloors[] = $indexedByNumber[$num];
        }

        ksort($indexedByNumber);
        $block->update(['floors' => array_values($indexedByNumber)]);

        return response()->json([
            'status' => 'success',
            'message' => 'Floors batch created successfully',
            'data' => $createdFloors,
        ], 201);
    }

    /**
     * Remove a floor entry by floor number from a block's floors JSONB.
     */
    public function destroyFloor(Block $block, int $floorNumber): JsonResponse
    {
        $floors = is_array($block->floors) ? array_values($block->floors) : [];
        $filtered = array_values(array_filter(
            $floors,
            fn ($f): bool => ! (is_array($f) && isset($f['floor_number']) && (int) $f['floor_number'] === $floorNumber)
        ));

        $block->update(['floors' => $filtered]);

        if ($block->project) {
            FeeCalculationService::recalculateProjectTotals($block->project);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Floor deleted successfully',
        ]);
    }
}
