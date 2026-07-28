<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LinkStatus;
use App\Models\Link;
use App\Models\LinkClick;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_link_belongs_to_user_and_has_clicks(): void
    {
        $user = User::factory()->create();
        $link = Link::factory()->for($user)->active()->create();
        $click = LinkClick::factory()->for($link)->create();

        $this->assertTrue($link->user->is($user));
        $this->assertTrue($link->clicks->first()->is($click));
        $this->assertSame(LinkStatus::Active, $link->status);
    }

    public function test_link_can_be_soft_deleted(): void
    {
        $link = Link::factory()->create();

        $link->delete();

        $this->assertSoftDeleted('links', [
            'id' => $link->id,
        ]);
    }

    public function test_link_click_stores_ip_hash_not_plain_ip(): void
    {
        $ip = '203.0.113.10';
        $hash = hash_hmac('sha256', $ip, config('app.key'));
        $click = LinkClick::factory()->create([
            'ip_hash' => $hash,
        ]);

        $this->assertSame($hash, $click->ip_hash);
        $this->assertNotSame($ip, $click->ip_hash);
    }
}
