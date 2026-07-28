<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Link;
use Illuminate\Support\Str;
use RuntimeException;

class ShortCodeService
{
    private const ALPHABET = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function generate(int $length = 7): string
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $code = $this->randomCode($length);

            if ($this->isAvailable($code)) {
                return $code;
            }
        }

        throw new RuntimeException('No fue posible generar un codigo corto unico.');
    }

    public function isAvailable(string $code, ?int $ignoreLinkId = null): bool
    {
        return ! Link::query()
            ->withTrashed()
            ->where('short_code', $code)
            ->when($ignoreLinkId !== null, fn ($query) => $query->whereKeyNot($ignoreLinkId))
            ->exists();
    }

    public function normalizeAlias(string $alias): string
    {
        $normalized = Str::of($alias)
            ->trim()
            ->lower()
            ->replaceMatches('/\s+/', '-')
            ->replaceMatches('/[^a-z0-9_-]/', '')
            ->replaceMatches('/-+/', '-')
            ->trim('-_');

        return (string) $normalized;
    }

    private function randomCode(int $length): string
    {
        $code = '';
        $max = strlen(self::ALPHABET) - 1;

        for ($i = 0; $i < $length; $i++) {
            $code .= self::ALPHABET[random_int(0, $max)];
        }

        return $code;
    }
}
