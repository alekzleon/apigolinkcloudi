<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Link;
use App\Models\LinkClick;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LinkClick>
 */
class LinkClickFactory extends Factory
{
    protected $model = LinkClick::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $ip = fake()->ipv4();

        return [
            'link_id' => Link::factory(),
            'ip_hash' => hash_hmac('sha256', $ip, config('app.key', 'testing')),
            'user_agent' => fake()->userAgent(),
            'referrer' => fake()->optional(0.7)->url(),
            'language' => fake()->randomElement(['es-MX', 'es', 'en-US', null]),
            'device_type' => fake()->randomElement(['desktop', 'mobile', 'tablet']),
            'browser' => fake()->randomElement(['Chrome', 'Safari', 'Firefox', 'Edge']),
            'operating_system' => fake()->randomElement(['Windows', 'macOS', 'iOS', 'Android', 'Linux']),
            'is_bot' => fake()->boolean(5),
            'utm_source' => fake()->optional(0.4)->randomElement(['instagram', 'facebook', 'google', 'newsletter']),
            'utm_medium' => fake()->optional(0.4)->randomElement(['social', 'cpc', 'email']),
            'utm_campaign' => fake()->optional(0.3)->slug(2),
            'utm_content' => fake()->optional(0.2)->slug(2),
            'utm_term' => fake()->optional(0.2)->word(),
            'clicked_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
