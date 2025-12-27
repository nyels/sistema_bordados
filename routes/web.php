<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Ruta raíz
Route::get('/', function () {
    return view('auth.login');
});

// Autenticación
Auth::routes([
    'register' => false,
]);

// Home
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])
    ->name('home')
    ->middleware('auth');

/*
|--------------------------------------------------------------------------
| RUTAS DE DISEÑOS
|--------------------------------------------------------------------------
*/

Route::get('admin/designs', [App\Http\Controllers\DesignController::class, 'index'])
    ->name('admin.designs.index')
    ->middleware('auth');

Route::get('admin/designs/create', [App\Http\Controllers\DesignController::class, 'create'])
    ->name('admin.designs.create')
    ->middleware('auth');

// Ruta para limpiar imagen temporal
Route::post('admin/designs/clear-temp-image', [App\Http\Controllers\DesignController::class, 'clearTempImage'])
    ->name('admin.designs.clear-temp-image')
    ->middleware('auth');

Route::middleware(['secure.file.upload'])->group(function () {
    Route::post('admin/designs', [App\Http\Controllers\DesignController::class, 'store'])
        ->name('admin.designs.store')
        ->middleware('auth');
});

Route::get('admin/designs/{design}', [App\Http\Controllers\DesignController::class, 'show'])
    ->name('admin.designs.show')
    ->middleware('auth');

Route::get('admin/designs/{design}/edit', [App\Http\Controllers\DesignController::class, 'edit'])
    ->name('admin.designs.edit')
    ->middleware('auth');

Route::middleware(['secure.file.upload'])->group(function () {
    Route::put('admin/designs/{design}', [App\Http\Controllers\DesignController::class, 'update'])
        ->name('admin.designs.update')
        ->middleware('auth');
});

Route::delete('admin/designs/{design}', [App\Http\Controllers\DesignController::class, 'destroy'])
    ->name('admin.designs.destroy')
    ->middleware('auth');

/*
|--------------------------------------------------------------------------
| RUTAS DE VARIANTES
|--------------------------------------------------------------------------
*/

Route::get('admin/designs/{design}/variants/create', [App\Http\Controllers\DesignVariantController::class, 'create'])
    ->name('admin.designs.variants.create')
    ->middleware('auth');

Route::post('admin/designs/{design}/variants', [App\Http\Controllers\DesignVariantController::class, 'store'])
    ->name('admin.designs.variants.store')
    ->middleware('auth');

Route::get('admin/designs/{design}/variants/{variant}/edit', [App\Http\Controllers\DesignVariantController::class, 'edit'])
    ->name('admin.designs.variants.edit')
    ->middleware('auth');

Route::put('admin/designs/{design}/variants/{variant}', [App\Http\Controllers\DesignVariantController::class, 'update'])
    ->name('admin.designs.variants.update')
    ->middleware('auth');

Route::delete('admin/designs/{design}/variants/{variant}', [App\Http\Controllers\DesignVariantController::class, 'destroy'])
    ->name('admin.designs.variants.destroy')
    ->middleware('auth');

Route::delete('admin/designs/{design}/variants/{variant}/images/{image}', [App\Http\Controllers\DesignVariantController::class, 'destroyImage'])
    ->name('admin.designs.variants.images.destroy')
    ->middleware('auth');

/*
|--------------------------------------------------------------------------
| RUTAS DE PRODUCCIÓN / EXPORTACIONES
|--------------------------------------------------------------------------
*/

// Módulo independiente "Producción" (menú lateral)
Route::get('admin/production', [App\Http\Controllers\DesignExportController::class, 'index'])
    ->name('admin.production.index')
    ->middleware('auth');

Route::get('admin/production/create', [App\Http\Controllers\DesignExportController::class, 'create'])
    ->name('admin.production.create')
    ->middleware('auth');

Route::post('admin/production', [App\Http\Controllers\DesignExportController::class, 'store'])
    ->name('admin.production.store')
    ->middleware('auth');

Route::get('admin/production/{export}', [App\Http\Controllers\DesignExportController::class, 'show'])
    ->name('admin.production.show')
    ->middleware('auth');

Route::get('admin/production/{export}/edit', [App\Http\Controllers\DesignExportController::class, 'edit'])
    ->name('admin.production.edit')
    ->middleware('auth');

Route::get('admin/production/{export}/download', [App\Http\Controllers\DesignExportController::class, 'download'])
    ->name('admin.production.download')
    ->middleware('auth');

Route::put('admin/production/{export}', [App\Http\Controllers\DesignExportController::class, 'update'])
    ->name('admin.production.update')
    ->middleware('auth');

Route::delete('admin/production/{export}', [App\Http\Controllers\DesignExportController::class, 'destroy'])
    ->name('admin.production.destroy')
    ->middleware('auth');

// Exportaciones para un diseño específico (sin variante)
Route::get('admin/designs/{design}/exports', [App\Http\Controllers\DesignExportController::class, 'forDesign'])
    ->name('admin.designs.exports.index')
    ->middleware('auth');

Route::get('admin/designs/{design}/exports/create', [App\Http\Controllers\DesignExportController::class, 'createForDesign'])
    ->name('admin.designs.exports.create')
    ->middleware('auth');

Route::post('admin/designs/{design}/exports', [App\Http\Controllers\DesignExportController::class, 'storeForDesign'])
    ->name('admin.designs.exports.store')
    ->middleware('auth');

// Exportaciones para una variante específica
Route::get('admin/designs/{design}/variants/{variant}/exports/create', [App\Http\Controllers\DesignExportController::class, 'createForVariant'])
    ->name('admin.variants.exports.create')
    ->middleware('auth');

Route::post('admin/designs/{design}/variants/{variant}/exports', [App\Http\Controllers\DesignExportController::class, 'storeForVariant'])
    ->name('admin.variants.exports.store')
    ->middleware('auth');

/*
|--------------------------------------------------------------------------
| RUTAS AJAX PARA EXPORTACIONES (MODAL)
|--------------------------------------------------------------------------
*/

// Analizar archivo de bordado (PyEmbroidery)
Route::post('admin/exports/analyze', [App\Http\Controllers\DesignExportController::class, 'analyzeFile'])
    ->name('admin.exports.analyze')
    ->middleware('auth');

// Guardar exportación via AJAX (desde modal)
Route::post('admin/exports/store-ajax', [App\Http\Controllers\DesignExportController::class, 'storeAjax'])
    ->name('admin.exports.store-ajax')
    ->middleware('auth');

// ⭐ ACTUALIZAR EXPORTACIÓN VIA AJAX - PUT
Route::put('admin/exports/{export}/update-ajax', [App\Http\Controllers\DesignExportController::class, 'updateAjax'])
    ->name('admin.exports.update-ajax')
    ->middleware('auth');

// ⭐ ACTUALIZAR EXPORTACIÓN VIA AJAX - POST (para compatibilidad con _method)
Route::post('admin/exports/{export}/update-ajax', [App\Http\Controllers\DesignExportController::class, 'updateAjax'])
    ->name('admin.exports.update-ajax-post')
    ->middleware('auth');

// ⭐ ACTUALIZAR ESTADO DE EXPORTACIÓN VIA AJAX
Route::post('admin/exports/{export}/status', [App\Http\Controllers\DesignExportController::class, 'updateStatus'])
    ->name('admin.exports.update-status')
    ->middleware('auth');

// Obtener exportación específica via AJAX
Route::get('admin/exports/{export}/ajax', [App\Http\Controllers\DesignExportController::class, 'getExport'])
    ->name('admin.exports.get-ajax')
    ->middleware('auth');

// Eliminar exportación via AJAX
Route::delete('admin/exports/{export}/ajax', [App\Http\Controllers\DesignExportController::class, 'destroyAjax'])
    ->name('admin.exports.destroy-ajax')
    ->middleware('auth');

// Obtener tipos de aplicación
Route::get('admin/exports/application-types', [App\Http\Controllers\DesignExportController::class, 'getApplicationTypes'])
    ->name('admin.exports.application-types')
    ->middleware('auth');

// Obtener extensiones permitidas
Route::get('admin/exports/allowed-extensions', [App\Http\Controllers\DesignExportController::class, 'getAllowedExtensions'])
    ->name('admin.exports.allowed-extensions')
    ->middleware('auth');

// Obtener exportaciones de diseño (AJAX para modal)
Route::get('admin/designs/{design}/exports/ajax', [App\Http\Controllers\DesignExportController::class, 'getDesignExports'])
    ->name('admin.designs.exports.ajax')
    ->middleware('auth');

// Obtener exportaciones de variante (AJAX para modal)
Route::get('admin/designs/{design}/variants/{variant}/exports/ajax', [App\Http\Controllers\DesignExportController::class, 'getVariantExports'])
    ->name('admin.designs.variants.exports.ajax')
    ->middleware('auth');

// ⭐ NUEVO: Obtener TODAS las exportaciones de un diseño agrupadas por variante
Route::get('admin/designs/{design}/exports-grouped', [App\Http\Controllers\DesignExportController::class, 'getAllDesignExportsGrouped'])
    ->name('admin.designs.exports-grouped')
    ->middleware('auth');

// ⭐ CONTADOR DE EXPORTACIONES (para actualizar cards en tiempo real)
Route::get('admin/designs/{design}/exports-count', [App\Http\Controllers\DesignExportController::class, 'getExportsCount'])
    ->name('admin.designs.exports-count')
    ->middleware('auth');

/*
|--------------------------------------------------------------------------
| RUTAS DE CATEGORÍAS
|--------------------------------------------------------------------------
*/
Route::get('admin/categories', [App\Http\Controllers\CategoryController::class, 'index'])
    ->name('admin.categories.index')
    ->middleware('auth');

Route::get('admin/categories/create', [App\Http\Controllers\CategoryController::class, 'create'])
    ->name('admin.categories.create')
    ->middleware('auth');

Route::post('admin/categories', [App\Http\Controllers\CategoryController::class, 'store'])
    ->name('admin.categories.store')
    ->middleware('auth');

Route::get('admin/categories/{category}', [App\Http\Controllers\CategoryController::class, 'show'])
    ->name('admin.categories.show')
    ->middleware('auth');

Route::get('admin/categories/{category}/edit', [App\Http\Controllers\CategoryController::class, 'edit'])
    ->name('admin.categories.edit')
    ->middleware('auth');

Route::put('admin/categories/{category}', [App\Http\Controllers\CategoryController::class, 'update'])
    ->name('admin.categories.update')
    ->middleware('auth');

Route::delete('admin/categories/{category}', [App\Http\Controllers\CategoryController::class, 'destroy'])
    ->name('admin.categories.destroy')
    ->middleware('auth');

/*
|--------------------------------------------------------------------------
| RUTAS DE BÚSQUEDA
|--------------------------------------------------------------------------
*/

Route::get('admin/search', [App\Http\Controllers\SearchController::class, 'index'])
    ->name('admin.search.index')
    ->middleware('auth');

/*
|--------------------------------------------------------------------------
| RUTAS ANTIGUAS (MANTENER SI ESTÁN EN USO)
|--------------------------------------------------------------------------
*/

// Giros
Route::get('/giros', [App\Http\Controllers\GiroController::class, 'index'])
    ->name('admin.giros.index')
    ->middleware('auth');
Route::get('/giros/nuevo', [App\Http\Controllers\GiroController::class, 'create'])
    ->name('admin.giros.create')
    ->middleware('auth');
Route::post('/giros/create', [App\Http\Controllers\GiroController::class, 'store'])
    ->name('admin.giros.store')
    ->middleware('auth');
Route::get('/giros/edit/{id}', [App\Http\Controllers\GiroController::class, 'edit'])
    ->name('admin.giros.edit')
    ->middleware('auth');
Route::put('/giros/edit/{id}', [App\Http\Controllers\GiroController::class, 'update'])
    ->name('admin.giros.update')
    ->middleware('auth');
Route::get('/giros/confirm_delete/{id}', [App\Http\Controllers\GiroController::class, 'confirm_delete'])
    ->name('admin.giros.confirm_delete')
    ->middleware('auth');
Route::delete('/giros/delete/{id}', [App\Http\Controllers\GiroController::class, 'destroy'])
    ->name('admin.giros.destroy')
    ->middleware('auth');

// Estados
Route::get('/estados', [App\Http\Controllers\EstadoController::class, 'index'])
    ->name('admin.estados.index')
    ->middleware('auth');
Route::get('/estados/nuevo', [App\Http\Controllers\EstadoController::class, 'create'])
    ->name('admin.estados.create')
    ->middleware('auth');
Route::post('/estados/create', [App\Http\Controllers\EstadoController::class, 'store'])
    ->name('admin.estados.store')
    ->middleware('auth');
Route::get('/estados/edit/{id}', [App\Http\Controllers\EstadoController::class, 'edit'])
    ->name('admin.estados.edit')
    ->middleware('auth');
Route::put('/estados/edit/{id}', [App\Http\Controllers\EstadoController::class, 'update'])
    ->name('admin.estados.update')
    ->middleware('auth');
Route::get('/estados/confirm_delete/{id}', [App\Http\Controllers\EstadoController::class, 'confirm_delete'])
    ->name('admin.estados.confirm_delete')
    ->middleware('auth');
Route::delete('/estados/delete/{id}', [App\Http\Controllers\EstadoController::class, 'destroy'])
    ->name('admin.estados.destroy')
    ->middleware('auth');

//Category
Route::get('/categorias', [App\Http\Controllers\CategoryController::class, 'index'])
    ->name('admin.categorias.index')
    ->middleware('auth');
Route::get('/categorias/nuevo', [App\Http\Controllers\CategoryController::class, 'create'])
    ->name('admin.categorias.create')
    ->middleware('auth');
Route::post('/categorias/create', [App\Http\Controllers\CategoryController::class, 'store'])
    ->name('admin.categorias.store')
    ->middleware('auth');
Route::get('/categorias/edit/{category}', [App\Http\Controllers\CategoryController::class, 'edit'])
    ->name('admin.categorias.edit')
    ->middleware('auth');

Route::put('/categorias/edit/{category}', [App\Http\Controllers\CategoryController::class, 'update'])
    ->name('admin.categorias.update')
    ->middleware('auth');
Route::get('/categorias/confirm_delete/{category}', [App\Http\Controllers\CategoryController::class, 'confirm_delete'])
    ->name('admin.categorias.confirm_delete')
    ->middleware('auth');
Route::delete('/categorias/delete/{category}', [App\Http\Controllers\CategoryController::class, 'destroy'])
    ->name('admin.categorias.destroy')
    ->middleware('auth');

// Proveedores
Route::get('/proveedores', [App\Http\Controllers\ProveedorController::class, 'index'])
    ->name('admin.proveedores.index')
    ->middleware('auth');
Route::get('/proveedores/nuevo', [App\Http\Controllers\ProveedorController::class, 'create'])
    ->name('admin.proveedores.create')
    ->middleware('auth');
Route::post('/proveedores/create', [App\Http\Controllers\ProveedorController::class, 'store'])
    ->name('admin.proveedores.store')
    ->middleware('auth');
Route::get('/proveedores/edit/{id}', [App\Http\Controllers\ProveedorController::class, 'edit'])
    ->name('admin.proveedores.edit')
    ->middleware('auth');
Route::put('/proveedores/edit/{id}', [App\Http\Controllers\ProveedorController::class, 'update'])
    ->name('admin.proveedores.update')
    ->middleware('auth');
Route::get('/proveedores/confirm_delete/{id}', [App\Http\Controllers\ProveedorController::class, 'confirm_delete'])
    ->name('admin.proveedores.confirm_delete')
    ->middleware('auth');
Route::delete('/proveedores/delete/{id}', [App\Http\Controllers\ProveedorController::class, 'destroy'])
    ->name('admin.proveedores.destroy')
    ->middleware('auth');

// Recomendaciones
Route::get('/recomendaciones', [App\Http\Controllers\RecomendacionController::class, 'index'])
    ->name('admin.recomendaciones.index')
    ->middleware('auth');
Route::get('/recomendaciones/nuevo', [App\Http\Controllers\RecomendacionController::class, 'create'])
    ->name('admin.recomendaciones.create')
    ->middleware('auth');
Route::post('/recomendaciones/create', [App\Http\Controllers\RecomendacionController::class, 'store'])
    ->name('admin.recomendaciones.store')
    ->middleware('auth');
Route::get('/recomendaciones/edit/{id}', [App\Http\Controllers\RecomendacionController::class, 'edit'])
    ->name('admin.recomendaciones.edit')
    ->middleware('auth');
Route::put('/recomendaciones/edit/{id}', [App\Http\Controllers\RecomendacionController::class, 'update'])
    ->name('admin.recomendaciones.update')
    ->middleware('auth');
Route::get('/recomendaciones/confirm_delete/{id}', [App\Http\Controllers\RecomendacionController::class, 'confirm_delete'])
    ->name('admin.recomendaciones.confirm_delete')
    ->middleware('auth');
Route::delete('/recomendaciones/delete/{id}', [App\Http\Controllers\RecomendacionController::class, 'destroy'])
    ->name('admin.recomendaciones.destroy')
    ->middleware('auth');

// Clientes
Route::get('/clientes', [App\Http\Controllers\ClienteController::class, 'index'])
    ->name('admin.clientes.index')
    ->middleware('auth');
Route::get('/clientes/nuevo', [App\Http\Controllers\ClienteController::class, 'create'])
    ->name('admin.clientes.create')
    ->middleware('auth');
Route::post('/clientes/create', [App\Http\Controllers\ClienteController::class, 'store'])
    ->name('admin.clientes.store')
    ->middleware('auth');
Route::get('/clientes/edit/{id}', [App\Http\Controllers\ClienteController::class, 'edit'])
    ->name('admin.clientes.edit')
    ->middleware('auth');
Route::put('/clientes/edit/{id}', [App\Http\Controllers\ClienteController::class, 'update'])
    ->name('admin.clientes.update')
    ->middleware('auth');
Route::get('/clientes/confirm_delete/{id}', [App\Http\Controllers\ClienteController::class, 'confirm_delete'])
    ->name('admin.clientes.confirm_delete')
    ->middleware('auth');
Route::delete('/clientes/delete/{id}', [App\Http\Controllers\ClienteController::class, 'destroy'])
    ->name('admin.clientes.destroy')
    ->middleware('auth');

// Tipos de aplicacion
Route::get('/tipos_aplicacion', [App\Http\Controllers\ApplicationTypesController::class, 'index'])
    ->name('admin.tipos_aplicacion.index')
    ->middleware('auth');

Route::get('/tipos_aplicacion/nuevo', [App\Http\Controllers\ApplicationTypesController::class, 'create'])
    ->name('admin.tipos_aplicacion.create')
    ->middleware('auth');

Route::post('/tipos_aplicacion/guardar', [App\Http\Controllers\ApplicationTypesController::class, 'store'])
    ->name('admin.tipos_aplicacion.store')
    ->middleware('auth');

Route::get('/tipos_aplicacion/editar/{id}', [App\Http\Controllers\ApplicationTypesController::class, 'edit'])
    ->name('admin.tipos_aplicacion.edit')
    ->middleware('auth');

Route::put('/tipos_aplicacion/actualizar/{id}', [App\Http\Controllers\ApplicationTypesController::class, 'update'])
    ->name('admin.tipos_aplicacion.update')
    ->middleware('auth');

Route::get('/tipos_aplicacion/confirmar-eliminacion/{id}', [App\Http\Controllers\ApplicationTypesController::class, 'confirm_delete'])
    ->name('admin.tipos_aplicacion.confirm_delete')
    ->middleware('auth');

Route::delete('/tipos_aplicacion/eliminar/{id}', [App\Http\Controllers\ApplicationTypesController::class, 'destroy'])
    ->name('admin.tipos_aplicacion.destroy')
    ->middleware('auth');

// Búsqueda AJAX
Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::get('search/ajax', [App\Http\Controllers\SearchController::class, 'searchAjax'])
        ->name('admin.search.ajax');

    Route::get('search/autocomplete', [App\Http\Controllers\SearchController::class, 'autocomplete'])
        ->name('admin.search.autocomplete');
});
