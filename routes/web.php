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

// AJAX: Listado de diseños sin reload (para Web App)
Route::get('admin/designs/ajax-list', [App\Http\Controllers\DesignController::class, 'ajaxList'])
    ->name('admin.designs.ajax-list')
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

// ⭐ CONTADOR DE EXPORTACIONES SIN IMAGE_ID (para badge de imagen principal del diseño)
Route::get('admin/designs/{design}/exports-without-image-count', [App\Http\Controllers\DesignExportController::class, 'getExportsWithoutImageCount'])
    ->name('admin.designs.exports-without-image-count')
    ->middleware('auth');

// ⭐ CONTADOR DE EXPORTACIONES POR IMAGEN (para badges en galería)
Route::get('admin/images/{image}/exports-count', [App\Http\Controllers\DesignExportController::class, 'getImageExportsCount'])
    ->name('admin.images.exports-count')
    ->middleware('auth');

// ⭐ CONTADORES BATCH DE EXPORTACIONES (múltiples imágenes en una sola petición)
Route::post('admin/images/exports-counts-batch', [App\Http\Controllers\DesignExportController::class, 'getImagesExportsCounts'])
    ->name('admin.images.exports-counts-batch')
    ->middleware('auth');

// ⭐ OBTENER EXPORTACIONES POR IMAGEN
Route::get('admin/images/{image}/exports', [App\Http\Controllers\DesignExportController::class, 'getImageExports'])
    ->name('admin.images.exports')
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

//rutas produccion
Route::group(['prefix' => 'produccion', 'as' => 'admin.produccion.', 'middleware' => ['auth']], function () {
    Route::get('/', [App\Http\Controllers\ProduccionController::class, 'index'])->name('index');
    Route::get('/nuevo', [App\Http\Controllers\ProduccionController::class, 'create'])->name('create');
    Route::post('/', [App\Http\Controllers\ProduccionController::class, 'store'])->name('store');
    Route::get('/{id}', [App\Http\Controllers\ProduccionController::class, 'show'])->name('show');
    Route::get('/{id}/editar', [App\Http\Controllers\ProduccionController::class, 'edit'])->name('edit');
    Route::put('/{id}', [App\Http\Controllers\ProduccionController::class, 'update'])->name('update');
    Route::delete('/{id}', [App\Http\Controllers\ProduccionController::class, 'destroy'])->name('destroy');

    // Acciones de estado
    Route::post('/{id}/solicitar', [App\Http\Controllers\ProduccionController::class, 'requestApproval'])->name('request');
    Route::post('/{id}/aprobar', [App\Http\Controllers\ProduccionController::class, 'approve'])->name('approve');
    Route::post('/{id}/archivar', [App\Http\Controllers\ProduccionController::class, 'archive'])->name('archive');
    Route::post('/{id}/revertir', [App\Http\Controllers\ProduccionController::class, 'revert'])->name('revert');
    Route::post('/{id}/restaurar', [App\Http\Controllers\ProduccionController::class, 'restore'])->name('restore');

    // Nueva ruta para vista previa SVG (On-demand y Cacheada)
    Route::get('/{export}/preview', [App\Http\Controllers\DesignPreviewController::class, 'preview'])->name('preview');
});



/*
|--------------------------------------------------------------------------
| RUTAS DE SISTEMA (CONFIGURACIÓN, LOGS, UNIDADES)
|--------------------------------------------------------------------------
*/

// Settings (Configuración del Sistema)
Route::get('admin/settings', [App\Http\Controllers\SystemSettingController::class, 'index'])
    ->name('settings.index')
    ->middleware('auth');

Route::get('admin/settings/create', [App\Http\Controllers\SystemSettingController::class, 'create'])
    ->name('settings.create')
    ->middleware('auth');

Route::post('admin/settings', [App\Http\Controllers\SystemSettingController::class, 'store'])
    ->name('settings.store')
    ->middleware('auth');

Route::get('admin/settings/{setting}', [App\Http\Controllers\SystemSettingController::class, 'show'])
    ->name('settings.show')
    ->middleware('auth');

Route::get('admin/settings/{setting}/edit', [App\Http\Controllers\SystemSettingController::class, 'edit'])
    ->name('settings.edit')
    ->middleware('auth');

Route::put('admin/settings/{setting}', [App\Http\Controllers\SystemSettingController::class, 'update'])
    ->name('settings.update')
    ->middleware('auth');

Route::delete('admin/settings/{setting}', [App\Http\Controllers\SystemSettingController::class, 'destroy'])
    ->name('settings.destroy')
    ->middleware('auth');

// Activity Logs (Registro de Actividad)
Route::get('admin/activity-logs', [App\Http\Controllers\ActivityLogController::class, 'index'])
    ->name('activity-logs.index')
    ->middleware('auth');

Route::get('admin/activity-logs/{activityLog}', [App\Http\Controllers\ActivityLogController::class, 'show'])
    ->name('activity-logs.show')
    ->middleware('auth');

/*
|--------------------------------------------------------------------------
| Unidades de Medida
|--------------------------------------------------------------------------
| Rutas para la gestión completa de unidades de medida del sistema.
| Incluye CRUD completo, cambio de estado, restauración y eliminación permanente.
|--------------------------------------------------------------------------
*/
/*
|--------------------------------------------------------------------------
| RUTAS DE UNIDADES DE MEDIDA
|--------------------------------------------------------------------------
*/

Route::get('/units', [App\Http\Controllers\UnitController::class, 'index'])
    ->name('units.index')
    ->middleware('auth');

Route::get('/units/create', [App\Http\Controllers\UnitController::class, 'create'])
    ->name('units.create')
    ->middleware('auth');

Route::post('/units', [App\Http\Controllers\UnitController::class, 'store'])
    ->name('units.store')
    ->middleware('auth');

Route::get('/units/{id}/edit', [App\Http\Controllers\UnitController::class, 'edit'])
    ->name('units.edit')
    ->middleware('auth');

Route::put('/units/{id}', [App\Http\Controllers\UnitController::class, 'update'])
    ->name('units.update')
    ->middleware('auth');

Route::get('/units/{id}/confirm-delete', [App\Http\Controllers\UnitController::class, 'confirmDelete'])
    ->name('units.confirm_delete')
    ->middleware('auth');

Route::delete('/units/{id}', [App\Http\Controllers\UnitController::class, 'destroy'])
    ->name('units.destroy')
    ->middleware('auth');

/*
|--------------------------------------------------------------------------
| RUTAS DE CONFIGURACIÓN DEL SISTEMA
|--------------------------------------------------------------------------
*/

Route::get('/settings', [App\Http\Controllers\SystemSettingController::class, 'index'])
    ->name('admin.settings.index')
    ->middleware('auth');

Route::put('/settings', [App\Http\Controllers\SystemSettingController::class, 'update'])
    ->name('admin.settings.update')
    ->middleware('auth');

/*
|--------------------------------------------------------------------------
| RUTAS DE REGISTRO DE ACTIVIDAD
|--------------------------------------------------------------------------
*/

Route::get('/activity-logs', [App\Http\Controllers\ActivityLogController::class, 'index'])
    ->name('activity-logs.index')
    ->middleware('auth');

// Cambiamos {id} por {uuid} para ser semánticamente correctos
Route::get('/activity-logs/{uuid}', [App\Http\Controllers\ActivityLogController::class, 'show'])
    ->name('activity-logs.show')
    ->middleware('auth');

/*
|--------------------------------------------------------------------------
| RUTAS DE CATEGORÍAS DE MATERIALES
|--------------------------------------------------------------------------
*/

Route::get('/material-categories', [App\Http\Controllers\MaterialCategoryController::class, 'index'])
    ->name('material-categories.index')
    ->middleware('auth');

Route::get('/material-categories/create', [App\Http\Controllers\MaterialCategoryController::class, 'create'])
    ->name('material-categories.create')
    ->middleware('auth');

Route::post('/material-categories', [App\Http\Controllers\MaterialCategoryController::class, 'store'])
    ->name('material-categories.store')
    ->middleware('auth');

Route::get('/material-categories/{id}/edit', [App\Http\Controllers\MaterialCategoryController::class, 'edit'])
    ->name('material-categories.edit')
    ->middleware('auth');

Route::put('/material-categories/{id}', [App\Http\Controllers\MaterialCategoryController::class, 'update'])
    ->name('material-categories.update')
    ->middleware('auth');

Route::get('/material-categories/{id}/confirm-delete', [App\Http\Controllers\MaterialCategoryController::class, 'confirmDelete'])
    ->name('material-categories.confirm_delete')
    ->middleware('auth');

Route::delete('/material-categories/{id}', [App\Http\Controllers\MaterialCategoryController::class, 'destroy'])
    ->name('material-categories.destroy')
    ->middleware('auth');

/*
|--------------------------------------------------------------------------
| RUTAS DE MATERIALES
|--------------------------------------------------------------------------
*/

Route::get('/materials', [App\Http\Controllers\MaterialController::class, 'index'])
    ->name('materials.index')
    ->middleware('auth');

Route::get('/materials/create', [App\Http\Controllers\MaterialController::class, 'create'])
    ->name('materials.create')
    ->middleware('auth');

Route::post('/materials', [App\Http\Controllers\MaterialController::class, 'store'])
    ->name('materials.store')
    ->middleware('auth');

Route::get('/materials/{id}/edit', [App\Http\Controllers\MaterialController::class, 'edit'])
    ->name('materials.edit')
    ->middleware('auth');

Route::put('/materials/{id}', [App\Http\Controllers\MaterialController::class, 'update'])
    ->name('materials.update')
    ->middleware('auth');

Route::get('/materials/{id}/confirm-delete', [App\Http\Controllers\MaterialController::class, 'confirmDelete'])
    ->name('materials.confirm_delete')
    ->middleware('auth');

Route::delete('/materials/{id}', [App\Http\Controllers\MaterialController::class, 'destroy'])
    ->name('materials.destroy')
    ->middleware('auth');

Route::get('/materials/category/{categoryId}', [App\Http\Controllers\MaterialController::class, 'getByCategory'])
    ->name('materials.by-category')
    ->middleware('auth');

/*
|--------------------------------------------------------------------------
| RUTAS DE VARIANTES DE MATERIALES
|--------------------------------------------------------------------------
*/

Route::get('/materials/{materialId}/variants', [App\Http\Controllers\MaterialVariantController::class, 'index'])
    ->name('material-variants.index')
    ->middleware('auth');

Route::get('/materials/{materialId}/variants/create', [App\Http\Controllers\MaterialVariantController::class, 'create'])
    ->name('material-variants.create')
    ->middleware('auth');

Route::post('/materials/{materialId}/variants', [App\Http\Controllers\MaterialVariantController::class, 'store'])
    ->name('material-variants.store')
    ->middleware('auth');

Route::get('/materials/{materialId}/variants/{id}/edit', [App\Http\Controllers\MaterialVariantController::class, 'edit'])
    ->name('material-variants.edit')
    ->middleware('auth');

Route::put('/materials/{materialId}/variants/{id}', [App\Http\Controllers\MaterialVariantController::class, 'update'])
    ->name('material-variants.update')
    ->middleware('auth');

Route::get('/materials/{materialId}/variants/{id}/confirm-delete', [App\Http\Controllers\MaterialVariantController::class, 'confirmDelete'])
    ->name('material-variants.confirm_delete')
    ->middleware('auth');

Route::delete('/materials/{materialId}/variants/{id}', [App\Http\Controllers\MaterialVariantController::class, 'destroy'])
    ->name('material-variants.destroy')
    ->middleware('auth');

Route::get('/materials/{materialId}/variants-json', [App\Http\Controllers\MaterialVariantController::class, 'getByMaterial'])
    ->name('material-variants.by-material')
    ->middleware('auth');
/*
|--------------------------------------------------------------------------
| RUTAS DE CONVERSIONES DE UNIDADES POR MATERIAL
|--------------------------------------------------------------------------
*/

Route::get('/materials/{materialId}/conversions', [App\Http\Controllers\MaterialUnitConversionController::class, 'index'])
    ->name('material-conversions.index')
    ->middleware('auth');

Route::get('/materials/{materialId}/conversions/create', [App\Http\Controllers\MaterialUnitConversionController::class, 'create'])
    ->name('material-conversions.create')
    ->middleware('auth');

Route::post('/materials/{materialId}/conversions', [App\Http\Controllers\MaterialUnitConversionController::class, 'store'])
    ->name('material-conversions.store')
    ->middleware('auth');

Route::get('/materials/{materialId}/conversions/{id}/edit', [App\Http\Controllers\MaterialUnitConversionController::class, 'edit'])
    ->name('material-conversions.edit')
    ->middleware('auth');

Route::put('/materials/{materialId}/conversions/{id}', [App\Http\Controllers\MaterialUnitConversionController::class, 'update'])
    ->name('material-conversions.update')
    ->middleware('auth');

Route::get('/materials/{materialId}/conversions/{id}/confirm-delete', [App\Http\Controllers\MaterialUnitConversionController::class, 'confirmDelete'])
    ->name('material-conversions.confirm_delete')
    ->middleware('auth');

Route::delete('/materials/{materialId}/conversions/{id}', [App\Http\Controllers\MaterialUnitConversionController::class, 'destroy'])
    ->name('material-conversions.destroy')
    ->middleware('auth');

Route::get('/materials/{materialId}/conversion-factor/{fromUnitId}', [App\Http\Controllers\MaterialUnitConversionController::class, 'getConversionFactor'])
    ->name('material-conversions.factor')
    ->middleware('auth');

/*
|--------------------------------------------------------------------------
| RUTAS DE COMPRAS (PURCHASES) - FORMATO EXPLÍCITO
|--------------------------------------------------------------------------
*/

// Listado
Route::get('purchases', [App\Http\Controllers\PurchaseController::class, 'index'])
    ->middleware('auth')
    ->name('admin.purchases.index');

// Crear
Route::get('purchases/create', [App\Http\Controllers\PurchaseController::class, 'create'])
    ->middleware('auth')
    ->name('admin.purchases.create');

Route::post('purchases', [App\Http\Controllers\PurchaseController::class, 'store'])
    ->middleware('auth')
    ->name('admin.purchases.store');

// Ver detalle
Route::get('purchases/{id}', [App\Http\Controllers\PurchaseController::class, 'show'])
    ->middleware('auth')
    ->name('admin.purchases.show')
    ->where('id', '[0-9]+');

// Editar
Route::get('purchases/{id}/edit', [App\Http\Controllers\PurchaseController::class, 'edit'])
    ->middleware('auth')
    ->name('admin.purchases.edit')
    ->where('id', '[0-9]+');

Route::put('purchases/{id}', [App\Http\Controllers\PurchaseController::class, 'update'])
    ->middleware('auth')
    ->name('admin.purchases.update')
    ->where('id', '[0-9]+');

// Confirmar (Borrador → Pendiente)
Route::post('purchases/{id}/confirm', [App\Http\Controllers\PurchaseController::class, 'confirm'])
    ->middleware('auth')
    ->name('admin.purchases.confirm')
    ->where('id', '[0-9]+');

// Recibir mercancía
Route::get('purchases/{id}/receive', [App\Http\Controllers\PurchaseController::class, 'showReceive'])
    ->middleware('auth')
    ->name('admin.purchases.receive')
    ->where('id', '[0-9]+');

Route::post('purchases/{id}/receive', [App\Http\Controllers\PurchaseController::class, 'receive'])
    ->middleware('auth')
    ->name('admin.purchases.receive.store')
    ->where('id', '[0-9]+');

// Cancelar
Route::get('purchases/{id}/cancel', [App\Http\Controllers\PurchaseController::class, 'showCancel'])
    ->middleware('auth')
    ->name('admin.purchases.cancel')
    ->where('id', '[0-9]+');

Route::post('purchases/{id}/cancel', [App\Http\Controllers\PurchaseController::class, 'cancel'])
    ->middleware('auth')
    ->name('admin.purchases.cancel.store')
    ->where('id', '[0-9]+');

// Eliminar (solo borradores)
Route::get('purchases/{id}/confirm-delete', [App\Http\Controllers\PurchaseController::class, 'confirmDelete'])
    ->middleware('auth')
    ->name('admin.purchases.confirm_delete')
    ->where('id', '[0-9]+');

Route::delete('purchases/{id}', [App\Http\Controllers\PurchaseController::class, 'destroy'])
    ->middleware('auth')
    ->name('admin.purchases.destroy')
    ->where('id', '[0-9]+');

// AJAX Endpoints
Route::get('purchases/ajax/materials/{categoryId}', [App\Http\Controllers\PurchaseController::class, 'getMaterialsByCategory'])
    ->middleware('auth')
    ->name('admin.purchases.ajax.materials')
    ->where('categoryId', '[0-9]+');

Route::get('purchases/ajax/variants/{materialId}', [App\Http\Controllers\PurchaseController::class, 'getVariantsByMaterial'])
    ->middleware('auth')
    ->name('admin.purchases.ajax.variants')
    ->where('materialId', '[0-9]+');

Route::get('purchases/ajax/units/{materialId}', [App\Http\Controllers\PurchaseController::class, 'getUnitsForMaterial'])
    ->middleware('auth')
    ->name('admin.purchases.ajax.units')
    ->where('materialId', '[0-9]+');
// Anular recepción
Route::post('purchases/{id}/receptions/{receptionId}/void', [App\Http\Controllers\PurchaseController::class, 'voidReception'])
    ->middleware('auth')
    ->name('admin.purchases.receptions.void')
    ->where(['id' => '[0-9]+', 'receptionId' => '[0-9]+']);

// Recalcular estado de compra
Route::post('purchases/{id}/recalculate-status', [App\Http\Controllers\PurchaseController::class, 'recalculateStatus'])
    ->middleware('auth')
    ->name('admin.purchases.recalculate')
    ->where('id', '[0-9]+');



/*
|--------------------------------------------------------------------------
| RUTAS DE PRODUCTOS (Explícitas)
|--------------------------------------------------------------------------
*/

//rutas productos
Route::get('/gestion-productos', [App\Http\Controllers\ProductController::class, 'index'])
    ->name('admin.productos.index')
    ->middleware(['auth']);

// Listado principal
Route::get('productos', [App\Http\Controllers\ProductController::class, 'index'])
    ->middleware('auth')
    ->name('admin.products.index');

// Crear producto
Route::get('productos/create', [App\Http\Controllers\ProductController::class, 'create'])
    ->middleware('auth')
    ->name('admin.products.create');

Route::post('productos', [App\Http\Controllers\ProductController::class, 'store'])
    ->middleware('auth')
    ->name('admin.products.store');

// Ver detalle
Route::get('productos/{id}', [App\Http\Controllers\ProductController::class, 'show'])
    ->middleware('auth')
    ->name('admin.products.show')
    ->where('id', '[0-9]+');

// Editar producto
Route::get('productos/{id}/edit', [App\Http\Controllers\ProductController::class, 'edit'])
    ->middleware('auth')
    ->name('admin.products.edit')
    ->where('id', '[0-9]+');

Route::put('productos/{id}', [App\Http\Controllers\ProductController::class, 'update'])
    ->middleware('auth')
    ->name('admin.products.update')
    ->where('id', '[0-9]+');

// Eliminar producto
Route::get('productos/{id}/confirm-delete', [App\Http\Controllers\ProductController::class, 'confirmDelete'])
    ->middleware('auth')
    ->name('admin.products.confirm_delete')
    ->where('id', '[0-9]+');

Route::delete('productos/{id}', [App\Http\Controllers\ProductController::class, 'destroy'])
    ->middleware('auth')
    ->name('admin.products.destroy')
    ->where('id', '[0-9]+');

// Operaciones adicionales
Route::post('productos/{id}/duplicate', [App\Http\Controllers\ProductController::class, 'duplicate'])
    ->middleware('auth')
    ->name('admin.products.duplicate')
    ->where('id', '[0-9]+');

Route::post('productos/{id}/toggle-status', [App\Http\Controllers\ProductController::class, 'toggleStatus'])
    ->middleware('auth')
    ->name('admin.products.toggle_status')
    ->where('id', '[0-9]+');

/*
|--------------------------------------------------------------------------
| VARIANTES DE PRODUCTO
|--------------------------------------------------------------------------
*/

Route::get('productos/{productId}/variants/create', [App\Http\Controllers\ProductController::class, 'createVariant'])
    ->middleware('auth')
    ->name('admin.products.variants.create')
    ->where('productId', '[0-9]+');

Route::post('productos/{productId}/variants', [App\Http\Controllers\ProductController::class, 'storeVariant'])
    ->middleware('auth')
    ->name('admin.products.variants.store')
    ->where('productId', '[0-9]+');

Route::get('productos/{productId}/variants/{variantId}/edit', [App\Http\Controllers\ProductController::class, 'editVariant'])
    ->middleware('auth')
    ->name('admin.products.variants.edit')
    ->where(['productId' => '[0-9]+', 'variantId' => '[0-9]+']);

Route::put('productos/{productId}/variants/{variantId}', [App\Http\Controllers\ProductController::class, 'updateVariant'])
    ->middleware('auth')
    ->name('admin.products.variants.update')
    ->where(['productId' => '[0-9]+', 'variantId' => '[0-9]+']);

Route::delete('productos/{productId}/variants/{variantId}', [App\Http\Controllers\ProductController::class, 'destroyVariant'])
    ->middleware('auth')
    ->name('admin.products.variants.destroy')
    ->where(['productId' => '[0-9]+', 'variantId' => '[0-9]+']);

/*
|--------------------------------------------------------------------------
| AJAX ENDPOINTS PRODUCTOS
|--------------------------------------------------------------------------
*/

Route::get('productos/ajax/designs-by-category/{categoryId}', [App\Http\Controllers\ProductController::class, 'getDesignsByCategory'])
    ->middleware('auth')
    ->name('admin.products.ajax.designs')
    ->where('categoryId', '[0-9]+');

Route::get('productos/ajax/design-exports/{designId}', [App\Http\Controllers\ProductController::class, 'getDesignExports'])
    ->middleware('auth')
    ->name('admin.products.ajax.exports')
    ->where('designId', '[0-9]+');

Route::get('productos/ajax/attributes', [App\Http\Controllers\ProductController::class, 'getAttributes'])
    ->middleware('auth')
    ->name('admin.products.ajax.attributes');

/*
|--------------------------------------------------------------------------
| CATEGORÍAS DE PRODUCTOS
|--------------------------------------------------------------------------
*/

Route::get('product-categories', [App\Http\Controllers\ProductCategoryController::class, 'index'])
    ->middleware('auth')
    ->name('admin.product_categories.index');

Route::get('product-categories/create', [App\Http\Controllers\ProductCategoryController::class, 'create'])
    ->middleware('auth')
    ->name('admin.product_categories.create');

Route::post('product-categories', [App\Http\Controllers\ProductCategoryController::class, 'store'])
    ->middleware('auth')
    ->name('admin.product_categories.store');

Route::get('product-categories/{id}/edit', [App\Http\Controllers\ProductCategoryController::class, 'edit'])
    ->middleware('auth')
    ->name('admin.product_categories.edit')
    ->where('id', '[0-9]+');

Route::put('product-categories/{id}', [App\Http\Controllers\ProductCategoryController::class, 'update'])
    ->middleware('auth')
    ->name('admin.product_categories.update')
    ->where('id', '[0-9]+');

Route::get('product-categories/{id}/confirm-delete', [App\Http\Controllers\ProductCategoryController::class, 'confirmDelete'])
    ->middleware('auth')
    ->name('admin.product_categories.confirm_delete')
    ->where('id', '[0-9]+');

Route::delete('product-categories/{id}', [App\Http\Controllers\ProductCategoryController::class, 'destroy'])
    ->middleware('auth')
    ->name('admin.product_categories.destroy')
    ->where('id', '[0-9]+');

/*
|--------------------------------------------------------------------------
| EXTRAS DE PRODUCTOS
|--------------------------------------------------------------------------
*/

Route::get('product-extras', [App\Http\Controllers\ProductExtraController::class, 'index'])
    ->middleware('auth')
    ->name('admin.product_extras.index');

Route::get('product-extras/create', [App\Http\Controllers\ProductExtraController::class, 'create'])
    ->middleware('auth')
    ->name('admin.product_extras.create');

Route::post('product-extras', [App\Http\Controllers\ProductExtraController::class, 'store'])
    ->middleware('auth')
    ->name('admin.product_extras.store');

Route::get('product-extras/{id}/edit', [App\Http\Controllers\ProductExtraController::class, 'edit'])
    ->middleware('auth')
    ->name('admin.product_extras.edit')
    ->where('id', '[0-9]+');

Route::put('product-extras/{id}', [App\Http\Controllers\ProductExtraController::class, 'update'])
    ->middleware('auth')
    ->name('admin.product_extras.update')
    ->where('id', '[0-9]+');

Route::delete('product-extras/{id}', [App\Http\Controllers\ProductExtraController::class, 'destroy'])
    ->middleware('auth')
    ->name('admin.product_extras.destroy')
    ->where('id', '[0-9]+');

/*
|--------------------------------------------------------------------------
| RUTAS DE ATRIBUTOS
|--------------------------------------------------------------------------
*/

Route::get('attributes', [App\Http\Controllers\AttributeController::class, 'index'])
    ->middleware('auth')
    ->name('admin.attributes.index');

Route::get('attributes/create', [App\Http\Controllers\AttributeController::class, 'create'])
    ->middleware('auth')
    ->name('admin.attributes.create');

Route::post('attributes', [App\Http\Controllers\AttributeController::class, 'store'])
    ->middleware('auth')
    ->name('admin.attributes.store');

Route::get('attributes/{id}/edit', [App\Http\Controllers\AttributeController::class, 'edit'])
    ->middleware('auth')
    ->name('admin.attributes.edit')
    ->where('id', '[0-9]+');

Route::put('attributes/{id}', [App\Http\Controllers\AttributeController::class, 'update'])
    ->middleware('auth')
    ->name('admin.attributes.update')
    ->where('id', '[0-9]+');

Route::get('attributes/{id}/confirm-delete', [App\Http\Controllers\AttributeController::class, 'confirm_delete'])
    ->middleware('auth')
    ->name('admin.attributes.confirm_delete')
    ->where('id', '[0-9]+');

Route::delete('attributes/{id}', [App\Http\Controllers\AttributeController::class, 'destroy'])
    ->middleware('auth')
    ->name('admin.attributes.destroy')
    ->where('id', '[0-9]+');

/*
|--------------------------------------------------------------------------
| RUTAS DE VALORES DE ATRIBUTOS
|--------------------------------------------------------------------------
*/

Route::get('attribute-values', [App\Http\Controllers\AttributeValueController::class, 'index'])
    ->middleware('auth')
    ->name('admin.attribute-values.index');

Route::get('attribute-values/create', [App\Http\Controllers\AttributeValueController::class, 'create'])
    ->middleware('auth')
    ->name('admin.attribute-values.create');

Route::post('attribute-values', [App\Http\Controllers\AttributeValueController::class, 'store'])
    ->middleware('auth')
    ->name('admin.attribute-values.store');

Route::get('attribute-values/{id}/edit', [App\Http\Controllers\AttributeValueController::class, 'edit'])
    ->middleware('auth')
    ->name('admin.attribute-values.edit')
    ->where('id', '[0-9]+');

Route::put('attribute-values/{id}', [App\Http\Controllers\AttributeValueController::class, 'update'])
    ->middleware('auth')
    ->name('admin.attribute-values.update')
    ->where('id', '[0-9]+');

Route::get('attribute-values/{id}/confirm-delete', [App\Http\Controllers\AttributeValueController::class, 'confirm_delete'])
    ->middleware('auth')
    ->name('admin.attribute-values.confirm_delete')
    ->where('id', '[0-9]+');

Route::delete('attribute-values/{id}', [App\Http\Controllers\AttributeValueController::class, 'destroy'])
    ->middleware('auth')
    ->name('admin.attribute-values.destroy')
    ->where('id', '[0-9]+');

// AJAX endpoint para obtener tipo de atributo (para color picker dinámico)
Route::get('attribute-values/get-type/{attributeId}', [App\Http\Controllers\AttributeValueController::class, 'getAttributeType'])
    ->middleware('auth')
    ->name('admin.attribute-values.get-type')
    ->where('attributeId', '[0-9]+');
