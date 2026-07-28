<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\LinkClickFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LinkClick extends Model
{
    /** @use HasFactory<LinkClickFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'link_id',
        'ip_hash',
        'user_agent',
        'referrer',
        'language',
        'device_type',
        'browser',
        'operating_system',
        'is_bot',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
        'clicked_at',
    ];

    /**
     * @return BelongsTo<Link, $this>
     */
    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_bot' => 'boolean',
            'clicked_at' => 'datetime',
        ];
    }
}
