<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('X-API-Key');
        $validApiKey = config('app.dds_api_key') ?? env('DDS_API_KEY');

        if (!$apiKey || $apiKey !== $validApiKey) {
            // Log unauthorized access attempt
            Log::warning('API access denied', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'endpoint' => $request->fullUrl(),
                'api_key_provided' => $apiKey ? 'yes' : 'no',
                'timestamp' => now()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Unauthorized',
                'message' => 'Invalid or missing API key'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Log successful API access
        Log::info('API access granted', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'endpoint' => $request->fullUrl(),
            'timestamp' => now()
        ]);

        return $next($request);
    }
}
