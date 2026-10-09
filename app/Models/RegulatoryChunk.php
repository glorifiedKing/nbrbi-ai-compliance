<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegulatoryChunk extends Model
{
    use HasFactory, HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'regulatory_document_id',
        'clause_number',
        'clause_title',
        'content',
        'metadata',
        'embedding',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<RegulatoryDocument, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(RegulatoryDocument::class, 'regulatory_document_id');
    }
}
