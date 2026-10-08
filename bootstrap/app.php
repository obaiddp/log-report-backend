<?php

use App\Http\Middleware\EnsureAuthenticatedUserIsActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use App\Http\Middleware\CheckPermission;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // ->withMiddleware(function (Middleware $middleware): void {
    //     $middleware->statefulApi();
        
    //     $middleware->redirectGuestsTo(
    //     fn (Request $request) =>
    //         $request->is('api/*') ? null : route('login')
    //     );

    //     $middleware->alias([
    //         'active' => EnsureAuthenticatedUserIsActive::class,
    //         'permission' => CheckPermission::class,
    //     ]);
    // })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->statefulApi();

        $middleware->redirectGuestsTo(
            fn (Request $request) =>
                $request->is('api/*') ? null : route('login')
        );

        $middleware->alias([
            'active' => EnsureAuthenticatedUserIsActive::class,
            'permission' => CheckPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
