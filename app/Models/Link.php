<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LinkStatus;
use Database\Factories\LinkFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property LinkStatus $status
 */
class Link extends Model
{
    /** @use HasFactory<LinkFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'original_url',
        'short_code',
        'is_custom_alias',
        'status',
        'clicks_count',
        'expires_at',
        'last_clicked_at',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<LinkClick, $this>
     */
    public function clicks(): HasMany
    {
        return $this->hasMany(LinkClick::class);
    }

    /**
     * @param  Builder<Link>  $query
     * @return Builder<Link>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', LinkStatus::Active);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_custom_alias' => 'boolean',
            'status' => LinkStatus::class,
            'clicks_count' => 'integer',
            'expires_at' => 'datetime',
            'last_clicked_at' => 'datetime',
        ];
    }
}
