<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Link;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (! App::environment('local')) {
            return;
        }

        $user = User::factory()->create([
            'name' => 'Cloudi Admin',
            'email' => 'admin@cloudi.local',
            'password' => 'password',
        ]);

        Link::factory()
            ->count(10)
            ->for($user)
            ->create()
            ->each(function (Link $link): void {
                $clicksCount = fake()->numberBetween(10, 100);

                $link->clicks()->createMany(
                    \Database\Factories\LinkClickFactory::new()
                        ->count($clicksCount)
                        ->make(['link_id' => $link->id])
                        ->toArray()
                );

                $link->forceFill([
                    'clicks_count' => $clicksCount,
                    'last_clicked_at' => $link->clicks()->max('clicked_at'),
                ])->save();
            });
    }
}
