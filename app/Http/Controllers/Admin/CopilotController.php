<?php

namespace App\Http\Controllers\Admin;

use App\Ai\Agents\UgandaBuildingRegulationsAgent;
use App\Http\Controllers\Controller;
use App\Models\DrawingVersion;
use App\Models\RegulatoryChunk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Ai\Enums\Lab;

class CopilotController extends Controller
{
    /**
     * Persona human-readable designations.
     *
     * @var array<string, string>
     */
    protected array $personaNames = [
        'inspector_okello' => 'Inspector Okello (Chief Building Control Officer)',
        'architect_namubiru' => 'Arch. Namubiru (Chartered Architect)',
        'engineer_kigozi' => 'Eng. Kigozi (Senior Structural Engineer)',
    ];

    /**
     * Handle the incoming copilot query for the administrative dashboard.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'max:2000'],
            'persona' => ['nullable', 'string', 'in:engineer_kigozi,architect_namubiru,inspector_okello'],
            'drawing_version_id' => ['nullable', 'uuid', 'exists:drawing_versions,id'],
        ]);

        $question = trim($validated['query']);
        $persona = $validated['persona'] ?? 'inspector_okello';
        $drawingVersionId = $validated['drawing_version_id'] ?? null;
        $personaName = $this->personaNames[$persona] ?? 'Inspector Okello';

        $responseText = null;
        $referencedClauses = [];

        // 1. Attempt LLM generation using the Uganda Building Regulations Agent
        try {
            $agent = new UgandaBuildingRegulationsAgent(persona: $persona);

            $response = $agent->prompt(
                $question,
                provider: [
                    Lab::Gemini->value => 'gemini-3-flash-preview'
                ],
            );

            if (! empty($response?->text)) {
                $responseText = trim($response->text);
            }
        } catch (\Throwable $e) {
            Log::warning('UgandaBuildingRegulationsAgent execution in Admin Copilot failed: '.$e->getMessage());
        }

        // 2. Resilient statutory fallback grounded in ingested building codes
        if (empty($responseText)) {
            $fallback = $this->generateStatutoryFallback($question, $persona, $drawingVersionId);
            $responseText = $fallback['response'];
            $referencedClauses = $fallback['referenced_clauses'];
        }

        Log::info('Admin Copilot query resolved', [
            'persona' => $persona,
            'query' => $question,
            'response_length' => strlen($responseText ?? ''),
            'clauses_count' => count($referencedClauses),
        ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'persona' => $persona,
                'persona_name' => $personaName,
                'query' => $question,
                'response' => $responseText,
                'referenced_clauses' => $referencedClauses,
            ],
        ]);
    }

    /**
     * Generate grounded statutory response based on keyword/database search.
     *
     * @return array{response: string, referenced_clauses: array<int, array<string, mixed>>}
     */
    protected function generateStatutoryFallback(string $query, string $persona, ?string $drawingVersionId): array
    {
        $keywords = explode(' ', preg_replace('/[^\w\s]/', '', strtolower($query)));
        $keywords = array_values(array_filter($keywords, fn ($w) => strlen($w) > 3));

        $regulatoryQuery = RegulatoryChunk::with('document');

        $like = DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'like';

        if (! empty($keywords)) {
            $regulatoryQuery->where(function ($q) use ($keywords, $like): void {
                foreach (array_slice($keywords, 0, 5) as $keyword) {
                    $q->orWhere('content', $like, "%{$keyword}%")
                        ->orWhere('clause_title', $like, "%{$keyword}%")
                        ->orWhere('clause_number', $like, "%{$keyword}%");
                }
            });
        }

        $relevantChunks = collect();

        try {
            $relevantChunks = $regulatoryQuery->limit(4)->get();
        } catch (\Throwable $e) {
            Log::warning('RegulatoryChunk query error in Admin Copilot: '.$e->getMessage());
        }

        $referencedClauses = $relevantChunks->map(fn (RegulatoryChunk $chunk) => [
            'document' => $chunk->document?->title ?? 'Uganda National Building Code',
            'clause_number' => $chunk->clause_number,
            'clause_title' => $chunk->clause_title,
            'snippet' => Str::limit($chunk->content, 260),
        ])->values()->all();

        // Optional drawing contextual metadata
        $drawingContext = '';
        if ($drawingVersionId) {
            try {
                $version = DrawingVersion::with(['drawing.floor.block.project', 'complianceResult'])->find($drawingVersionId);
                if ($version) {
                    $drawingContext = "\n\n> **Drawing Context:** {$version->drawing?->discipline} drawing on floor '{$version->drawing?->floor?->name}', Project: {$version->drawing?->floor?->block?->project?->name}.";
                    if ($version->complianceResult) {
                        $drawingContext .= " Current audit status: **{$version->complianceResult->overall_status}**.";
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('DrawingVersion lookup error in Admin Copilot: '.$e->getMessage());
            }
        }

        if ($relevantChunks->isNotEmpty()) {
            $provisionsList = '';
            foreach ($relevantChunks as $idx => $chunk) {
                $num = $idx + 1;
                $docTitle = $chunk->document?->title ?? 'Uganda Building Code';
                $provisionsList .= "{$num}. **{$chunk->clause_number} — {$chunk->clause_title}** ({$docTitle}):\n";
                $provisionsList .= '   > "'.trim($chunk->content)."\"\n\n";
            }

            $response = match ($persona) {
                'engineer_kigozi' => "### Structural & Engineering Compliance Review\n\n".
                    "Based on the Ugandan building engineering standards and structural statutory provisions:\n\n".
                    $provisionsList.
                    "**Engineering Assessment:** All structural calculations, reinforcement ratios, and site execution parameters must rigorously comply with these cited clauses to guarantee structural integrity and public safety.{$drawingContext}\n\n".
                    '— *Eng. Kigozi (Senior Structural & Site Engineer, NBRB BIMS)*',

                'architect_namubiru' => "### Architectural Design & Ergonomic Review\n\n".
                    "From an architectural standpoint (natural light, ventilation, and spatial circulation):\n\n".
                    $provisionsList.
                    "**Architectural Directive:** Ensure your spatial layout incorporates required window openings, headroom clearances, and accessibility pathways (PWD standards) specified in these regulations.{$drawingContext}\n\n".
                    '— *Arch. Namubiru (Chartered Architect, NBRB BIMS)*',

                default => "### Official Statutory Regulatory Ruling\n\n".
                    "Under the National Building Review Board (NBRB) mandate and the Uganda Building Control Regulations:\n\n".
                    $provisionsList.
                    "**Statutory Ruling:** Plan approval requires full verifiable compliance with the above cited statutory provisions. Failure to provide adequate documentation will result in plan rejection under NBRB regulations.{$drawingContext}\n\n".
                    '— *Inspector Okello (Chief Building Control Officer, NBRB Uganda)*',
            };
        } else {
            $response = match ($persona) {
                'engineer_kigozi' => "### Structural Engineering Standards Notice\n\n".
                    "Under the **Uganda National Building Code (Structural & Geotechnical Standards)**, all load-bearing elements, foundation designs, and structural safety margins must conform to statutory engineering thresholds. Verify that reinforcement details, concrete mix strengths (Class 25 minimum for structural elements), and site test reports are appended to your submission.{$drawingContext}\n\n".
                    '— *Eng. Kigozi (Senior Structural Engineer)*',

                'architect_namubiru' => "### Architectural Compliance Standard Notice\n\n".
                    "Under the **Uganda Building Control Regulations (2020)**, all habitable rooms require natural ventilation and natural lighting openings equal to at least **10% of the net floor area**. Furthermore, barrier-free access ramps must not exceed a slope of 1:12 for universal PWD accessibility.{$drawingContext}\n\n".
                    '— *Arch. Namubiru (Chartered Architect)*',

                default => "### Statutory Compliance Directive\n\n".
                    "Under the **Uganda National Building Code and Building Control Regulations (2020)**, all designs submitted to the National Building Review Board must strictly conform to statutory occupancy schedules, dimensional standards, and fire protection bylaws. Please specify the clause or topic you would like me to review in detail.{$drawingContext}\n\n".
                    '— *Inspector Okello (Chief Building Control Officer)*',
            };
        }

        return [
            'response' => $response,
            'referenced_clauses' => $referencedClauses,
        ];
    }
}
