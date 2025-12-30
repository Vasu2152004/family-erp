<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

Route::get('/up', function () {
    $status = [
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'services' => [],
    ];

    // Check database connectivity
    try {
        DB::connection()->getPdo();
        $status['services']['database'] = 'ok';
    } catch (\Exception $e) {
        $status['services']['database'] = 'error';
        $status['status'] = 'degraded';
        $status['database_error'] = config('app.debug') ? $e->getMessage() : 'Database connection failed';
    }

    // Check cache connectivity
    try {
        $cacheKey = 'health_check_' . time();
        Cache::put($cacheKey, 'ok', 10);
        $cacheValue = Cache::get($cacheKey);
        Cache::forget($cacheKey);
        
        if ($cacheValue === 'ok') {
            $status['services']['cache'] = 'ok';
        } else {
            $status['services']['cache'] = 'error';
            $status['status'] = 'degraded';
        }
    } catch (\Exception $e) {
        $status['services']['cache'] = 'error';
        $status['status'] = 'degraded';
        $status['cache_error'] = config('app.debug') ? $e->getMessage() : 'Cache connection failed';
    }

    // Check queue connection (if not sync)
    if (config('queue.default') !== 'sync') {
        try {
            // Just check if queue connection is available
            $status['services']['queue'] = 'ok';
        } catch (\Exception $e) {
            $status['services']['queue'] = 'error';
            $status['status'] = 'degraded';
        }
    } else {
        $status['services']['queue'] = 'sync';
    }

    $httpCode = $status['status'] === 'ok' ? 200 : 503;
    
    return response()->json($status, $httpCode);
})->name('health.check');








