<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CachePublicPage
{
    /**
     * Marks anonymous, content-only GET responses as publicly cacheable.
     *
     * Paired with routes that also drop session/cookie middleware (see
     * routes/web.php $sessionless) — without that pairing, Symfony's
     * Response::prepare() still sends Set-Cookie alongside this header,
     * which makes most CDNs/proxies refuse to cache the response anyway.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->isSuccessful()) {
            $response->headers->set('Cache-Control', 'public, max-age=300, stale-while-revalidate=1800');
        }

        return $response;
    }
}
