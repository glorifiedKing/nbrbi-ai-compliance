<?php

namespace App\Jobs;

use App\Models\DrawingVersion;
use App\Services\FastApiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessDrawingJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public DrawingVersion $drawingVersion
    ) {}

    /**
     * Execute the job.
     */
    public function handle(FastApiService $fastApiService): void
    {
        Log::info("Starting compliance analysis job for drawing version: {$this->drawingVersion->id}");

        $this->drawingVersion->loadMissing(['drawing.floor.block.project']);

        $drawing = $this->drawingVersion->drawing;
        $project = $drawing?->floor?->block?->project;

        $discipline = $drawing->discipline ?? 'architectural';
        $occupancyClass = $project->occupancy_class ?? 'Class A (Residential)';

        $this->drawingVersion->update([
            'status' => 'analyzing',
            'error_message' => null,
        ]);

        $disk = config('filesystems.default', 'local');
        $absolutePath = Storage::disk($disk)->path($this->drawingVersion->file_name);

        if (! file_exists($absolutePath)) {
            $publicPath = public_path($this->drawingVersion->file_name);
            if (file_exists($publicPath)) {
                $absolutePath = $publicPath;
            }
        }

        try {
            $result = $fastApiService->analyzeDrawing(
                version: $this->drawingVersion,
                absoluteFilePath: $absolutePath,
                discipline: $discipline,
                occupancyClass: $occupancyClass
            );

            $taskId = $result['task_id'] ?? null;

            $this->drawingVersion->update([
                'batch_job_id' => $taskId,
            ]);

            Log::info("Dispatched drawing version [{$this->drawingVersion->id}] to FastAPI queue with task ID [{$taskId}]");
        } catch (Throwable $e) {
            Log::error("Failed to trigger analysis for drawing version [{$this->drawingVersion->id}]: {$e->getMessage()}");

            $this->drawingVersion->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
