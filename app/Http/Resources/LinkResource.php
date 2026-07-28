<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LinkResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'original_url' => $this->original_url,
            'short_code' => $this->short_code,
            'short_url' => config('cloudigo.short_url_base').'/'.$this->short_code,
            'is_custom_alias' => $this->is_custom_alias,
            'status' => $this->status?->value,
            'clicks_count' => $this->clicks_count,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'last_clicked_at' => $this->last_clicked_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
