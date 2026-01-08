<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        //  api: __DIR__ . '/../routes/api.php', // <--- ASEGÚRATE DE QUE ESTA LÍNEA ESTÉ AQUÍ
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // ✅ CORREGIDO: Solo registrar como alias, NO como middleware global
        $middleware->alias([
            'secure.file.upload' => \App\Http\Middleware\SecureFileUpload::class,
        ]);

        // ✅ Asegurar que el middleware se aplique solo a rutas específicas
        $middleware->appendToGroup('web', [
            \App\Http\Middleware\SecureFileUpload::class,
            \App\Http\Middleware\PreventBackHistory::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
