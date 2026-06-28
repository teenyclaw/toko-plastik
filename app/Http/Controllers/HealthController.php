<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = ['app' => 'ok'];

        try {
            DB::connection()->getPdo();
            $checks['database'] = 'ok';
        } catch (\Throwable $e) {
            $checks['database'] = 'error';

            return response()->json([
                'status' => 'degraded',
                'app' => config('app.name'),
                'checks' => $checks,
                'time' => now()->toIso8601String(),
            ], 503);
        }

        return response()->json([
            'status' => 'ok',
            'app' => config('app.name'),
            'checks' => $checks,
            'time' => now()->toIso8601String(),
        ]);
    }
}
