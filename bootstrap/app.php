<?php

use App\Exceptions\EmployeeNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // F02 · La excepción de dominio se convierte aquí en un 404 con
        // nuestra página de error: el usuario nunca ve la traza.
        $exceptions->render(function (EmployeeNotFoundException $e) {
            return response()->view('errors.404', ['message' => $e->getMessage()], 404);
        });
    })->create();
