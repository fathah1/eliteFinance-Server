<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiRequestLogger
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $next($request);

        $durationMs = (microtime(true) - $start) * 1000;
        $status = $response->getStatusCode();

        Log::channel('api_requests')->info('api.request', [
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            'query' => $request->getQueryString(),
            'status' => $status,
            'ip' => $request->ip(),
            'duration_ms' => round($durationMs, 1),
            'user_agent' => (string) $request->userAgent(),
        ]);

        return $response;
    }
}
