<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LinkStatus;
use App\Models\Link;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LinkCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_link_with_generated_code(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/links', [
            'name' => 'Demo CloudiShop',
            'original_url' => 'https://cloudishop.mx/demo',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Enlace creado correctamente.')
            ->assertJsonPath('data.name', 'Demo CloudiShop')
            ->assertJsonPath('data.is_custom_alias', false)
            ->assertJsonPath('data.status', 'active')
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'original_url',
                    'short_code',
                    'short_url',
                    'is_custom_alias',
                    'status',
                    'clicks_count',
                ],
            ]);

        $this->assertSame(7, strlen((string) $response->json('data.short_code')));
        $this->assertDatabaseHas('links', [
            'user_id' => $user->id,
            'name' => 'Demo CloudiShop',
            'is_custom_alias' => false,
        ]);
    }

    public function test_authenticated_user_can_create_link_with_custom_alias(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/links', [
            'name' => 'Demo Alias',
            'original_url' => 'https://cloudishop.mx/demo',
            'custom_alias' => 'Demo Alias',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.short_code', 'demo-alias')
            ->assertJsonPath('data.is_custom_alias', true);
    }

    public function test_links_routes_reject_unauthenticated_users(): void
    {
        $response = $this->getJson('/api/links');

        $response
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_duplicate_alias_is_rejected(): void
    {
        Link::factory()->customAlias('demo')->create();
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/links', [
            'name' => 'Duplicado',
            'original_url' => 'https://cloudishop.mx/demo',
            'custom_alias' => 'demo',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('custom_alias');
    }

    public function test_reserved_alias_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/links', [
            'name' => 'Reservado',
            'original_url' => 'https://cloudishop.mx/demo',
            'custom_alias' => 'api',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('custom_alias');
    }

    public function test_dangerous_url_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/links', [
            'name' => 'URL peligrosa',
            'original_url' => 'http://127.0.0.1/admin',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('original_url');
    }

    public function test_unsupported_url_scheme_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/links', [
            'name' => 'URL invalida',
            'original_url' => 'javascript:alert(1)',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('original_url');
    }

    public function test_expiration_date_must_be_in_the_future(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/links', [
            'name' => 'Expirado',
            'original_url' => 'https://cloudishop.mx/demo',
            'expires_at' => now()->subDay()->toIso8601String(),
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('expires_at');
    }

    public function test_user_only_sees_own_links(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $ownLink = Link::factory()->for($user)->create(['name' => 'Mio']);
        Link::factory()->for($otherUser)->create(['name' => 'Ajeno']);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/links');

        $response
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.links.0.id', $ownLink->id)
            ->assertJsonMissing(['name' => 'Ajeno']);
    }

    public function test_links_can_be_filtered_searched_sorted_and_paginated(): void
    {
        $user = User::factory()->create();

        Link::factory()->for($user)->active()->create([
            'name' => 'Demo Alpha',
            'short_code' => 'alpha01',
            'clicks_count' => 5,
            'created_at' => now()->subDays(2),
        ]);
        $target = Link::factory()->for($user)->active()->create([
            'name' => 'Demo Beta',
            'short_code' => 'beta001',
            'clicks_count' => 25,
            'created_at' => now()->subDay(),
        ]);
        Link::factory()->for($user)->inactive()->create([
            'name' => 'Demo Gamma',
            'short_code' => 'gamma01',
            'clicks_count' => 50,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/links?search=demo&status=active&sort=clicks_count&direction=desc&per_page=1');

        $response
            ->assertOk()
            ->assertJsonPath('data.meta.total', 2)
            ->assertJsonPath('data.meta.per_page', 1)
            ->assertJsonPath('data.links.0.id', $target->id)
            ->assertJsonPath('data.links.0.clicks_count', 25);
    }

    public function test_user_cannot_update_another_users_link(): void
    {
        $link = Link::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $response = $this->patchJson("/api/links/{$link->id}", [
            'name' => 'Intento ajeno',
        ]);

        $response
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_link_can_be_updated_without_changing_short_code(): void
    {
        $user = User::factory()->create();
        $link = Link::factory()->for($user)->create([
            'short_code' => 'abc123x',
            'original_url' => 'https://cloudishop.mx/antes',
        ]);
        Sanctum::actingAs($user);

        $response = $this->patchJson("/api/links/{$link->id}", [
            'name' => 'Nuevo nombre',
            'original_url' => 'https://cloudishop.mx/despues',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.name', 'Nuevo nombre')
            ->assertJsonPath('data.original_url', 'https://cloudishop.mx/despues')
            ->assertJsonPath('data.short_code', 'abc123x');
    }

    public function test_link_can_be_deactivated(): void
    {
        $user = User::factory()->create();
        $link = Link::factory()->for($user)->active()->create();
        Sanctum::actingAs($user);

        $response = $this->patchJson("/api/links/{$link->id}/status", [
            'status' => LinkStatus::Inactive->value,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive');
    }

    public function test_link_can_be_soft_deleted(): void
    {
        $user = User::factory()->create();
        $link = Link::factory()->for($user)->create();
        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/links/{$link->id}");

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Enlace eliminado correctamente.');

        $this->assertSoftDeleted('links', [
            'id' => $link->id,
        ]);
    }
}
