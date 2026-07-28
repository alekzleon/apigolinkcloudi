<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LinkStatus;
use App\Models\Link;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_short_code_redirects_to_original_url(): void
    {
        $link = Link::factory()->active()->create([
            'short_code' => 'demo123',
            'original_url' => 'https://cloudishop.mx/demo',
            'expires_at' => now()->addDay(),
        ]);

        $response = $this->get('/demo123');

        $response
            ->assertStatus(302)
            ->assertRedirect($link->original_url);
    }

    public function test_missing_short_code_returns_not_found(): void
    {
        $response = $this->get('/missing');

        $response->assertNotFound();
    }

    public function test_expired_link_returns_gone(): void
    {
        Link::factory()->active()->expired()->create([
            'short_code' => 'expired',
            'original_url' => 'https://cloudishop.mx/demo',
        ]);

        $response = $this->get('/expired');

        $response->assertGone();
    }

    public function test_inactive_link_does_not_redirect(): void
    {
        Link::factory()->inactive()->create([
            'short_code' => 'inactive',
            'original_url' => 'https://cloudishop.mx/demo',
            'status' => LinkStatus::Inactive,
        ]);

        $response = $this->get('/inactive');

        $response->assertForbidden();
    }

    public function test_valid_redirect_tracks_click_and_updates_counters(): void
    {
        $link = Link::factory()->active()->create([
            'short_code' => 'trackme',
            'original_url' => 'https://cloudishop.mx/demo',
            'clicks_count' => 0,
            'last_clicked_at' => null,
            'expires_at' => now()->addDay(),
        ]);

        $this
            ->withServerVariables([
                'REMOTE_ADDR' => '203.0.113.10',
                'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Version/17.0 Mobile/15E148 Safari/604.1',
                'HTTP_REFERER' => 'https://instagram.com/cloudi',
                'HTTP_ACCEPT_LANGUAGE' => 'es-MX,es;q=0.9',
            ])
            ->get('/trackme?utm_source=instagram&utm_medium=social&utm_campaign=lanzamiento')
            ->assertStatus(302);

        $link->refresh();

        $expectedIpHash = hash_hmac('sha256', '203.0.113.10', config('app.key'));

        $this->assertSame(1, $link->clicks_count);
        $this->assertNotNull($link->last_clicked_at);
        $this->assertDatabaseHas('link_clicks', [
            'link_id' => $link->id,
            'ip_hash' => $expectedIpHash,
            'referrer' => 'https://instagram.com/cloudi',
            'language' => 'es_MX',
            'device_type' => 'mobile',
            'browser' => 'Safari',
            'operating_system' => 'iOS',
            'is_bot' => false,
            'utm_source' => 'instagram',
            'utm_medium' => 'social',
            'utm_campaign' => 'lanzamiento',
        ]);
        $this->assertDatabaseMissing('link_clicks', [
            'ip_hash' => '203.0.113.10',
        ]);
    }

    public function test_bot_user_agent_is_marked(): void
    {
        $link = Link::factory()->active()->create([
            'short_code' => 'botlink',
            'original_url' => 'https://cloudishop.mx/demo',
            'expires_at' => now()->addDay(),
        ]);

        $this
            ->withHeader('User-Agent', 'Googlebot/2.1')
            ->get('/botlink')
            ->assertStatus(302);

        $this->assertDatabaseHas('link_clicks', [
            'link_id' => $link->id,
            'is_bot' => true,
            'browser' => 'Other',
        ]);
    }

    public function test_invalid_public_links_do_not_track_clicks(): void
    {
        Link::factory()->inactive()->create([
            'short_code' => 'offlink',
        ]);

        Link::factory()->active()->expired()->create([
            'short_code' => 'oldlink',
        ]);

        $this->get('/missing')->assertNotFound();
        $this->get('/offlink')->assertForbidden();
        $this->get('/oldlink')->assertGone();

        $this->assertDatabaseCount('link_clicks', 0);
    }
}
