<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CacheHeadersForGet
{
    private const MAX_AGE = 60;

    /**
     * Route names that are safe to cache with short-lived public cache.
     */
    private const CACHEABLE_ROUTE_NAMES = [
        'dashboard',
        'families.index',
        'families.show',
        'families.members.index',
        'families.health.index',
        'families.tasks.index',
        'families.calendar.index',
        'finance.index',
        'finance.accounts.index',
        'finance.transactions.index',
        'finance.budgets.index',
        'notifications.index',
        'family-member-requests.index',
        'assets.index',
        'investments.index',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->method() !== 'GET') {
            return $response;
        }

        $routeName = $request->route()?->getName();
        if ($routeName === null || !in_array($routeName, self::CACHEABLE_ROUTE_NAMES, true)) {
            return $response;
        }

        // Use private for authenticated routes (user-specific content), public for guest
        $cacheType = $request->user() ? 'private' : 'public';
        $response->headers->set('Cache-Control', $cacheType . ', max-age=' . self::MAX_AGE);

        $content = $response->getContent();
        if ($content !== false && $content !== '') {
            $etag = '"' . md5($content) . '"';
            $response->headers->set('ETag', $etag);

            if ($request->header('If-None-Match') === $etag) {
                return response('', 304)
                    ->withHeaders([
                        'Cache-Control' => $cacheType . ', max-age=' . self::MAX_AGE,
                        'ETag' => $etag,
                    ]);
            }
        }

        return $response;
    }
}
