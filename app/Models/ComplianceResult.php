<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplianceResult extends Model
{
    use HasFactory, HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'drawing_version_id',
        'preflight_metrics',
        'extracted_geometry',
        'discrepancies',
        'summary_metrics',
        'overall_status',
        'analyzed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'preflight_metrics' => 'array',
            'extracted_geometry' => 'array',
            'discrepancies' => 'array',
            'summary_metrics' => 'array',
            'analyzed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<DrawingVersion, $this>
     */
    public function drawingVersion(): BelongsTo
    {
        return $this->belongsTo(DrawingVersion::class, 'drawing_version_id');
    }
}
