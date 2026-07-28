<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthController extends Controller
{
    use ApiResponse;

    public function __invoke(): JsonResponse
    {
        return $this->success([
            'status' => 'ok',
            'application' => config('app.name'),
            'database' => $this->databaseStatus(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    private function databaseStatus(): string
    {
        try {
            DB::connection()->getPdo();

            return 'connected';
        } catch (Throwable) {
            return 'disconnected';
        }
    }
}
