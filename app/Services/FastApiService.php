<?php

namespace App\Services;

use App\Models\DrawingVersion;
use App\Models\RegulatoryDocument;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class FastApiService
{
    protected string $baseUrl;

    protected string $secret;

    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.fastapi.base_url', 'http://127.0.0.1:8001'), '/');
        $this->secret = (string) config('services.fastapi.secret', 'bims-secure-internal-secret');
        $this->timeout = (int) config('services.fastapi.timeout', 60);
    }

    /**
     * Check if the FastAPI microservice is healthy and responding.
     */
    public function healthCheck(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/health");

            return $response->successful();
        } catch (\Throwable $e) {
            Log::warning('FastAPI health check failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Send a regulatory PDF standard to FastAPI for chunking, vector embedding, and pgvector indexing.
     *
     * @return array<string, mixed>
     */
    public function ingestRegulatoryDocument(RegulatoryDocument $document, string $absoluteFilePath): array
    {
        if (! file_exists($absoluteFilePath)) {
            throw new RuntimeException("Regulatory PDF file not found at path: {$absoluteFilePath}");
        }

        $url = "{$this->baseUrl}/api/v1/documents/ingest-standard";

        Log::info("Dispatching regulatory document [{$document->id}] to FastAPI: {$url}");

        $response = Http::timeout($this->timeout)
            ->withHeaders([
                'X-Internal-Secret' => $this->secret,
            ])
            ->attach(
                'file',
                file_get_contents($absoluteFilePath),
                basename($absoluteFilePath)
            )
            ->post($url, [
                'document_id' => $document->id,
            ]);

        if (! $response->successful()) {
            $errorMessage = $response->json('detail') ?? $response->body() ?? 'FastAPI ingestion error';
            Log::error("FastAPI regulatory ingestion failed [{$response->status()}]: {$errorMessage}");
            throw new RuntimeException("FastAPI ingestion failed: {$errorMessage}");
        }

        return $response->json();
    }

    /**
     * Trigger asynchronous compliance analysis for an uploaded drawing version.
     *
     * @return array<string, mixed>
     */
    public function analyzeDrawing(
        DrawingVersion $version,
        string $absoluteFilePath,
        string $discipline,
        ?string $occupancyClass = 'Class A (Residential)'
    ): array {
        $url = "{$this->baseUrl}/api/v1/analyze-drawing";

        Log::info("Triggering drawing analysis for version [{$version->id}] on FastAPI: {$url}");

        $payload = [
            'drawing_version_id' => $version->id,
            'drawing_id' => $version->drawing_id,
            'discipline' => strtolower($discipline),
            'file_path' => $absoluteFilePath,
            'occupancy_class' => $occupancyClass ?? 'Class A (Residential)',
        ];

        $response = Http::timeout(15)
            ->withHeaders([
                'X-Internal-Secret' => $this->secret,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->post($url, $payload);

        if (! $response->successful()) {
            $errorMessage = $response->json('detail') ?? $response->body() ?? 'FastAPI drawing analysis trigger failed';
            Log::error("FastAPI drawing analysis request failed [{$response->status()}]: {$errorMessage}");
            throw new RuntimeException("FastAPI analysis trigger failed: {$errorMessage}");
        }

        return $response->json();
    }
}
