<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return $next($request);
        }

        // Auto-assign tenant if missing (create unique tenant for user)
        if (!$user->tenant_id) {
            $tenant = Tenant::create([
                'name' => $user->name . "'s Family",
            ]);

            $user->update(['tenant_id' => $tenant->id]);
            $user->refresh(); // Refresh to get updated tenant_id
        }

        return $next($request);
    }
}
