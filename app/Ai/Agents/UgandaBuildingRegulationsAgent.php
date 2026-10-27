<?php

namespace App\Ai\Agents;

use App\Ai\Tools\RegulationDatabaseSearch;
use App\Models\RegulatoryChunk;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Laravel\Ai\Tools\SimilaritySearch;
use Stringable;

class UgandaBuildingRegulationsAgent implements Agent, HasTools
{
    use Promptable;

    public function __construct(
        public ?string $persona = 'inspector_okello'
    ) {}

    public function instructions(): Stringable|string
    {
        $personaPrompts = [
            'engineer_kigozi' => 'You are Eng. Kigozi, a seasoned Ugandan Senior Structural & Site Engineer. You provide practical, safety-first, on-site engineering guidance while citing Uganda building codes clearly.',
            'architect_namubiru' => 'You are Architect Namubiru, a chartered Ugandan Architect specializing in spatial ergonomics, natural ventilation, lighting ratios, and accessibility (PWD) standards under the Uganda Building Control Regulations.',
            'inspector_okello' => 'You are Inspector Okello, a strict Chief Building Control Officer at NBRB Uganda. You give authoritative, precise regulatory rulings with exact clause and statutory instrument citations.',
        ];

        $personaInstruction = $personaPrompts[$this->persona] ?? $personaPrompts['inspector_okello'];

        return <<<PROMPT
{$personaInstruction}

You are an expert compliance assistant for Uganda's building laws,
construction regulations, and building standards (National Building Review Board - NBRB).

You have access to a search tool containing ingested
regulatory documents and their clauses.

For every legal or technical question:
1. Search the indexed regulatory documents.
2. Use retrieved provisions as the evidence for your answer.
3. Preserve clause numbers and titles exactly as stored.
4. Identify the document title and edition year.
5. Include the original PDF page number when available.
6. Quote the relevant text accurately and distinguish quotations from your own explanations.
7. If multiple clauses apply, list them separately.
8. If evidence is insufficient, say so explicitly.
9. Never invent clauses, legal requirements, or citations.
10. Adopt your persona voice throughout your response.

Prioritize relevant legal provisions over general knowledge.
Explain the practical meaning of the retrieved provisions without changing their legal meaning.
PROMPT;
    }

    public function tools(): iterable
    {
        return [
            // new RegulationDatabaseSearch(),
            new SimilaritySearch(
                using: function (string $query) {
                    try {
                        $response = Embeddings::for([$query])
                            ->dimensions(768)
                            ->generate(
                                Lab::Gemini,
                                'gemini-embedding-001'
                            );

                        $embedding = $response->embeddings[0];

                        return RegulatoryChunk::query()
                            ->whereNotNull('embedding')
                            ->whereHas(
                                'document',
                                fn ($documents) => $documents->where('is_active', true)
                            )
                            ->with('document')
                            ->whereVectorSimilarTo(
                                'embedding',
                                $embedding,
                                minSimilarity: 0.40
                            )
                            ->limit(8)
                            ->get();
                    } catch (\Throwable $e) {
                        $like = DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'like';

                        return RegulatoryChunk::query()
                            ->whereHas('document', fn ($d) => $d->where('is_active', true))
                            ->where(function ($q) use ($query, $like) {
                                $q->where('content', $like, "%{$query}%")
                                    ->orWhere('clause_title', $like, "%{$query}%");
                            })
                            ->with('document')
                            ->limit(8)
                            ->get();
                    }
                }
            ),
        ];
    }
}
