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

// Home Analytics (AJAX endpoint)
Route::get('/home/analytics', [App\Http\Controllers\HomeController::class, 'analytics'])
    ->name('home.analytics')
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

Route::get('admin/designs/{design}/download', [App\Http\Controllers\DesignController::class, 'downloadFile'])
    ->name('admin.designs.download')
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

// Módulo Producción (Principal)
Route::group(['prefix' => 'admin/production', 'as' => 'admin.production.', 'middleware' => ['auth']], function () {
    // Cola de produccion
    Route::get('/queue', [App\Http\Controllers\ProductionQueueController::class, 'index'])->name('queue');
    Route::get('/queue/{order}/materials', [App\Http\Controllers\ProductionQueueController::class, 'getMaterialsForOrder'])->name('queue.materials');
    Route::patch('/queue/{order}/priority', [App\Http\Controllers\ProductionQueueController::class, 'updatePriority'])->name('queue.priority');
    Route::post('/queue/{order}/start', [App\Http\Controllers\ProductionQueueController::class, 'startProduction'])->name('queue.start');

    Route::get('/', [App\Http\Controllers\ProduccionController::class, 'index'])->name('index');
    Route::get('/create', [App\Http\Controllers\ProduccionController::class, 'create'])->name('create');
    Route::post('/', [App\Http\Controllers\ProduccionController::class, 'store'])->name('store');
    Route::get('/{id}', [App\Http\Controllers\ProduccionController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [App\Http\Controllers\ProduccionController::class, 'edit'])->name('edit');
    Route::put('/{id}', [App\Http\Controllers\ProduccionController::class, 'update'])->name('update');
    Route::delete('/{id}', [App\Http\Controllers\ProduccionController::class, 'destroy'])->name('destroy');
    Route::get('/{id}/download', [App\Http\Controllers\ProduccionController::class, 'download'])->name('download');

    // Acciones de estado
    Route::post('/{id}/solicitar', [App\Http\Controllers\ProduccionController::class, 'requestApproval'])->name('request');
    Route::post('/{id}/aprobar', [App\Http\Controllers\ProduccionController::class, 'approve'])->name('approve');
    Route::post('/{id}/archivar', [App\Http\Controllers\ProduccionController::class, 'archive'])->name('archive');
    Route::post('/{id}/revertir', [App\Http\Controllers\ProduccionController::class, 'revert'])->name('revert');
    Route::post('/{id}/restaurar', [App\Http\Controllers\ProduccionController::class, 'restore'])->name('restore');

    // Vista previa
    Route::get('/{export}/preview', [App\Http\Controllers\DesignPreviewController::class, 'preview'])->name('preview');
});

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

// AJAX: Obtener contador de exportaciones del diseño (para pestaña Producción)
Route::get('admin/designs/{design}/exports-count', [App\Http\Controllers\DesignExportController::class, 'getDesignExportsCount'])
    ->name('admin.designs.exports-count')
    ->middleware('auth');

// AJAX: Obtener exportaciones de una variante específica (para contador de producción)
Route::get('admin/designs/{design}/variants/{variant}/exports/ajax', [App\Http\Controllers\DesignExportController::class, 'getVariantExports'])
    ->name('admin.variants.exports.ajax')
    ->middleware('auth');


/*
|--------------------------------------------------------------------------
| RUTAS DE VISUALIZADOR DE BORDADOS (STANDALONE)
|--------------------------------------------------------------------------
*/
Route::get('admin/visualizer', [App\Http\Controllers\VisualizerController::class, 'index'])
    ->name('admin.visualizer.index')
    ->middleware('auth');

Route::post('admin/visualizer/analyze', [App\Http\Controllers\VisualizerController::class, 'analyze'])
    ->name('admin.visualizer.analyze')
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

Route::get('admin/images/{image}/download', [App\Http\Controllers\DesignController::class, 'downloadImage'])
    ->name('admin.images.download')
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

Route::get('admin/categories/{category}/confirm-delete', [App\Http\Controllers\CategoryController::class, 'confirm_delete'])
    ->name('admin.categories.confirm_delete')
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

Route::get('/clientes/measures/{id}', [App\Http\Controllers\ClienteController::class, 'getMeasures'])
    ->name('admin.clientes.measures')
    ->middleware('auth');

// Quick store (AJAX para crear cliente rapido desde pedidos)
Route::post('/clientes/quick-store', [App\Http\Controllers\ClienteController::class, 'quickStore'])
    ->name('admin.clientes.quick-store')
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





/*
|--------------------------------------------------------------------------
| RUTAS DE SISTEMA (CONFIGURACIÓN, LOGS, UNIDADES)
|--------------------------------------------------------------------------
*/

// Settings (Configuración del Sistema)
Route::get('admin/settings', [App\Http\Controllers\SystemSettingController::class, 'index'])
    ->name('admin.settings.index')
    ->middleware('auth');

Route::get('admin/settings/create', [App\Http\Controllers\SystemSettingController::class, 'create'])
    ->name('admin.settings.create')
    ->middleware('auth');

Route::post('admin/settings', [App\Http\Controllers\SystemSettingController::class, 'store'])
    ->name('admin.settings.store')
    ->middleware('auth');

// Actualización masiva de configuraciones (formulario principal)
Route::put('admin/settings', [App\Http\Controllers\SystemSettingController::class, 'update'])
    ->name('admin.settings.bulk-update')
    ->middleware('auth');

Route::get('admin/settings/{setting}', [App\Http\Controllers\SystemSettingController::class, 'show'])
    ->name('admin.settings.show')
    ->middleware('auth');

Route::get('admin/settings/{setting}/edit', [App\Http\Controllers\SystemSettingController::class, 'edit'])
    ->name('admin.settings.edit')
    ->middleware('auth');

Route::put('admin/settings/{setting}', [App\Http\Controllers\SystemSettingController::class, 'update'])
    ->name('admin.settings.update')
    ->middleware('auth');

Route::delete('admin/settings/{setting}', [App\Http\Controllers\SystemSettingController::class, 'destroy'])
    ->name('admin.settings.destroy')
    ->middleware('auth');

// Activity Logs (Registro de Actividad)
Route::get('admin/activity-logs', [App\Http\Controllers\ActivityLogController::class, 'index'])
    ->name('admin.activity-logs.index')
    ->middleware('auth');

Route::get('admin/activity-logs/{uuid}', [App\Http\Controllers\ActivityLogController::class, 'show'])
    ->name('admin.activity-logs.show')
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

Route::get('admin/units', [App\Http\Controllers\UnitController::class, 'index'])
    ->name('admin.units.index')
    ->middleware('auth');

Route::get('admin/units/create', [App\Http\Controllers\UnitController::class, 'create'])
    ->name('admin.units.create')
    ->middleware('auth');

Route::post('admin/units', [App\Http\Controllers\UnitController::class, 'store'])
    ->name('admin.units.store')
    ->middleware('auth');

Route::get('admin/units/{id}/edit', [App\Http\Controllers\UnitController::class, 'edit'])
    ->name('admin.units.edit')
    ->middleware('auth');

Route::put('admin/units/{id}', [App\Http\Controllers\UnitController::class, 'update'])
    ->name('admin.units.update')
    ->middleware('auth');

Route::get('admin/units/{id}/confirm-delete', [App\Http\Controllers\UnitController::class, 'confirmDelete'])
    ->name('admin.units.confirm_delete')
    ->middleware('auth');

Route::delete('admin/units/{id}', [App\Http\Controllers\UnitController::class, 'destroy'])
    ->name('admin.units.destroy')
    ->middleware('auth');



/*
|--------------------------------------------------------------------------
| RUTAS DE CATEGORÍAS DE MATERIALES
|--------------------------------------------------------------------------
*/

Route::get('admin/material-categories', [App\Http\Controllers\MaterialCategoryController::class, 'index'])
    ->name('admin.material-categories.index')
    ->middleware('auth');

Route::get('admin/material-categories/create', [App\Http\Controllers\MaterialCategoryController::class, 'create'])
    ->name('admin.material-categories.create')
    ->middleware('auth');

Route::post('admin/material-categories', [App\Http\Controllers\MaterialCategoryController::class, 'store'])
    ->name('admin.material-categories.store')
    ->middleware('auth');

Route::get('admin/material-categories/{id}/edit', [App\Http\Controllers\MaterialCategoryController::class, 'edit'])
    ->name('admin.material-categories.edit')
    ->middleware('auth');

Route::put('admin/material-categories/{id}', [App\Http\Controllers\MaterialCategoryController::class, 'update'])
    ->name('admin.material-categories.update')
    ->middleware('auth');

Route::get('admin/material-categories/{id}/confirm-delete', [App\Http\Controllers\MaterialCategoryController::class, 'confirmDelete'])
    ->name('admin.material-categories.confirm_delete')
    ->middleware('auth');

Route::delete('admin/material-categories/{id}', [App\Http\Controllers\MaterialCategoryController::class, 'destroy'])
    ->name('admin.material-categories.destroy')
    ->middleware('auth');

Route::get('admin/material-categories/{id}/get-materials', [App\Http\Controllers\MaterialCategoryController::class, 'getMaterials'])
    ->name('admin.material-categories.get-materials')
    ->middleware('auth');

Route::get('admin/material-categories/{id}/get-units', [App\Http\Controllers\MaterialCategoryController::class, 'getUnits'])
    ->name('admin.material-categories.get-units')
    ->middleware('auth');

/*
|--------------------------------------------------------------------------
| RUTAS DE GESTIÓN: CATEGORÍA ↔ UNIDADES PERMITIDAS
|--------------------------------------------------------------------------
| Módulo administrativo para configurar qué unidades de compra están
| permitidas para cada categoría de material.
|--------------------------------------------------------------------------
*/

Route::get('admin/material-categories/units', [App\Http\Controllers\MaterialCategoryUnitController::class, 'index'])
    ->name('admin.material-category-units.index')
    ->middleware('auth');

Route::post('admin/material-categories/{categoryId}/units', [App\Http\Controllers\MaterialCategoryUnitController::class, 'store'])
    ->name('admin.material-category-units.store')
    ->middleware('auth');

Route::delete('admin/material-categories/{categoryId}/units/{unitId}', [App\Http\Controllers\MaterialCategoryUnitController::class, 'destroy'])
    ->name('admin.material-category-units.destroy')
    ->middleware('auth');

Route::get('admin/material-categories/{categoryId}/assigned-units', [App\Http\Controllers\MaterialCategoryUnitController::class, 'getAssignedUnits'])
    ->name('admin.material-category-units.assigned')
    ->middleware('auth');

Route::get('admin/material-categories/{categoryId}/available-units', [App\Http\Controllers\MaterialCategoryUnitController::class, 'getAvailableUnits'])
    ->name('admin.material-category-units.available')
    ->middleware('auth');

Route::get('admin/material-categories/{categoryId}/check-integrity', [App\Http\Controllers\MaterialCategoryUnitController::class, 'checkIntegrity'])
    ->name('admin.material-category-units.check-integrity')
    ->middleware('auth');

/*
|--------------------------------------------------------------------------
| RUTAS DE MATERIALES
|--------------------------------------------------------------------------
*/

Route::get('admin/materials', [App\Http\Controllers\MaterialController::class, 'index'])
    ->name('admin.materials.index')
    ->middleware('auth');

Route::get('admin/materials/create', [App\Http\Controllers\MaterialController::class, 'create'])
    ->name('admin.materials.create')
    ->middleware('auth');

// UX V2: Wizard de creación de material
Route::get('admin/materials/create-wizard', [App\Http\Controllers\MaterialController::class, 'createWizard'])
    ->name('admin.materials.create-wizard')
    ->middleware('auth');

Route::post('admin/materials/store-wizard', [App\Http\Controllers\MaterialController::class, 'storeWizard'])
    ->name('admin.materials.store-wizard')
    ->middleware('auth');

Route::post('admin/materials', [App\Http\Controllers\MaterialController::class, 'store'])
    ->name('admin.materials.store')
    ->middleware('auth');

Route::get('admin/materials/{id}/edit', [App\Http\Controllers\MaterialController::class, 'edit'])
    ->name('admin.materials.edit')
    ->middleware('auth');

Route::put('admin/materials/{id}', [App\Http\Controllers\MaterialController::class, 'update'])
    ->name('admin.materials.update')
    ->middleware('auth');

Route::get('admin/materials/{id}/confirm-delete', [App\Http\Controllers\MaterialController::class, 'confirmDelete'])
    ->name('admin.materials.confirm_delete')
    ->middleware('auth');

Route::delete('admin/materials/{id}', [App\Http\Controllers\MaterialController::class, 'destroy'])
    ->name('admin.materials.destroy')
    ->middleware('auth');

Route::get('admin/materials/category/{categoryId}', [App\Http\Controllers\MaterialController::class, 'getByCategory'])
    ->name('admin.materials.by-category')
    ->middleware('auth');

Route::post('admin/products/validate-prices', [App\Http\Controllers\ProductController::class, 'validateMaterialPrices'])
    ->name('admin.products.validate-prices')
    ->middleware('auth');

Route::post('admin/products/save-draft', [App\Http\Controllers\ProductController::class, 'storeDraft'])
    ->name('admin.products.save-draft')
    ->middleware('auth');

/*
|--------------------------------------------------------------------------
| RUTAS DE VARIANTES DE MATERIALES
|--------------------------------------------------------------------------
*/

Route::get('admin/materials/{materialId}/variants', [App\Http\Controllers\MaterialVariantController::class, 'index'])
    ->name('admin.material-variants.index')
    ->middleware('auth');

Route::get('admin/materials/{materialId}/variants/create', [App\Http\Controllers\MaterialVariantController::class, 'create'])
    ->name('admin.material-variants.create')
    ->middleware('auth');

Route::post('admin/materials/{materialId}/variants', [App\Http\Controllers\MaterialVariantController::class, 'store'])
    ->name('admin.material-variants.store')
    ->middleware('auth');

Route::get('admin/materials/{materialId}/variants/{id}/edit', [App\Http\Controllers\MaterialVariantController::class, 'edit'])
    ->name('admin.material-variants.edit')
    ->middleware('auth');

Route::put('admin/materials/{materialId}/variants/{id}', [App\Http\Controllers\MaterialVariantController::class, 'update'])
    ->name('admin.material-variants.update')
    ->middleware('auth');

Route::get('admin/materials/{materialId}/variants/{id}/confirm-delete', [App\Http\Controllers\MaterialVariantController::class, 'confirmDelete'])
    ->name('admin.material-variants.confirm_delete')
    ->middleware('auth');

Route::delete('admin/materials/{materialId}/variants/{id}', [App\Http\Controllers\MaterialVariantController::class, 'destroy'])
    ->name('admin.material-variants.destroy')
    ->middleware('auth');

//ajax
Route::get('admin/materials/{materialId}/variants-json', [App\Http\Controllers\MaterialVariantController::class, 'getByMaterial'])
    ->name('admin.material-variants.by-material')
    ->middleware('auth');

Route::get('admin/materials/{materialId}/variants-with-conversions', [App\Http\Controllers\MaterialVariantController::class, 'getByMaterial2'])
    ->name('admin.material-variants.conversiones')
    ->middleware('auth');
/*
|--------------------------------------------------------------------------
| RUTAS DE CONVERSIONES DE UNIDADES POR MATERIAL
|--------------------------------------------------------------------------
*/

Route::get('admin/materials/{materialId}/conversions', [App\Http\Controllers\MaterialUnitConversionController::class, 'index'])
    ->name('admin.material-conversions.index')
    ->middleware('auth');

Route::get('admin/materials/{materialId}/conversions/create', [App\Http\Controllers\MaterialUnitConversionController::class, 'create'])
    ->name('admin.material-conversions.create')
    ->middleware('auth');

Route::post('admin/materials/{materialId}/conversions', [App\Http\Controllers\MaterialUnitConversionController::class, 'store'])
    ->name('admin.material-conversions.store')
    ->middleware('auth');

Route::get('admin/materials/{materialId}/conversions/{id}/edit', [App\Http\Controllers\MaterialUnitConversionController::class, 'edit'])
    ->name('admin.material-conversions.edit')
    ->middleware('auth');

Route::put('admin/materials/{materialId}/conversions/{id}', [App\Http\Controllers\MaterialUnitConversionController::class, 'update'])
    ->name('admin.material-conversions.update')
    ->middleware('auth');

Route::get('admin/materials/{materialId}/conversions/{id}/confirm-delete', [App\Http\Controllers\MaterialUnitConversionController::class, 'confirmDelete'])
    ->name('admin.material-conversions.confirm_delete')
    ->middleware('auth');

Route::delete('admin/materials/{materialId}/conversions/{id}', [App\Http\Controllers\MaterialUnitConversionController::class, 'destroy'])
    ->name('admin.material-conversions.destroy')
    ->middleware('auth');

Route::get('admin/materials/{materialId}/conversion-factor/{fromUnitId}', [App\Http\Controllers\MaterialUnitConversionController::class, 'getConversionFactor'])
    ->name('admin.material-conversions.factor')
    ->middleware('auth');

/*
|--------------------------------------------------------------------------
| RUTAS DE COMPRAS (PURCHASES) - FORMATO EXPLÍCITO
|--------------------------------------------------------------------------
*/

// Listado
Route::get('admin/purchases', [App\Http\Controllers\PurchaseController::class, 'index'])
    ->middleware('auth')
    ->name('admin.purchases.index');

// Crear
Route::get('admin/purchases/create', [App\Http\Controllers\PurchaseController::class, 'create'])
    ->middleware('auth')
    ->name('admin.purchases.create');

Route::post('admin/purchases', [App\Http\Controllers\PurchaseController::class, 'store'])
    ->middleware('auth')
    ->name('admin.purchases.store');

// Ver detalle
Route::get('admin/purchases/{id}', [App\Http\Controllers\PurchaseController::class, 'show'])
    ->middleware('auth')
    ->name('admin.purchases.show')
    ->where('id', '[0-9]+');

// Editar
Route::get('admin/purchases/{id}/edit', [App\Http\Controllers\PurchaseController::class, 'edit'])
    ->middleware('auth')
    ->name('admin.purchases.edit')
    ->where('id', '[0-9]+');

Route::put('admin/purchases/{id}', [App\Http\Controllers\PurchaseController::class, 'update'])
    ->middleware('auth')
    ->name('admin.purchases.update')
    ->where('id', '[0-9]+');

// Confirmar (Borrador → Pendiente)
Route::post('admin/purchases/{id}/confirm', [App\Http\Controllers\PurchaseController::class, 'confirm'])
    ->middleware('auth')
    ->name('admin.purchases.confirm')
    ->where('id', '[0-9]+');

// Confirmar y Recibir en un solo paso (Borrador → Recibido)
Route::post('admin/purchases/{id}/confirm-and-receive', [App\Http\Controllers\PurchaseController::class, 'confirmAndReceive'])
    ->middleware('auth')
    ->name('admin.purchases.confirm_and_receive')
    ->where('id', '[0-9]+');

// Recibir mercancía
Route::get('admin/purchases/{id}/receive', [App\Http\Controllers\PurchaseController::class, 'showReceive'])
    ->middleware('auth')
    ->name('admin.purchases.receive')
    ->where('id', '[0-9]+');

Route::post('admin/purchases/{id}/receive', [App\Http\Controllers\PurchaseController::class, 'receive'])
    ->middleware('auth')
    ->name('admin.purchases.receive.store')
    ->where('id', '[0-9]+');

// Cancelar
Route::get('admin/purchases/{id}/cancel', [App\Http\Controllers\PurchaseController::class, 'showCancel'])
    ->middleware('auth')
    ->name('admin.purchases.cancel')
    ->where('id', '[0-9]+');

Route::post('admin/purchases/{id}/cancel', [App\Http\Controllers\PurchaseController::class, 'cancel'])
    ->middleware('auth')
    ->name('admin.purchases.cancel.store')
    ->where('id', '[0-9]+');

// Eliminar (solo borradores)
Route::get('admin/purchases/{id}/confirm-delete', [App\Http\Controllers\PurchaseController::class, 'confirmDelete'])
    ->middleware('auth')
    ->name('admin.purchases.confirm_delete')
    ->where('id', '[0-9]+');

Route::delete('admin/purchases/{id}', [App\Http\Controllers\PurchaseController::class, 'destroy'])
    ->middleware('auth')
    ->name('admin.purchases.destroy')
    ->where('id', '[0-9]+');

// AJAX Endpoints
Route::get('admin/purchases/ajax/materials/{categoryId}', [App\Http\Controllers\PurchaseController::class, 'getMaterialsByCategory'])
    ->middleware('auth')
    ->name('admin.purchases.ajax.materials')
    ->where('categoryId', '[0-9]+');

Route::get('admin/purchases/ajax/variants/{materialId}', [App\Http\Controllers\PurchaseController::class, 'getVariantsByMaterial'])
    ->middleware('auth')
    ->name('admin.purchases.ajax.variants')
    ->where('materialId', '[0-9]+');

Route::get('admin/purchases/ajax/units/{materialId}', [App\Http\Controllers\PurchaseController::class, 'getUnitsForMaterial'])
    ->middleware('auth')
    ->name('admin.purchases.ajax.units')
    ->where('materialId', '[0-9]+');

// Anular recepción
Route::post('admin/purchases/{id}/receptions/{receptionId}/void', [App\Http\Controllers\PurchaseController::class, 'voidReception'])
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
| RUTAS DE PRODUCTOS
|--------------------------------------------------------------------------
*/

Route::prefix('admin/products')->name('admin.products.')->middleware('auth')->group(function () {
    // Listado principal
    Route::get('/', [App\Http\Controllers\ProductController::class, 'index'])->name('index');

    // Crear producto
    Route::get('create', [App\Http\Controllers\ProductController::class, 'create'])->name('create');
    Route::post('/', [App\Http\Controllers\ProductController::class, 'store'])->name('store');

    // Ver detalle
    Route::get('{product}', [App\Http\Controllers\ProductController::class, 'show'])->name('show');

    // Editar producto
    Route::get('{product}/edit', [App\Http\Controllers\ProductController::class, 'edit'])->name('edit');
    Route::put('{product}', [App\Http\Controllers\ProductController::class, 'update'])->name('update');

    // Eliminar producto
    Route::get('{product}/confirm-delete', [App\Http\Controllers\ProductController::class, 'confirmDelete'])->name('confirm_delete');
    Route::delete('{product}', [App\Http\Controllers\ProductController::class, 'destroy'])->name('destroy');

    // Operaciones adicionales
    Route::post('{product}/duplicate', [App\Http\Controllers\ProductController::class, 'duplicate'])->name('duplicate');
    Route::post('{product}/toggle-status', [App\Http\Controllers\ProductController::class, 'toggleStatus'])->name('toggle_status');

    // Variantes de Producto (Sub-rutas)
    Route::prefix('{product}/variants')->name('variants.')->group(function () {
        Route::get('create', [App\Http\Controllers\ProductController::class, 'createVariant'])->name('create');
        Route::post('/', [App\Http\Controllers\ProductController::class, 'storeVariant'])->name('store');
        Route::get('{variant}/edit', [App\Http\Controllers\ProductController::class, 'editVariant'])->name('edit');
        Route::get('{variant}/json', [App\Http\Controllers\ProductController::class, 'getVariantJson'])->name('json');
        Route::put('{variant}', [App\Http\Controllers\ProductController::class, 'updateVariant'])->name('update');
        Route::delete('{variant}', [App\Http\Controllers\ProductController::class, 'destroyVariant'])->name('destroy');
    });

    // AJAX Endpoints
    Route::prefix('ajax')->name('ajax.')->group(function () {
        Route::get('designs-by-category/{categoryId}', [App\Http\Controllers\ProductController::class, 'getDesignsByCategory'])->name('designs');
        Route::get('design-exports/{designId}', [App\Http\Controllers\ProductController::class, 'getDesignExports'])->name('exports');
        Route::get('approved-design-exports', [App\Http\Controllers\ProductController::class, 'getApprovedDesignExports'])->name('approved_exports');
        Route::get('attributes', [App\Http\Controllers\ProductController::class, 'getAttributes'])->name('attributes');
        Route::get('search-materials', [App\Http\Controllers\ProductController::class, 'searchMaterials'])->name('search_materials');
    });
});


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

/*
|--------------------------------------------------------------------------
| RUTAS DE CATEGORÍAS DE PRODUCTOS (PRODUCT CATEGORIES)
|--------------------------------------------------------------------------
*/

Route::get('admin/product-categories', [App\Http\Controllers\ProductCategoryController::class, 'index'])
    ->middleware('auth')
    ->name('admin.product_categories.index');

Route::get('admin/product-categories/create', [App\Http\Controllers\ProductCategoryController::class, 'create'])
    ->middleware('auth')
    ->name('admin.product_categories.create');

Route::post('admin/product-categories', [App\Http\Controllers\ProductCategoryController::class, 'store'])
    ->middleware('auth')
    ->name('admin.product_categories.store');

Route::get('admin/product-categories/{id}', [App\Http\Controllers\ProductCategoryController::class, 'show'])
    ->middleware('auth')
    ->name('admin.product_categories.show')
    ->where('id', '[0-9]+');

Route::get('admin/product-categories/{id}/edit', [App\Http\Controllers\ProductCategoryController::class, 'edit'])
    ->middleware('auth')
    ->name('admin.product_categories.edit')
    ->where('id', '[0-9]+');

Route::put('admin/product-categories/{id}', [App\Http\Controllers\ProductCategoryController::class, 'update'])
    ->middleware('auth')
    ->name('admin.product_categories.update')
    ->where('id', '[0-9]+');

Route::get('admin/product-categories/{id}/confirm-delete', [App\Http\Controllers\ProductCategoryController::class, 'confirmDelete'])
    ->middleware('auth')
    ->name('admin.product_categories.confirm_delete')
    ->where('id', '[0-9]+');

Route::delete('admin/product-categories/{id}', [App\Http\Controllers\ProductCategoryController::class, 'destroy'])
    ->middleware('auth')
    ->name('admin.product_categories.destroy')
    ->where('id', '[0-9]+');

/*
|--------------------------------------------------------------------------
| RUTAS DE EXTRAS DE PRODUCTOS (PRODUCT EXTRAS)
|--------------------------------------------------------------------------
*/

Route::get('/product_extras', [App\Http\Controllers\ProductExtraController::class, 'index'])
    ->name('admin.product_extras.index')
    ->middleware('auth');

Route::get('/product_extras/nuevo', [App\Http\Controllers\ProductExtraController::class, 'create'])
    ->name('admin.product_extras.create')
    ->middleware('auth');

Route::post('/product_extras/create', [App\Http\Controllers\ProductExtraController::class, 'store'])
    ->name('admin.product_extras.store')
    ->middleware('auth');

Route::get('/product_extras/edit/{id}', [App\Http\Controllers\ProductExtraController::class, 'edit'])
    ->name('admin.product_extras.edit')
    ->middleware('auth');

Route::put('/product_extras/edit/{id}', [App\Http\Controllers\ProductExtraController::class, 'update'])
    ->name('admin.product_extras.update')
    ->middleware('auth');

Route::get('/product_extras/confirm_delete/{id}', [App\Http\Controllers\ProductExtraController::class, 'confirm_delete'])
    ->name('admin.product_extras.confirm_delete')
    ->middleware('auth');

Route::delete('/product_extras/delete/{id}', [App\Http\Controllers\ProductExtraController::class, 'destroy'])
    ->name('admin.product_extras.destroy')
    ->middleware('auth');

/*
|--------------------------------------------------------------------------
| RUTAS DE PERSONAL (STAFF)
|--------------------------------------------------------------------------
*/

Route::prefix('admin/staff')->name('admin.staff.')->middleware('auth')->group(function () {
    Route::get('/', [App\Http\Controllers\StaffController::class, 'index'])->name('index');
    Route::get('create', [App\Http\Controllers\StaffController::class, 'create'])->name('create');
    Route::post('/', [App\Http\Controllers\StaffController::class, 'store'])->name('store');
    Route::get('{staff}/edit', [App\Http\Controllers\StaffController::class, 'edit'])->name('edit');
    Route::put('{staff}', [App\Http\Controllers\StaffController::class, 'update'])->name('update');
    Route::delete('{staff}', [App\Http\Controllers\StaffController::class, 'destroy'])->name('destroy');
});

/*
|--------------------------------------------------------------------------
| RUTAS DE USUARIOS
|--------------------------------------------------------------------------
*/

Route::prefix('admin/users')->name('admin.users.')->middleware('auth')->group(function () {
    Route::get('/', [App\Http\Controllers\UserController::class, 'index'])->name('index');
    Route::get('create', [App\Http\Controllers\UserController::class, 'create'])->name('create');
    Route::post('/', [App\Http\Controllers\UserController::class, 'store'])->name('store');
    Route::get('{user}/edit', [App\Http\Controllers\UserController::class, 'edit'])->name('edit');
    Route::put('{user}', [App\Http\Controllers\UserController::class, 'update'])->name('update');
    Route::delete('{user}', [App\Http\Controllers\UserController::class, 'destroy'])->name('destroy');
});

/*
|--------------------------------------------------------------------------
| RUTAS DE PEDIDOS (ORDERS)
|--------------------------------------------------------------------------
*/

Route::prefix('admin/orders')->name('admin.orders.')->middleware('auth')->group(function () {
    Route::get('/', [App\Http\Controllers\OrderController::class, 'index'])->name('index');
    Route::get('create', [App\Http\Controllers\OrderController::class, 'create'])->name('create');
    Route::post('/', [App\Http\Controllers\OrderController::class, 'store'])->name('store');
    Route::get('{order}', [App\Http\Controllers\OrderController::class, 'show'])->name('show');
    // FASE 3: Edición habilitada SOLO para pedidos en estado 'draft'
    Route::get('{order}/edit', [App\Http\Controllers\OrderController::class, 'edit'])->name('edit');
    Route::put('{order}', [App\Http\Controllers\OrderController::class, 'update'])->name('update');
    Route::patch('{order}/status', [App\Http\Controllers\OrderController::class, 'updateStatus'])->name('update-status');
    Route::patch('{order}/cancel', [App\Http\Controllers\OrderController::class, 'cancel'])->name('cancel');
    Route::post('{order}/payments', [App\Http\Controllers\OrderController::class, 'storePayment'])->name('payments.store');
    Route::put('payments/{payment}', [App\Http\Controllers\OrderController::class, 'updatePayment'])->name('payments.update');
    Route::delete('payments/{payment}', [App\Http\Controllers\OrderController::class, 'destroyPayment'])->name('payments.destroy');

    // AJAX
    Route::get('ajax/search-clientes', [App\Http\Controllers\OrderController::class, 'searchClientes'])->name('ajax.search-clientes');
    Route::get('ajax/search-products', [App\Http\Controllers\OrderController::class, 'searchProducts'])->name('ajax.search-products');
    Route::get('ajax/product/{product}/extras', [App\Http\Controllers\OrderController::class, 'getProductExtras'])->name('ajax.product-extras');
    Route::get('ajax/cliente/{cliente}/measurements', [App\Http\Controllers\OrderController::class, 'getClientMeasurements'])->name('ajax.client-measurements');
    Route::post('ajax/cliente/{cliente}/measurements', [App\Http\Controllers\OrderController::class, 'storeClientMeasurements'])->name('ajax.store-client-measurements');
    Route::get('ajax/{order}/check-annex-type', [App\Http\Controllers\OrderController::class, 'checkAnnexType'])->name('ajax.check-annex-type');
    Route::post('ajax/store-quick', [App\Http\Controllers\OrderController::class, 'storeQuick'])->name('ajax.store-quick');

    // Pedidos Anexos
    Route::get('{order}/annex/create', [App\Http\Controllers\OrderController::class, 'createAnnex'])->name('create-annex');
    Route::post('{order}/annex', [App\Http\Controllers\OrderController::class, 'storeAnnex'])->name('store-annex');
    Route::post('{order}/annex-items', [App\Http\Controllers\OrderController::class, 'storeAnnexItems'])->name('store-annex-items');

    // Mensajes operativos (comunicación en tiempo real)
    Route::get('{order}/messages', [App\Http\Controllers\OrderMessageController::class, 'index'])->name('messages.index');
    Route::post('{order}/messages', [App\Http\Controllers\OrderMessageController::class, 'store'])->name('messages.store');
    Route::delete('{order}/messages/{message}', [App\Http\Controllers\OrderMessageController::class, 'destroy'])->name('messages.destroy');
});

/*
|--------------------------------------------------------------------------
| RUTAS DE MEDIDAS DE CLIENTE
|--------------------------------------------------------------------------
*/

Route::prefix('admin/client-measurements')->name('admin.client-measurements.')->middleware('auth')->group(function () {
    Route::get('cliente/{cliente}', [App\Http\Controllers\ClientMeasurementController::class, 'index'])->name('index');
    Route::post('/', [App\Http\Controllers\ClientMeasurementController::class, 'store'])->name('store');
    Route::get('{measurement}', [App\Http\Controllers\ClientMeasurementController::class, 'show'])->name('show');
    Route::put('{measurement}', [App\Http\Controllers\ClientMeasurementController::class, 'update'])->name('update');
    Route::patch('{measurement}/primary', [App\Http\Controllers\ClientMeasurementController::class, 'setPrimary'])->name('set-primary');
    Route::delete('{measurement}', [App\Http\Controllers\ClientMeasurementController::class, 'destroy'])->name('destroy');
});

/*
|--------------------------------------------------------------------------
| RUTAS DE INVENTARIO OPERATIVO
|--------------------------------------------------------------------------
*/

Route::prefix('admin/inventory')->name('admin.inventory.')->middleware('auth')->group(function () {
    // Vista general de inventario
    Route::get('/', [App\Http\Controllers\InventoryController::class, 'index'])->name('index');

    // Kardex por variante de material
    Route::get('kardex/{variant}', [App\Http\Controllers\InventoryController::class, 'kardex'])->name('kardex');

    // Reservas activas
    Route::get('reservations', [App\Http\Controllers\InventoryController::class, 'reservations'])->name('reservations');

    // Historial de reservas (todas)
    Route::get('reservations/history', [App\Http\Controllers\InventoryController::class, 'reservationsHistory'])->name('reservations.history');

    // Ajustes manuales
    Route::get('adjustment/{variant}', [App\Http\Controllers\InventoryController::class, 'adjustmentForm'])->name('adjustment');
    Route::post('adjustment/{variant}', [App\Http\Controllers\InventoryController::class, 'storeAdjustment'])->name('adjustment.store');
});

/*
|--------------------------------------------------------------------------
| RUTAS DE NOTIFICACIONES (Tiempo Real)
|--------------------------------------------------------------------------
*/
Route::prefix('admin/notifications')->name('admin.notifications.')->middleware('auth')->group(function () {
    Route::get('recent', [App\Http\Controllers\NotificationController::class, 'getRecent'])->name('recent');
    Route::get('count', [App\Http\Controllers\NotificationController::class, 'getCount'])->name('count');
    Route::post('mark-read', [App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('mark-read');
});

/*
|--------------------------------------------------------------------------
| RUTAS DE DISEÑO DE ITEMS (Personalización)
|--------------------------------------------------------------------------
*/
Route::prefix('admin/orders/{order}/items/{item}/design')->name('admin.orders.items.design.')->middleware('auth')->group(function () {
    Route::post('upload', [App\Http\Controllers\OrderItemDesignController::class, 'upload'])->name('upload');
    Route::post('send-review', [App\Http\Controllers\OrderItemDesignController::class, 'sendToReview'])->name('send-review');
    Route::post('approve', [App\Http\Controllers\OrderItemDesignController::class, 'approve'])->name('approve');
    Route::post('reject', [App\Http\Controllers\OrderItemDesignController::class, 'reject'])->name('reject');
    Route::get('download', [App\Http\Controllers\OrderItemDesignController::class, 'download'])->name('download');
});
Route::get('admin/orders/{order}/designs/status', [App\Http\Controllers\OrderItemDesignController::class, 'status'])
    ->name('admin.orders.designs.status')
    ->middleware('auth');
