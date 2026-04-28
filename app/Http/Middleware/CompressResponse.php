<?php

namespace App\Http\Middleware;

use Closure;

class CompressResponse
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if (strpos($request->header('Accept-Encoding'), 'gzip') !== false) {
            $response->header('Content-Encoding', 'gzip');
            $response->setContent(gzencode($response->getContent()));
        }

        return $response;
    }
}