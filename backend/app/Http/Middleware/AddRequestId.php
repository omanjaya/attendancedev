<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AddRequestId
{
    /**
     * Add unique request ID to all API responses for error tracking
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Generate unique request ID
        $requestId = (string) Str::uuid();

        // Store in request for use in controllers/services
        $request->attributes->set('request_id', $requestId);

        $response = $next($request);

        // Add request ID to response headers
        $response->headers->set('X-Request-ID', $requestId);

        // Add request ID to JSON response body untuk API
        if ($response->headers->get('Content-Type') === 'application/json') {
            $content = json_decode($response->getContent(), true);

            if (is_array($content)) {
                $content['request_id'] = $requestId;
                $response->setContent(json_encode($content));
            }
        }

        return $response;
    }
}
