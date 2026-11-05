<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Drawing extends Model
{
    use HasFactory, HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'block_id',
        'discipline',
        'current_version_id',
    ];

    /**
     * @return BelongsTo<Block, $this>
     */
    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }

    /**
     * @return BelongsTo<DrawingVersion, $this>
     */
    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(DrawingVersion::class, 'current_version_id');
    }

    /**
     * @return HasMany<DrawingVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(DrawingVersion::class)->orderByDesc('version_number');
    }
}
