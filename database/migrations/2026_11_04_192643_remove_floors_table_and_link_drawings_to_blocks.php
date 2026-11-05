<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('blocks', 'floors')) {
            Schema::table('blocks', function (Blueprint $table): void {
                $table->jsonb('floors')->nullable()->after('description');
            });
        }

        if (! Schema::hasColumn('drawings', 'block_id')) {
            Schema::table('drawings', function (Blueprint $table): void {
                $table->uuid('block_id')->nullable()->after('id');
            });
        }

        if (Schema::hasTable('floors')) {
            $floorsByBlock = DB::table('floors')
                ->orderBy('floor_number')
                ->get()
                ->groupBy('block_id');

            foreach ($floorsByBlock as $blockId => $floors) {
                $floorDetails = $floors->map(fn ($floor): array => [
                    'floor_number' => (int) $floor->floor_number,
                    'floor_name' => $floor->name ?? "Floor {$floor->floor_number}",
                    'name' => $floor->name ?? "Floor {$floor->floor_number}",
                    'gross_floor_area_sqm' => $floor->gross_floor_area_sqm !== null ? (float) $floor->gross_floor_area_sqm : 0.0,
                ])->values()->all();

                DB::table('blocks')
                    ->where('id', $blockId)
                    ->update(['floors' => json_encode($floorDetails)]);
            }

            if (Schema::hasColumn('drawings', 'floor_id')) {
                $drawings = DB::table('drawings')
                    ->join('floors', 'drawings.floor_id', '=', 'floors.id')
                    ->select('drawings.id', 'drawings.discipline', 'floors.block_id', 'drawings.updated_at')
                    ->orderByDesc('drawings.updated_at')
                    ->get();

                $seenBlockDiscipline = [];

                foreach ($drawings as $drawing) {
                    $key = "{$drawing->block_id}:{$drawing->discipline}";

                    if (isset($seenBlockDiscipline[$key])) {
                        DB::table('drawings')->where('id', $drawing->id)->delete();

                        continue;
                    }

                    $seenBlockDiscipline[$key] = true;

                    DB::table('drawings')
                        ->where('id', $drawing->id)
                        ->update(['block_id' => $drawing->block_id]);
                }
            }
        }

        if (Schema::hasColumn('drawings', 'floor_id')) {
            Schema::table('drawings', function (Blueprint $table): void {
                $table->dropUnique(['floor_id', 'discipline']);
                $table->dropForeign(['floor_id']);
                $table->dropColumn('floor_id');
            });
        }

        DB::table('drawings')->whereNull('block_id')->delete();

        Schema::table('drawings', function (Blueprint $table): void {
            $table->uuid('block_id')->nullable(false)->change();
            $table->foreign('block_id')->references('id')->on('blocks')->cascadeOnDelete();
            $table->unique(['block_id', 'discipline']);
        });

        Schema::dropIfExists('floors');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('floors')) {
            Schema::create('floors', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('block_id')->constrained()->cascadeOnDelete();
                $table->integer('floor_number');
                $table->string('name')->nullable();
                $table->decimal('gross_floor_area_sqm', 10, 2)->nullable();
                $table->timestamps();

                $table->unique(['block_id', 'floor_number']);
            });
        }

        Schema::table('drawings', function (Blueprint $table): void {
            $table->uuid('floor_id')->nullable()->after('id');
        });

        $blocks = DB::table('blocks')->get();
        foreach ($blocks as $block) {
            $floors = json_decode((string) ($block->floors ?? '[]'), true) ?: [];
            if (empty($floors)) {
                $floors = [['floor_number' => 0, 'name' => 'Ground Floor', 'gross_floor_area_sqm' => $block->total_floor_area_sqm ?? 0]];
            }

            $firstFloorId = null;
            foreach ($floors as $index => $floor) {
                $floorId = (string) Str::uuid();
                if ($firstFloorId === null) {
                    $firstFloorId = $floorId;
                }

                DB::table('floors')->insert([
                    'id' => $floorId,
                    'block_id' => $block->id,
                    'floor_number' => (int) ($floor['floor_number'] ?? $index),
                    'name' => $floor['floor_name'] ?? $floor['name'] ?? "Floor {$index}",
                    'gross_floor_area_sqm' => isset($floor['gross_floor_area_sqm']) ? (float) $floor['gross_floor_area_sqm'] : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($firstFloorId) {
                DB::table('drawings')
                    ->where('block_id', $block->id)
                    ->update(['floor_id' => $firstFloorId]);
            }
        }

        Schema::table('drawings', function (Blueprint $table): void {
            $table->dropUnique(['block_id', 'discipline']);
            $table->dropForeign(['block_id']);
            $table->dropColumn('block_id');
            $table->uuid('floor_id')->nullable(false)->change();
            $table->foreign('floor_id')->references('id')->on('floors')->cascadeOnDelete();
            $table->unique(['floor_id', 'discipline']);
        });

        if (Schema::hasColumn('blocks', 'floors')) {
            Schema::table('blocks', function (Blueprint $table): void {
                $table->dropColumn('floors');
            });
        }
    }
};
