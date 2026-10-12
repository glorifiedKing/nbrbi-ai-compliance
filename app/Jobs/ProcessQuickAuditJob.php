<?php

namespace App\Jobs;

use App\Models\QuickAudit;
use App\Services\FastApiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessQuickAuditJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public QuickAudit $quickAudit
    ) {}

    /**
     * Execute the job.
     */
    public function handle(FastApiService $fastApiService): void
    {
        Log::info("Starting quick compliance analysis job for audit: {$this->quickAudit->id}");

        $this->quickAudit->update([
            'status' => 'analyzing',
            'error_message' => null,
        ]);

        $disk = config('filesystems.default', 'local');
        $absolutePath = Storage::disk($disk)->path($this->quickAudit->file_name);

        if (! file_exists($absolutePath)) {
            $publicPath = public_path($this->quickAudit->file_name);
            if (file_exists($publicPath)) {
                $absolutePath = $publicPath;
            }
        }

        try {
            $result = $fastApiService->analyzeDrawing(
                versionOrAudit: $this->quickAudit,
                absoluteFilePath: $absolutePath,
                discipline: $this->quickAudit->discipline,
                occupancyClass: $this->quickAudit->occupancy_class,
                drawingId: null
            );

            $taskId = $result['task_id'] ?? null;

            $this->quickAudit->update([
                'batch_job_id' => $taskId,
            ]);

            Log::info("Dispatched quick audit [{$this->quickAudit->id}] to FastAPI queue with task ID [{$taskId}]");
        } catch (Throwable $e) {
            Log::error("Failed to trigger quick analysis for audit [{$this->quickAudit->id}]: {$e->getMessage()}");

            $this->quickAudit->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
