<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuickAudit extends Model
{
    use HasFactory, HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'file_name',
        'original_name',
        'file_size_bytes',
        'file_hash',
        'discipline',
        'occupancy_class',
        'gross_floor_area_sqm',
        'estimated_statutory_fee_ugx',
        'status',
        'batch_job_id',
        'error_message',
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
            'file_size_bytes' => 'integer',
            'gross_floor_area_sqm' => 'float',
            'estimated_statutory_fee_ugx' => 'float',
            'preflight_metrics' => 'array',
            'extracted_geometry' => 'array',
            'discrepancies' => 'array',
            'summary_metrics' => 'array',
            'analyzed_at' => 'datetime',
        ];
    }

    /**
     * The user who requested the quick audit (if authenticated).
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
