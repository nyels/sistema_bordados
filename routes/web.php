<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return view('auth.login');
});

//otra forma de redirigir
/*Route::get('/', function () {
    return redirect()->route('login');
});
*/
Auth::routes([
    'register' => false,
]);

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])
    ->name('home')
    ->middleware('auth');


//RUTAS GIROS
//ruta al index de giros
Route::get('/giros', [App\Http\Controllers\GiroController::class, 'index'])
    ->name('admin.giros.index')
    ->middleware('auth');
//ruta al formulario para crear nuevo giro
Route::get('/giros/nuevo', [App\Http\Controllers\GiroController::class, 'create'])
    ->name('admin.giros.create')
    ->middleware('auth');
//ruta al store para guardar el dato del nuevo giro
Route::post('/giros/create', [App\Http\Controllers\GiroController::class, 'store'])
    ->name('admin.giros.store')
    ->middleware('auth');
//ruta al edit para editar el giro
Route::get('/giros/edit/{id}', [App\Http\Controllers\GiroController::class, 'edit'])
    ->name('admin.giros.edit')
    ->middleware('auth');
//ruta al update para actualizar el giro
Route::put('/giros/edit/{id}', [App\Http\Controllers\GiroController::class, 'update'])
    ->name('admin.giros.update')
    ->middleware('auth');
//ruta al confirm_delete para confirmar la eliminacion del giro
Route::get('/giros/confirm_delete/{id}', [App\Http\Controllers\GiroController::class, 'confirm_delete'])
    ->name('admin.giros.confirm_delete')
    ->middleware('auth');
//ruta al delete para eliminar el giro  
Route::delete('/giros/delete/{id}', [App\Http\Controllers\GiroController::class, 'destroy'])
    ->name('admin.giros.destroy')
    ->middleware('auth');


//RUTAS ESTADOS
//ruta al index de estados
Route::get('/estados', [App\Http\Controllers\EstadoController::class, 'index'])
    ->name('admin.estados.index')
    ->middleware('auth');
//redirige a la ruta de nuevo con el formulario
Route::get('/estados/nuevo', [App\Http\Controllers\EstadoController::class, 'create'])
    ->name('admin.estados.create')
    ->middleware('auth');
//guarda los datos del formulario
Route::post('/estados/create', [App\Http\Controllers\EstadoController::class, 'store'])
    ->name('admin.estados.store')
    ->middleware('auth');
//edita los datos del formulario
Route::get('/estados/edit/{id}', [App\Http\Controllers\EstadoController::class, 'edit'])
    ->name('admin.estados.edit')
    ->middleware('auth');
//guarda los datos del formulario
Route::put('/estados/edit/{id}', [App\Http\Controllers\EstadoController::class, 'update'])
    ->name('admin.estados.update')
    ->middleware('auth');
//va al formulario para confirmar la eliminacion
Route::get('/estados/confirm_delete/{id}', [App\Http\Controllers\EstadoController::class, 'confirm_delete'])
    ->name('admin.estados.confirm_delete')
    ->middleware('auth');
//elimina los datos del formulario
Route::delete('/estados/delete/{id}', [App\Http\Controllers\EstadoController::class, 'destroy'])
    ->name('admin.estados.destroy')
    ->middleware('auth');


//RUTAS PROVEEDORES
Route::get('/proveedores', [App\Http\Controllers\ProveedorController::class, 'index'])
    ->name('admin.proveedores.index')
    ->middleware('auth');
//redirige a la ruta de nuevo con el formulario
Route::get('/proveedores/nuevo', [App\Http\Controllers\ProveedorController::class, 'create'])
    ->name('admin.proveedores.create')
    ->middleware('auth');
//guarda los datos del formulario
Route::post('/proveedores/create', [App\Http\Controllers\ProveedorController::class, 'store'])
    ->name('admin.proveedores.store')
    ->middleware('auth');
//edita los datos del formulario
Route::get('/proveedores/edit/{id}', [App\Http\Controllers\ProveedorController::class, 'edit'])
    ->name('admin.proveedores.edit')
    ->middleware('auth');
//guarda los datos del formulario
Route::put('/proveedores/edit/{id}', [App\Http\Controllers\ProveedorController::class, 'update'])
    ->name('admin.proveedores.update')
    ->middleware('auth');
//va al formulario para confirmar la eliminacion
Route::get('/proveedores/confirm_delete/{id}', [App\Http\Controllers\ProveedorController::class, 'confirm_delete'])
    ->name('admin.proveedores.confirm_delete')
    ->middleware('auth');
//elimina los datos del formulario
Route::delete('/proveedores/delete/{id}', [App\Http\Controllers\ProveedorController::class, 'destroy'])
    ->name('admin.proveedores.destroy')
    ->middleware('auth');

//FALTA RUTA SHOW DE PROVEEDORES PARA USAR MODAL

//RUTAS RECOMENDACIONES
//redirige a la ruta de nuevo con el formulario
Route::get('/recomendaciones', [App\Http\Controllers\RecomendacionController::class, 'index'])
    ->name('admin.recomendaciones.index')
    ->middleware('auth');
//manda al formulario para agregar nueva recomendacion
Route::get('/recomendaciones/nuevo', [App\Http\Controllers\RecomendacionController::class, 'create'])
    ->name('admin.recomendaciones.create')
    ->middleware('auth');
//guarda los datos del formulario
Route::post('/recomendaciones/create', [App\Http\Controllers\RecomendacionController::class, 'store'])
    ->name('admin.recomendaciones.store')
    ->middleware('auth');
//edita los datos del formulario
Route::get('/recomendaciones/edit/{id}', [App\Http\Controllers\RecomendacionController::class, 'edit'])
    ->name('admin.recomendaciones.edit')
    ->middleware('auth');
//guarda los datos del formulario
Route::put('/recomendaciones/edit/{id}', [App\Http\Controllers\RecomendacionController::class, 'update'])
    ->name('admin.recomendaciones.update')
    ->middleware('auth');
//va al formulario para confirmar la eliminacion
Route::get('/recomendaciones/confirm_delete/{id}', [App\Http\Controllers\RecomendacionController::class, 'confirm_delete'])
    ->name('admin.recomendaciones.confirm_delete')
    ->middleware('auth');
//elimina los datos del formulario
Route::delete('/recomendaciones/delete/{id}', [App\Http\Controllers\RecomendacionController::class, 'destroy'])
    ->name('admin.recomendaciones.destroy')
    ->middleware('auth');
