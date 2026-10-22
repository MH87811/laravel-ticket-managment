<?php

use App\Http\Middleware\JwtMiddleware;
use App\Http\Responses\Response as ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'jwt' => JwtMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        $exceptions->render(
            function (ValidationException $e, Request $request) {
                if (!$request->is('api/*') && !$request->expectsJson()) {
                    return null;
                }
                return app(ApiResponse::class)->error(
                    message: 'Validation failed',
                    error: $e->errors(),
                    error_code: 'VALIDATION_FAILURE',
                    status_code: 422,
                );
            }
        );
        $exceptions->render(
            function (ModelNotFoundException $e, Request $request) {
                if (!$request->is('api/*') && !$request->expectsJson()) {
                    return null;
                }
                return app(ApiResponse::class)->notFoundError(class_basename($e->getModel()));
            }
        );
//        $exceptions->render(
//            function (NotFoundHttpException $e, Request $request) {
//                if (!$request->is('api/*') && !$request->expectsJson()) {
//                    return null;
//                }
//                return app(ApiResponse::class)->notFoundError();
//            }
//        );
        $exceptions->render(
            function (UnauthorizedException $e, Request $request) {
                if (!$request->is('api/*') && !$request->expectsJson()) {
                    return null;
                }
                return app(ApiResponse::class)->unauthorizedError(null);
            }
        );
        $exceptions->render(
            function (AuthenticationException $e, Request $request) {
                if (!$request->is('api/*') && !$request->expectsJson()) {
                    return null;
                }

                $action = $request->route()?->getName() ?? 'access resource';

                return app(ApiResponse::class)->unauthorizedError(action: $action);
            }
        );
        $exceptions->render(
            function (AuthorizationException $e, Request $request) {
                if (!$request->is('api/*') && !$request->expectsJson()) {
                    return null;
                }

                $accessResponse = $e->response();
                $reason = $accessResponse->message();

                return app(ApiResponse::class)->permissionDeniedError(
                    action: $request->route()->getName() ?? 'access resource',
                    reason: $reason
                );
            }
        );
        $exceptions->render(
            function (\Throwable $e, Request $request) {
                if (!$request->is('api/*') && !$request->expectsJson()) {
                    return null;
                }
                return app(ApiResponse::class)->internalServerError();
            }
        );
    })->create();
