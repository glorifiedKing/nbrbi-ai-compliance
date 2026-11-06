<?php

namespace App\Http\Controllers;

use App\Ai\Agents\UgandaBuildingRegulationsAgent;
use App\Models\CopilotWebsite;
use App\Models\RegulatoryChunk;
use App\Services\CopilotPersonaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Ai\Enums\Lab;

class EmbedCopilotController extends Controller
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
     * Serve the JavaScript embed script for an external website.
     * If the website does not exist or is disabled (`is_active === false`),
     * returns a no-op script so the chatbot does NOT load on the external site.
     */
    public function script(Request $request, ?string $site = null): Response
    {
        $headers = [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Access-Control-Allow-Origin' => '*',
        ];

        $identifier = trim((string) ($site ?? $request->query('site', '')));

        if ($identifier === '') {
            $baseEmbedUrl = url('/copilot/embed');
            $loaderJs = <<<JS
(function () {
    var current = document.currentScript || document.querySelector('script[data-site], script[data-website-id]');
    var siteId = current ? (current.getAttribute('data-site') || current.getAttribute('data-website-id') || '') : '';
    if (!siteId) {
        console.warn('[NBRB AI Copilot] Missing data-site attribute on embed script.');
        return;
    }
    var s = document.createElement('script');
    s.src = '{$baseEmbedUrl}/' + encodeURIComponent(siteId.trim()) + '.js';
    s.defer = true;
    document.head.appendChild(s);
})();
JS;

            return response($loaderJs, 200, $headers);
        }

        $website = CopilotWebsite::findByIdentifier($identifier);

        if (! $website || ! $website->is_active) {
            $statusReason = ! $website ? 'unregistered' : 'disabled';
            $disabledJs = <<<JS
(function () {
    /* NBRB Statutory AI Copilot: External website widget is {$statusReason} (site: "{$identifier}"). Chatbot will not load. */
    var existingHost = document.getElementById('nbrb-ext-copilot-host');
    if (existingHost && existingHost.parentNode) {
        existingHost.parentNode.removeChild(existingHost);
    }
})();
JS;

            return response($disabledJs, 200, $headers);
        }

        $website->update(['last_active_at' => now()]);

        $personas = CopilotPersonaService::getEnabled();
        $askUrl = route('api.v1.copilot.embed.ask', ['site' => $website->slug]);
        $baseUrl = url('/');

        $jsContent = view('embed.copilot-widget-js', [
            'website' => $website,
            'personas' => $personas,
            'askUrl' => $askUrl,
            'baseUrl' => $baseUrl,
        ])->render();

        return response($jsContent, 200, $headers);
    }

    /**
     * Handle CORS preflight OPTIONS request for external websites.
     */
    public function options(): Response
    {
        return response('', 204, $this->corsHeaders());
    }

    /**
     * Handle chat queries from an embedded external website widget.
     */
    public function ask(Request $request, string $site): JsonResponse
    {
        $website = CopilotWebsite::findByIdentifier($site);

        if (! $website || ! $website->is_active) {
            return response()->json([
                'status' => 'disabled',
                'message' => 'This external AI Copilot widget is currently disabled by the administrator.',
            ], 403, $this->corsHeaders());
        }

        $validated = $request->validate([
            'query' => ['required', 'string', 'max:2000'],
            'persona' => [
                'nullable',
                'string',
                Rule::in(array_keys(CopilotPersonaService::all()) ?: ['inspector_okello', 'architect_namubiru', 'engineer_kigozi']),
            ],
        ]);

        $website->increment('requests_count');
        $website->update(['last_active_at' => now()]);

        $question = trim($validated['query']);
        $requestedPersona = $validated['persona'] ?? null;
        $persona = ($requestedPersona && CopilotPersonaService::isEnabled($requestedPersona))
            ? $requestedPersona
            : CopilotPersonaService::getDefaultPersonaKey();
        $personaName = $this->personaNames[$persona] ?? 'Inspector Okello';

        $responseText = null;
        $referencedClauses = [];

        try {
            $agent = new UgandaBuildingRegulationsAgent(persona: $persona);

            $response = $agent->prompt(
                $question,
                provider: [
                    Lab::Gemini->value => 'gemini-3-flash-preview',
                ],
            );

            if (! empty($response?->text)) {
                $responseText = trim($response->text);
            }
        } catch (\Throwable $e) {
            Log::warning('External Embed Copilot LLM execution failed, using statutory fallback: '.$e->getMessage());
        }

        if (empty($responseText)) {
            $fallback = $this->generateStatutoryFallback($question, $persona);
            $responseText = $fallback['response'];
            $referencedClauses = $fallback['referenced_clauses'];
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'website' => $website->slug,
                'persona' => $persona,
                'persona_name' => $personaName,
                'query' => $question,
                'response' => $responseText,
                'referenced_clauses' => $referencedClauses,
            ],
        ], 200, $this->corsHeaders());
    }

    /**
     * Generate grounded statutory fallback response from indexed regulatory chunks.
     *
     * @return array{response: string, referenced_clauses: array<int, array<string, mixed>>}
     */
    protected function generateStatutoryFallback(string $query, string $persona): array
    {
        $keywords = explode(' ', preg_replace('/[^\w\s]/', '', strtolower($query)));
        $keywords = array_values(array_filter($keywords, fn ($w): bool => strlen($w) > 3));

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
            Log::warning('RegulatoryChunk lookup error in EmbedCopilotController: '.$e->getMessage());
        }

        $referencedClauses = $relevantChunks->map(fn (RegulatoryChunk $chunk): array => [
            'document' => $chunk->document?->title ?? 'Uganda National Building Code',
            'clause_number' => $chunk->clause_number,
            'clause_title' => $chunk->clause_title,
            'snippet' => Str::limit($chunk->content, 240),
        ])->values()->all();

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
                    "**Engineering Assessment:** All structural calculations, reinforcement ratios, and site execution parameters must rigorously comply with these cited clauses to guarantee structural integrity and public safety.\n\n".
                    '— *Eng. Kigozi (Senior Structural & Site Engineer, NBRB BIMS)*',

                'architect_namubiru' => "### Architectural Design & Ergonomic Review\n\n".
                    "From an architectural standpoint (natural light, ventilation, and spatial circulation):\n\n".
                    $provisionsList.
                    "**Architectural Directive:** Ensure your spatial layout incorporates required window openings, headroom clearances, and accessibility pathways (PWD standards) specified in these regulations.\n\n".
                    '— *Arch. Namubiru (Chartered Architect, NBRB BIMS)*',

                default => "### Official Statutory Regulatory Ruling\n\n".
                    "Under the National Building Review Board (NBRB) mandate and the Uganda Building Control Regulations:\n\n".
                    $provisionsList.
                    "**Statutory Ruling:** Plan approval requires full verifiable compliance with the above cited statutory provisions.\n\n".
                    '— *Inspector Okello (Chief Building Control Officer, NBRB Uganda)*',
            };
        } else {
            $response = match ($persona) {
                'engineer_kigozi' => "### Structural Engineering Standards Notice\n\n".
                    "Under the **Uganda National Building Code (Structural & Geotechnical Standards)**, all load-bearing elements, foundation designs, and structural safety margins must conform to statutory engineering thresholds.\n\n".
                    '— *Eng. Kigozi (Senior Structural Engineer)*',

                'architect_namubiru' => "### Architectural Compliance Standard Notice\n\n".
                    "Under the **Uganda Building Control Regulations (2020)**, all habitable rooms require natural ventilation and natural lighting openings equal to at least **10% of the net floor area**.\n\n".
                    '— *Arch. Namubiru (Chartered Architect)*',

                default => "### Statutory Compliance Directive\n\n".
                    "Under the **Uganda National Building Code and Building Control Regulations (2020)**, all designs submitted to the National Building Review Board must strictly conform to statutory occupancy schedules, dimensional standards, and fire protection bylaws.\n\n".
                    '— *Inspector Okello (Chief Building Control Officer)*',
            };
        }

        return [
            'response' => $response,
            'referenced_clauses' => $referencedClauses,
        ];
    }

    /**
     * Standard CORS headers for cross-origin embed widget requests.
     *
     * @return array<string, string>
     */
    protected function corsHeaders(): array
    {
        return [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Accept, X-Requested-With, Origin',
        ];
    }
}
