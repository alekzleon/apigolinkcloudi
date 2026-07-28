<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LinkStatus;
use App\Models\Link;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LinkService
{
    public function __construct(
        private readonly ShortCodeService $shortCodeService
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): Link
    {
        return DB::transaction(function () use ($user, $data): Link {
            $customAlias = isset($data['custom_alias']) && $data['custom_alias'] !== null && $data['custom_alias'] !== '';
            $shortCode = $customAlias
                ? $this->shortCodeService->normalizeAlias((string) $data['custom_alias'])
                : $this->shortCodeService->generate();

            return $user->links()->create([
                'name' => $data['name'],
                'original_url' => $data['original_url'],
                'short_code' => $shortCode,
                'is_custom_alias' => $customAlias,
                'status' => LinkStatus::tryFrom((string) ($data['status'] ?? '')) ?? LinkStatus::Active,
                'expires_at' => $data['expires_at'] ?? null,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Link $link, array $data): Link
    {
        return DB::transaction(function () use ($link, $data): Link {
            $link->fill([
                'name' => $data['name'] ?? $link->name,
                'original_url' => $data['original_url'] ?? $link->original_url,
                'status' => isset($data['status'])
                    ? LinkStatus::from((string) $data['status'])
                    : $link->status,
                'expires_at' => array_key_exists('expires_at', $data) ? $data['expires_at'] : $link->expires_at,
            ]);

            $link->save();

            return $link->refresh();
        });
    }

    public function updateStatus(Link $link, LinkStatus $status): Link
    {
        $link->forceFill(['status' => $status])->save();

        return $link->refresh();
    }
}
