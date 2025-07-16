<?php

use App\Classes\ApiResponseClass;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->render(function (MethodNotAllowedHttpException $e) {

            return ApiResponseClass::errorResponse('Method not allowed', $e->getMessage(), 405);

        });
        $exceptions->render(function (NotFoundHttpException $e) {

            return ApiResponseClass::errorResponse('Endpoint not found', $e->getMessage(), 404);

        });
        $exceptions->render(function (Exception $e) {

            return ApiResponseClass::errorResponse('Internal Server Error', $e->getMessage(), 500);

        });
        $exceptions->render(function (Error $e) {

            return ApiResponseClass::errorResponse('Internal Server Error', $e->getMessage(), 500);

        });

    })->create();
