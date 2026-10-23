<?php
namespace App\Services;

use App\Models\RegulatoryChunk;
use Illuminate\Support\Collection;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Enums\Lab;

class RegulationSearchService
{

    public function exactClause(string $clauseNumber): Collection
    {
        return RegulatoryChunk::query()
            ->where('clause_number', trim($clauseNumber))
            ->whereHas(
                'document',
                fn($query) =>
                    $query->where('is_active', true)
            )
            ->with('document')
            ->get();
    }

    public function formatSources(Collection $chunks): array
    {
        return $chunks->map(fn(RegulatoryChunk $chunk) => [
            'id' => $chunk->id,
            'document_title' => $chunk->document?->title,
            'category' => $chunk->document?->category,
            'edition_year' => $chunk->document?->edition_year,
            'clause_number' => $chunk->clause_number,
            'clause_title' => $chunk->clause_title,
            //'content' => $chunk->content,
            'page_number' => data_get($chunk->metadata, 'page'),
        ])->values()->all();
    }
    public function semanticSearch(
        string $question,
        int $limit = 8
    ): Collection {
        $response = Embeddings::for([$question])
            ->generate(Lab::Gemini, 'gemini-embedding-001');

        $queryEmbedding = $response->embeddings[0];

        return RegulatoryChunk::query()
            ->whereNotNull('embedding')
            ->whereHas(
                'document',
                fn($query) =>
                    $query->where('is_active', true)
            )
            ->with('document')
            ->whereVectorSimilarTo(
                'embedding',
                $queryEmbedding,
                minSimilarity: 0.40
            )
            ->limit(min(max($limit, 1), 20))
            ->get();
        //    $search = $question;
        // return RegulatoryChunk::query()
        //     ->whereHas(
        //         'document',
        //         fn($query) =>
        //             $query->where('is_active', true)
        //     )
        //     ->with('document')
        //     ->where(function ($q) use ($search): void {
        //         $term = '%' . $search . '%';

        //         $q->where('clause_number', 'ILIKE', $term)
        //             ->orWhere('clause_title', 'ILIKE', $term)
        //             ->orWhere('content', 'ILIKE', $term);
        //     })
        //     ->limit(min(max($limit, 1), 20))
        //     ->get();
    }
}