<?php

namespace App\Models;

use Database\Factories\CopilotWebsiteFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CopilotWebsite extends Model
{
    /** @use HasFactory<CopilotWebsiteFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'welcome_message',
        'is_active',
        'requests_count',
        'last_active_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'requests_count' => 'integer',
            'last_active_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Find a website by either its slug or its UUID.
     */
    public static function findByIdentifier(string $identifier): ?self
    {
        $trimmed = trim($identifier);
        if ($trimmed === '') {
            return null;
        }

        return static::where('slug', $trimmed)
            ->orWhere(function ($query) use ($trimmed): void {
                if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $trimmed)) {
                    $query->where('id', $trimmed);
                }
            })
            ->first();
    }
}
