<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * @param  array<string, mixed>  $headers
     */
    protected function success(
        mixed $data = null,
        ?string $message = null,
        int $status = 200,
        array $headers = []
    ): JsonResponse {
        $payload = [
            'success' => true,
        ];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        if ($data !== null) {
            $payload['data'] = $data;
        }

        return response()->json($payload, $status, $headers);
    }

    /**
     * @param  array<string, mixed>  $errors
     * @param  array<string, mixed>  $headers
     */
    protected function error(
        string $message = 'No fue posible completar la operacion.',
        int $status = 400,
        array $errors = [],
        array $headers = []
    ): JsonResponse {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status, $headers);
    }
}
