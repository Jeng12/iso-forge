<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $request->route('tenant');

        if ($tenant instanceof Tenant) {
            abort_unless((int) $request->user()->tenant_id === (int) $tenant->id, 403, 'Tenant access denied.');
        }

        return $next($request);
    }
}
