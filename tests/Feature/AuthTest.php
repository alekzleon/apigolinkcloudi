<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Alejandro Leon',
            'email' => 'alejandro@example.com',
            'password' => 'password-seguro',
            'password_confirmation' => 'password-seguro',
            'device_name' => 'Cloudi Go Web',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Registro exitoso.')
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.email', 'alejandro@example.com')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'token',
                    'token_type',
                    'user' => ['id', 'name', 'email'],
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'alejandro@example.com',
        ]);
    }

    public function test_user_can_login(): void
    {
        User::factory()->create([
            'email' => 'alejandro@example.com',
            'password' => 'password-seguro',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'alejandro@example.com',
            'password' => 'password-seguro',
            'device_name' => 'Cloudi Go Web',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Inicio de sesion exitoso.')
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.email', 'alejandro@example.com');
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        User::factory()->create([
            'email' => 'alejandro@example.com',
            'password' => 'password-seguro',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'alejandro@example.com',
            'password' => 'incorrecta',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Los datos proporcionados no son validos.')
            ->assertJsonValidationErrors('email');
    }

    public function test_authenticated_user_can_get_profile(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/auth/me');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.email', $user->email);
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('Cloudi Go Web');

        $response = $this
            ->withToken($token->plainTextToken)
            ->postJson('/api/auth/logout');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Sesion cerrada correctamente.');

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
    }

    public function test_protected_route_rejects_unauthenticated_users(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'No autenticado.');
    }
}
