<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class checkIpMapping
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ipAddress = $request->ip();
        $unit = DB::connection('pgsql')
            ->table('ip_mappings')
            ->where('ip_address', $ipAddress)
            ->value('unit');
        
        if (!$unit) {
            return response()->view('Middleware.ip_restriction', [], 403);
        }

        $allowedUnits = DB::connection('pgsql')
            ->table('service_units')
            ->pluck('unit_service_name');

        if (!in_array($unit, $allowedUnits->toArray())) {
            return response()->view('Middleware.unit_restriction', [], 403);
        }

        return $next($request);
    }
}
