<?php

/*
|--------------------------------------------------------------------------
| RUTAS DE BÚSQUEDA AVANZADA
|--------------------------------------------------------------------------
| 
| Agregar estas rutas a routes/web.php
|
*/

use App\Http\Controllers\SearchController;

// Grupo de rutas de búsqueda con autenticación
Route::middleware(['auth'])->prefix('admin')->group(function () {
    
    // Búsqueda principal (página y AJAX)
    Route::get('search', [SearchController::class, 'index'])
        ->name('admin.search.index');
    
    // Endpoint AJAX para búsqueda en tiempo real
    Route::get('search/ajax', [SearchController::class, 'searchAjax'])
        ->name('admin.search.ajax');
    
    // Autocompletado
    Route::get('search/autocomplete', [SearchController::class, 'autocomplete'])
        ->name('admin.search.autocomplete');
    
    // Health check del sistema de búsqueda (para monitoreo)
    Route::get('search/health', [SearchController::class, 'healthCheck'])
        ->name('admin.search.health');
    
    // Estadísticas del índice (solo admin)
    Route::get('search/stats', [SearchController::class, 'stats'])
        ->name('admin.search.stats');
});

/*
|--------------------------------------------------------------------------
| RUTAS PARA API (opcional, para apps móviles o SPA)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum'])->prefix('api/v1')->group(function () {
    
    Route::get('search', [SearchController::class, 'searchAjax'])
        ->name('api.search');
    
    Route::get('search/autocomplete', [SearchController::class, 'autocomplete'])
        ->name('api.search.autocomplete');
});
