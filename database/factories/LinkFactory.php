<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LinkStatus;
use App\Models\Link;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Link>
 */
class LinkFactory extends Factory
{
    protected $model = Link::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->sentence(3),
            'original_url' => fake()->url(),
            'short_code' => Str::random(7),
            'is_custom_alias' => false,
            'status' => fake()->randomElement(LinkStatus::cases()),
            'clicks_count' => 0,
            'expires_at' => fake()->optional(0.25)->dateTimeBetween('-10 days', '+30 days'),
            'last_clicked_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => LinkStatus::Active,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => LinkStatus::Inactive,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'expires_at' => now()->subDay(),
        ]);
    }

    public function customAlias(?string $alias = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'short_code' => $alias ?? fake()->unique()->slug(2),
            'is_custom_alias' => true,
        ]);
    }
}
