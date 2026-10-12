<?php

namespace App\Models;

use App\Services\FeeCalculationService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'owner_id',
        'name',
        'reference_number',
        'occupancy_class',
        'total_floor_area_sqm',
        'fee_rate_per_sqm_ugx',
        'estimated_statutory_fee_ugx',
        'share_token',
        'is_public',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'total_floor_area_sqm' => 'float',
            'fee_rate_per_sqm_ugx' => 'float',
            'estimated_statutory_fee_ugx' => 'float',
        ];
    }

    /**
     * Recalculates block and project total floor areas and statutory fees.
     */
    public function recalculateTotals(): self
    {
        return FeeCalculationService::recalculateProjectTotals($this);
    }

    /**
     * Owner of the project.
     *
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Collaborators / users attached to this project.
     *
     * @return BelongsToMany<User, $this>
     */
    public function collaborators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_users')
            ->withPivot('id', 'role', 'status')
            ->withTimestamps();
    }

    /**
     * Blocks within this project.
     *
     * @return HasMany<Block, $this>
     */
    public function blocks(): HasMany
    {
        return $this->hasMany(Block::class);
    }

    /**
     * Floors belonging to the project across all blocks.
     *
     * @return HasManyThrough<Floor>
     */
    public function floors(): HasManyThrough
    {
        return $this->hasManyThrough(Floor::class, Block::class);
    }
}
