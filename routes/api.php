<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SearchController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Estas rutas retornan JSON y están pensadas para operaciones AJAX
| Automáticamente tienen prefijo /api y middleware 'api'
*/

// Rutas de imágenes (operaciones AJAX)
Route::post('images/upload', [ImageController::class, 'upload'])
    ->name('api.images.upload')
    ->middleware('auth:sanctum');

Route::put('images/{image}', [ImageController::class, 'update'])
    ->name('api.images.update')
    ->middleware('auth:sanctum');

Route::delete('images/{image}', [ImageController::class, 'destroy'])
    ->name('api.images.destroy')
    ->middleware('auth:sanctum');

Route::post('images/reorder', [ImageController::class, 'reorder'])
    ->name('api.images.reorder')
    ->middleware('auth:sanctum');

// Autocompletado de búsqueda
Route::get('search/autocomplete', [SearchController::class, 'autocomplete'])
    ->name('api.search.autocomplete');

// Esta línea crea automáticamente las rutas para index, store, show, update y destroy
Route::apiResource('products', ProductController::class);
