<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DrawingVersion;
use App\Models\RegulatoryChunk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CopilotController extends Controller
{
    /**
     * Ask Uganda Building Code questions to the interactive floating AI copilot.
     */
    public function ask(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'max:1000'],
            'persona' => ['nullable', 'string', 'in:engineer_kigozi,architect_namubiru,inspector_okello'],
            'drawing_version_id' => ['nullable', 'uuid', 'exists:drawing_versions,id'],
        ]);

        $query = $validated['query'];
        $persona = $validated['persona'] ?? 'inspector_okello';

        // 1. Fetch relevant statutory clauses from regulatory_chunks via keyword search
        $keywords = explode(' ', preg_replace('/[^\w\s]/', '', strtolower($query)));
        $keywords = array_filter($keywords, fn ($w) => strlen($w) > 3);

        $regulatoryQuery = RegulatoryChunk::with('document');

        if (! empty($keywords)) {
            $regulatoryQuery->where(function ($q) use ($keywords): void {
                foreach (array_slice($keywords, 0, 4) as $keyword) {
                    $q->orWhere('content', 'ILIKE', "%{$keyword}%")
                        ->orWhere('clause_title', 'ILIKE', "%{$keyword}%");
                }
            });
        }

        $relevantChunks = $regulatoryQuery->limit(4)->get();

        $statutoryContext = '';
        foreach ($relevantChunks as $chunk) {
            $docTitle = $chunk->document?->title ?? 'Uganda Building Code';
            $statutoryContext .= "[{$docTitle} - {$chunk->clause_number} ({$chunk->clause_title})]:\n{$chunk->content}\n\n";
        }

        // 2. Check if drawing context was passed
        $drawingContext = '';
        if (! empty($validated['drawing_version_id'])) {
            $version = DrawingVersion::with(['drawing.floor.block.project', 'complianceResult'])->find($validated['drawing_version_id']);
            if ($version) {
                $drawingContext = "DRAWING CONTEXT: {$version->drawing?->discipline} drawing on {$version->drawing?->floor?->name}, project: {$version->drawing?->floor?->block?->project?->name}.\n";
                if ($version->complianceResult) {
                    $drawingContext .= 'Current Compliance Status: '.$version->complianceResult->overall_status."\n";
                }
            }
        }

        // Persona definitions
        $personaPrompts = [
            'engineer_kigozi' => 'You are Eng. Kigozi, a seasoned Ugandan Senior Structural & Site Engineer. You provide practical, safety-first, on-site engineering guidance while citing Uganda building codes clearly.',
            'architect_namubiru' => 'You are Architect Namubiru, a chartered Ugandan Architect specializing in spatial ergonomics, natural ventilation, lighting ratios, and accessibility (PWD) standards under the Uganda Building Control Regulations.',
            'inspector_okello' => 'You are Inspector Okello, a strict Chief Building Control Officer at NBRB Uganda. You give authoritative, precise regulatory rulings with exact clause and statutory instrument citations.',
        ];

        $personaInstruction = $personaPrompts[$persona] ?? $personaPrompts['inspector_okello'];

        $geminiApiKey = config('services.gemini.api_key') ?? env('GEMINI_API_KEY');

        $aiResponseText = null;

        if ($geminiApiKey) {
            try {
                $systemPrompt = "{$personaInstruction}\n\nUGANDAN STATUTORY CONTEXT:\n{$statutoryContext}\n{$drawingContext}\nAnswer the user query accurately and ground all recommendations in Uganda building regulations.";

                $response = Http::timeout(20)->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$geminiApiKey}", [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                ['text' => "{$systemPrompt}\n\nUser Question: {$query}"],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.2,
                        'maxOutputTokens' => 800,
                    ],
                ]);

                if ($response->successful()) {
                    $aiResponseText = $response->json('candidates.0.content.parts.0.text');
                }
            } catch (\Throwable $e) {
                Log::warning('Direct Gemini API call in CopilotController failed: '.$e->getMessage());
            }
        }

        // Fallback response if Gemini API key not configured or unreachable
        if (! $aiResponseText) {
            $personaName = match ($persona) {
                'engineer_kigozi' => 'Eng. Kigozi',
                'architect_namubiru' => 'Arch. Namubiru',
                default => 'Inspector Okello',
            };

            if ($relevantChunks->isNotEmpty()) {
                $topChunk = $relevantChunks->first();
                $aiResponseText = "According to {$topChunk->document?->title} ({$topChunk->clause_number} - {$topChunk->clause_title}):\n\n\"{$topChunk->content}\"\n\n— {$personaName} (NBRB BIMS Compliance Assistant)";
            } else {
                $aiResponseText = "Under the Uganda National Building Code and Building Control Regulations (2020), all design parameters must strictly conform to the statutory occupancy schedule and minimum dimensional standards. Please ensure the scale is explicitly verified on your CAD submission.\n\n— {$personaName}";
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'persona' => $persona,
                'query' => $query,
                'response' => $aiResponseText,
                'referenced_clauses' => $relevantChunks->map(fn ($c) => [
                    'document' => $c->document?->title,
                    'clause_number' => $c->clause_number,
                    'clause_title' => $c->clause_title,
                ]),
            ],
        ]);
    }
}
