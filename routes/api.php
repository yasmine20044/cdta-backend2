<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Middleware\SecureHeaders;

Route::middleware([SecureHeaders::class])->group(function () {

    //
    // TEST
    //
    Route::get('/test', function () {
        return response()->json(['message' => 'API works']);
    });

    //
    // VERSIONING V1
    //
    Route::prefix('v1')->group(function () {

        //
        // AUTH (PUBLIC)
        //
        Route::middleware(['throttle:5,1'])->group(function () {
            Route::post('/register', [AuthController::class, 'register']);
            Route::post('/login', [AuthController::class, 'login']);
            Route::post('/logout', [AuthController::class, 'logout'])
                ->middleware('auth:sanctum');
        });

        //
        // PUBLIC READ (lecture publique)
        //
        Route::get('/events', [EventController::class, 'index']);
        Route::get('/events/{id}', [EventController::class, 'show']);

        Route::get('/pages', [PageController::class, 'index']);
        Route::get('/pages/{id}', [PageController::class, 'show']);

        Route::get('/news', [NewsController::class, 'index']);
        Route::get('/news/{id}', [NewsController::class, 'show']);

        Route::get('/services', [ServiceController::class, 'index']);
        Route::get('/services/{id}', [ServiceController::class, 'show']);

        //
        // ADMIN + EDITOR (CREATE / UPDATE / DELETE)
        //
        Route::middleware(['auth:sanctum', 'role:admin,editor', 'throttle:5,1'])
            ->group(function () {

                // EVENTS
                Route::post('/events', [EventController::class, 'store']);
                Route::put('/events/{id}', [EventController::class, 'update']);
                Route::delete('/events/{id}', [EventController::class, 'destroy']);
                Route::post('/events/{id}/image', [EventController::class, 'updateImage']);
                Route::delete('/events/{id}/image', [EventController::class, 'deleteImage']);

                // PAGES
                Route::post('/pages', [PageController::class, 'store']);
                Route::put('/pages/{id}', [PageController::class, 'update']);
                Route::delete('/pages/{id}', [PageController::class, 'destroy']);
                Route::post('/pages/{id}/image', [PageController::class, 'updateImage']);
                Route::delete('/pages/{id}/image', [PageController::class, 'deleteImage']);
                // NEWS
                Route::post('/news', [NewsController::class, 'store']);
                Route::put('/news/{id}', [NewsController::class, 'update']);
                Route::delete('/news/{id}', [NewsController::class, 'destroy']);
                Route::post('/news/{id}/image', [NewsController::class, 'updateImage']);
                Route::delete('/news/{id}/image', [NewsController::class, 'deleteImage']);
                // SERVICES
                Route::post('/services', [ServiceController::class, 'store']);
                Route::put('/services/{id}', [ServiceController::class, 'update']);
                Route::delete('/services/{id}', [ServiceController::class, 'destroy']);
                Route::post('/services/{id}/image', [ServiceController::class, 'updateImage']);
                Route::delete('/services/{id}/image', [ServiceController::class, 'deleteImage']);
            });

        //
        // ADMIN ONLY
        //
        Route::middleware(['auth:sanctum', 'role:admin', 'throttle:5,1'])
            ->group(function () {
                Route::get('/manage-users', function () {
                    return response()->json([
                        'message' => 'Admin can manage users'
                    ]);
                });
            });

        //
        // ALL AUTHENTICATED ROLES
        //
        Route::middleware(['auth:sanctum', 'role:admin,editor,user'])
            ->group(function () {
                Route::get('/view', function () {
                    return response()->json([
                        'message' => 'All roles can view'
                    ]);
                });
            });

    });

});