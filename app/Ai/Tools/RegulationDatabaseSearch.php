<?php

namespace App\Ai\Tools;

use App\Models\RegulatoryChunk;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Collection;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class RegulationDatabaseSearch implements Tool
{
    public function description(): Stringable|string
    {
        return 'Search active Ugandan building regulations by clause number, title, or content. Returns matching provisions and their source document details.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema
                ->string()
                ->description(
                    'Clause number, clause title, or keywords to search for.'
                )
                ->required(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $search = $request['query'] ?? request()->input('query', 'hallo');

        if ($search === '') {
            return 'Provide a clause number, title, or search keyword.';
        }

        $chunks = RegulatoryChunk::query()
            ->whereHas(
                'document',
                fn($query) =>
                    $query->where('is_active', true)
            )
            ->with('document')
            ->where(function ($q) use ($search): void {
                $term = '%' . $search . '%';

                $q->where('clause_number', 'ILIKE', $term)
                    ->orWhere('clause_title', 'ILIKE', $term)
                    ->orWhere('content', 'ILIKE', $term);
            })
            ->limit(15)
            ->get();

        if ($chunks->isEmpty()) {
            return 'No matching provisions were found in active regulatory documents.';
        }

        return $chunks->map(fn(RegulatoryChunk $chunk) => [
            'document_title' => $chunk->document?->title,
            'edition_year' => $chunk->document?->edition_year,
            'category' => $chunk->document?->category,
            'clause_number' => $chunk->clause_number,
            'clause_title' => $chunk->clause_title,
            'content' => $chunk->content,
            'page_number' => data_get($chunk->metadata, 'page'),
        ])->toJson(JSON_PRETTY_PRINT);
    }
}
