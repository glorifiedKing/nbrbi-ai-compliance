<?php
namespace App\Services;

use App\Models\RegulatoryChunk;
use Illuminate\Support\Collection;

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
                $question,
                minSimilarity: 0.40
            )
            ->limit(min(max($limit, 1), 20))
            ->get();
    }
}