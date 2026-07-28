<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::query()->create($request->safe()->only([
            'name',
            'email',
            'password',
        ]));

        $token = $user->createToken($this->deviceName($request))->plainTextToken;

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ], 'Registro exitoso.', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()
            ->where('email', strtolower((string) $request->validated('email')))
            ->first();

        if (! $user || ! Hash::check((string) $request->validated('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas no son validas.'],
            ]);
        }

        $token = $user->createToken($this->deviceName($request))->plainTextToken;

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ], 'Inicio de sesion exitoso.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success([
            'user' => new UserResource($request->user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return $this->success(null, 'Sesion cerrada correctamente.');
    }

    private function deviceName(Request $request): string
    {
        return (string) ($request->input('device_name') ?: 'Cloudi Go API');
    }
}
