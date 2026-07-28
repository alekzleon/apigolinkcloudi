<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Link;
use App\Models\LinkClick;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClickTrackingService
{
    public function track(Link $link, Request $request): LinkClick
    {
        return DB::transaction(function () use ($link, $request): LinkClick {
            $clickedAt = now();

            $click = $link->clicks()->create([
                'ip_hash' => $this->hashIp($request->ip()),
                'user_agent' => $request->userAgent(),
                'referrer' => $request->headers->get('referer'),
                'language' => $this->preferredLanguage($request),
                'device_type' => $this->detectDeviceType($request->userAgent()),
                'browser' => $this->detectBrowser($request->userAgent()),
                'operating_system' => $this->detectOperatingSystem($request->userAgent()),
                'is_bot' => $this->isBot($request->userAgent()),
                'utm_source' => $request->query('utm_source'),
                'utm_medium' => $request->query('utm_medium'),
                'utm_campaign' => $request->query('utm_campaign'),
                'utm_content' => $request->query('utm_content'),
                'utm_term' => $request->query('utm_term'),
                'clicked_at' => $clickedAt,
            ]);

            Link::query()
                ->whereKey($link->id)
                ->update([
                    'clicks_count' => DB::raw('clicks_count + 1'),
                    'last_clicked_at' => $clickedAt,
                    'updated_at' => $clickedAt,
                ]);

            return $click;
        });
    }

    private function hashIp(?string $ip): ?string
    {
        if ($ip === null || $ip === '') {
            return null;
        }

        return hash_hmac('sha256', $ip, (string) config('app.key'));
    }

    private function preferredLanguage(Request $request): ?string
    {
        $language = $request->getPreferredLanguage();

        return $language !== null && $language !== '' ? $language : null;
    }

    private function detectDeviceType(?string $userAgent): string
    {
        $userAgent = strtolower((string) $userAgent);

        if (str_contains($userAgent, 'tablet') || str_contains($userAgent, 'ipad')) {
            return 'tablet';
        }

        if (str_contains($userAgent, 'mobile') || str_contains($userAgent, 'android') || str_contains($userAgent, 'iphone')) {
            return 'mobile';
        }

        return 'desktop';
    }

    private function detectBrowser(?string $userAgent): string
    {
        $userAgent = strtolower((string) $userAgent);

        return match (true) {
            str_contains($userAgent, 'edg/') => 'Edge',
            str_contains($userAgent, 'chrome/') && ! str_contains($userAgent, 'chromium') => 'Chrome',
            str_contains($userAgent, 'safari/') && ! str_contains($userAgent, 'chrome/') => 'Safari',
            str_contains($userAgent, 'firefox/') => 'Firefox',
            str_contains($userAgent, 'curl/') => 'curl',
            default => 'Other',
        };
    }

    private function detectOperatingSystem(?string $userAgent): string
    {
        $userAgent = strtolower((string) $userAgent);

        return match (true) {
            str_contains($userAgent, 'windows') => 'Windows',
            str_contains($userAgent, 'iphone') || str_contains($userAgent, 'ipad') => 'iOS',
            str_contains($userAgent, 'android') => 'Android',
            str_contains($userAgent, 'mac os x') || str_contains($userAgent, 'macintosh') => 'macOS',
            str_contains($userAgent, 'linux') => 'Linux',
            default => 'Other',
        };
    }

    private function isBot(?string $userAgent): bool
    {
        $userAgent = strtolower((string) $userAgent);

        foreach (['bot', 'crawl', 'spider', 'slurp', 'preview', 'facebookexternalhit', 'whatsapp', 'telegrambot'] as $needle) {
            if (str_contains($userAgent, $needle)) {
                return true;
            }
        }

        return false;
    }
}
