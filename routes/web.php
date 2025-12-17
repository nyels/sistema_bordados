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

//Estados rutas
Route::get('/estados', [App\Http\Controllers\EstadoController::class, 'index'])
    ->name('admin.estados.index')
    ->middleware('auth');

//Proveedores rutas
Route::get('/proveedores', [App\Http\Controllers\ProveedorController::class, 'index'])
    ->name('admin.proveedores.index')
    ->middleware('auth');

Route::get('/proveedores/nuevo', [App\Http\Controllers\ProveedorController::class, 'create'])
    ->name('admin.proveedores.create')
    ->middleware('auth');

Route::post('/proveedores/create', [App\Http\Controllers\ProveedorController::class, 'store'])
    ->name('admin.proveedores.store')
    ->middleware('auth');
