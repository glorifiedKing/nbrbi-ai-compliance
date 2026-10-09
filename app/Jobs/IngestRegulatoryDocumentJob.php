<?php

namespace App\Jobs;

use App\Models\RegulatoryDocument;
use App\Services\FastApiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class IngestRegulatoryDocumentJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 180;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public RegulatoryDocument $document
    ) {}

    /**
     * Execute the job.
     */
    public function handle(FastApiService $fastApiService): void
    {
        Log::info("Starting ingestion job for regulatory document: {$this->document->id}");

        $this->document->update([
            'status' => 'processing',
            'error_message' => null,
        ]);

        $disk = config('filesystems.default', 'local');
        $absolutePath = Storage::disk($disk)->path($this->document->file_path);

        if (! file_exists($absolutePath)) {
            // Also check public disk fallback if stored under storage/app/public or public path
            $publicPath = public_path($this->document->file_path);
            if (file_exists($publicPath)) {
                $absolutePath = $publicPath;
            }
        }

        try {
            $result = $fastApiService->ingestRegulatoryDocument($this->document, $absolutePath);

            $totalChunks = (int) ($result['total_chunks_vectorized'] ?? 0);

            $this->document->update([
                'status' => 'indexed',
                'total_chunks' => $totalChunks,
                'error_message' => null,
            ]);

            Log::info("Successfully indexed regulatory document [{$this->document->id}] with {$totalChunks} chunks.");
        } catch (Throwable $e) {
            Log::error("Failed to ingest regulatory document [{$this->document->id}]: {$e->getMessage()}");

            $this->document->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
