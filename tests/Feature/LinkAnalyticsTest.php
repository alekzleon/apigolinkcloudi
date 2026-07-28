<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Link;
use App\Models\LinkClick;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LinkAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_analytics_returns_summary_and_grouped_data(): void
    {
        $user = User::factory()->create();
        $link = Link::factory()->for($user)->create();
        Sanctum::actingAs($user);

        LinkClick::factory()->for($link)->create([
            'clicked_at' => now(),
            'referrer' => 'instagram.com',
            'device_type' => 'mobile',
            'browser' => 'Chrome',
            'operating_system' => 'iOS',
        ]);
        LinkClick::factory()->for($link)->create([
            'clicked_at' => now()->subDay(),
            'referrer' => 'instagram.com',
            'device_type' => 'desktop',
            'browser' => 'Safari',
            'operating_system' => 'iOS',
        ]);
        LinkClick::factory()->for($link)->create([
            'clicked_at' => now()->subDays(10),
            'referrer' => 'google.com',
            'device_type' => 'mobile',
            'browser' => 'Chrome',
            'operating_system' => 'Android',
        ]);

        $response = $this->getJson("/api/links/{$link->id}/analytics");

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.summary.total_clicks', 3)
            ->assertJsonPath('data.summary.today_clicks', 1)
            ->assertJsonPath('data.summary.last_7_days_clicks', 2)
            ->assertJsonPath('data.summary.last_30_days_clicks', 3)
            ->assertJsonPath('data.top_referrers.0.referrer', 'instagram.com')
            ->assertJsonPath('data.top_referrers.0.clicks', 2)
            ->assertJsonPath('data.top_devices.0.device', 'mobile')
            ->assertJsonPath('data.top_devices.0.clicks', 2)
            ->assertJsonPath('data.top_browsers.0.browser', 'Chrome')
            ->assertJsonPath('data.top_browsers.0.clicks', 2)
            ->assertJsonPath('data.top_operating_systems.0.operating_system', 'iOS');

        $this->assertCount(3, $response->json('data.clicks_by_day'));
    }

    public function test_analytics_respects_date_filters(): void
    {
        $user = User::factory()->create();
        $link = Link::factory()->for($user)->create();
        Sanctum::actingAs($user);

        LinkClick::factory()->for($link)->create([
            'clicked_at' => '2026-07-25 12:00:00',
            'device_type' => 'mobile',
        ]);
        LinkClick::factory()->for($link)->create([
            'clicked_at' => '2026-07-26 12:00:00',
            'device_type' => 'desktop',
        ]);
        LinkClick::factory()->for($link)->create([
            'clicked_at' => '2026-07-27 12:00:00',
            'device_type' => 'tablet',
        ]);

        $response = $this->getJson("/api/links/{$link->id}/analytics?from=2026-07-26&to=2026-07-27");

        $response
            ->assertOk()
            ->assertJsonPath('data.summary.total_clicks', 2);

        $this->assertSame([
            ['date' => '2026-07-26', 'clicks' => 1],
            ['date' => '2026-07-27', 'clicks' => 1],
        ], $response->json('data.clicks_by_day'));
    }

    public function test_user_cannot_view_analytics_for_another_users_link(): void
    {
        $link = Link::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson("/api/links/{$link->id}/analytics");

        $response
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_analytics_rejects_invalid_date_range(): void
    {
        $user = User::factory()->create();
        $link = Link::factory()->for($user)->create();
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/links/{$link->id}/analytics?from=2026-07-28&to=2026-07-27");

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('to');
    }
}
