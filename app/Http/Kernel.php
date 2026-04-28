<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    // Middlewares globaux
    protected $middleware = [
        \App\Http\Middleware\SecureHeaders::class,
        \App\Http\Middleware\ForceHttps::class,
        \App\Http\Middleware\CompressResponse::class,
    ];

    // Middlewares pour routes (avec clés)
    protected $routeMiddleware = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'role' => \App\Http\Middleware\CheckRole::class,
    ];
}