<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    public function register(): void
    {
        //
    }

    public function render($request, Throwable $exception)
{
    if ($request->is('api/*') || $request->expectsJson()) {
        
     
        // 500 général
        return response()->json([
            'message' => 'Server Error'
        ], 500);
    }

    return parent::render($request, $exception);
}
}