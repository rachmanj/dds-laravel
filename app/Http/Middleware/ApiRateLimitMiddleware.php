<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ApiRateLimitMiddleware
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
        $ip = $request->ip();

        // Rate limit by API key + IP combination
        $key = "api_rate_limit:{$apiKey}:{$ip}";

        // Get current request count
        $currentCount = Cache::get($key, 0);

        // Rate limit: 100 requests per hour, 20 per minute
        $hourlyLimit = 100;
        $minuteLimit = 20;

        // Check hourly limit
        if ($currentCount >= $hourlyLimit) {
            Log::warning('API rate limit exceeded (hourly)', [
                'ip' => $ip,
                'api_key' => $apiKey,
                'endpoint' => $request->fullUrl(),
                'current_count' => $currentCount,
                'limit' => $hourlyLimit
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Rate limit exceeded',
                'message' => 'Hourly rate limit exceeded. Please try again later.',
                'retry_after' => Cache::get("{$key}:reset_time", 3600)
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        // Check minute limit (sliding window)
        $minuteKey = "api_rate_limit_minute:{$apiKey}:{$ip}";
        $minuteCount = Cache::get($minuteKey, 0);

        if ($minuteCount >= $minuteLimit) {
            Log::warning('API rate limit exceeded (minute)', [
                'ip' => $ip,
                'api_key' => $apiKey,
                'endpoint' => $request->fullUrl(),
                'current_count' => $minuteCount,
                'limit' => $minuteLimit
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Rate limit exceeded',
                'message' => 'Minute rate limit exceeded. Please slow down your requests.',
                'retry_after' => 60
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        // Increment counters
        Cache::put($key, $currentCount + 1, 3600); // 1 hour
        Cache::put($minuteKey, $minuteCount + 1, 60); // 1 minute

        // Add rate limit headers to response
        $response = $next($request);

        $response->headers->set('X-RateLimit-Limit-Hourly', $hourlyLimit);
        $response->headers->set('X-RateLimit-Remaining-Hourly', $hourlyLimit - ($currentCount + 1));
        $response->headers->set('X-RateLimit-Reset-Hourly', time() + 3600);

        $response->headers->set('X-RateLimit-Limit-Minute', $minuteLimit);
        $response->headers->set('X-RateLimit-Remaining-Minute', $minuteLimit - ($minuteCount + 1));
        $response->headers->set('X-RateLimit-Reset-Minute', time() + 60);

        return $response;
    }
}
