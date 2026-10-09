<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Block;
use App\Models\Floor;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        ]);

        $block = $project->blocks()->create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Block created successfully',
            'data' => $block,
        ], 201);
    }

    /**
     * Create a single floor in a block.
     */
    public function storeFloor(Request $request, Block $block): JsonResponse
    {
        $validated = $request->validate([
            'floor_number' => ['required', 'integer'],
            'name' => ['nullable', 'string', 'max:100'],
        ]);

        $existing = Floor::where('block_id', $block->id)
            ->where('floor_number', $validated['floor_number'])
            ->first();

        if ($existing) {
            return response()->json([
                'status' => 'error',
                'message' => "Floor number {$validated['floor_number']} already exists in this block.",
            ], 422);
        }

        $floor = $block->floors()->create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Floor created successfully',
            'data' => $floor,
        ], 201);
    }

    /**
     * Batch generate multiple floors in a block (e.g., from -1 to 5).
     */
    public function batchCreateFloors(Request $request, Block $block): JsonResponse
    {
        $validated = $request->validate([
            'start_floor' => ['required', 'integer', 'min:-5', 'max:100'],
            'end_floor' => ['required', 'integer', 'gte:start_floor', 'max:100'],
        ]);

        $createdFloors = [];

        DB::transaction(function () use ($block, $validated, &$createdFloors): void {
            for ($num = $validated['start_floor']; $num <= $validated['end_floor']; $num++) {
                $name = match (true) {
                    $num < 0 => 'Basement Level '.abs($num),
                    $num === 0 => 'Ground Floor',
                    $num === 1 => 'First Floor',
                    $num === 2 => 'Second Floor',
                    default => "Floor {$num}",
                };

                $floor = Floor::firstOrCreate(
                    ['block_id' => $block->id, 'floor_number' => $num],
                    ['name' => $name]
                );

                $createdFloors[] = $floor;
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Floors batch created successfully',
            'data' => $createdFloors,
        ], 201);
    }

    /**
     * Delete a floor.
     */
    public function destroyFloor(Floor $floor): JsonResponse
    {
        $floor->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Floor deleted successfully',
        ]);
    }
}
