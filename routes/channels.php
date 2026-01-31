<?php

use App\Models\Order;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Canales de Broadcast - Sistema de Bordados
|--------------------------------------------------------------------------
|
| Canales privados para notificaciones en tiempo real.
| Los eventos usan PrivateChannel para seguridad.
|
*/

// Canal de usuario (default Laravel)
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Canal administrativo - Solo usuarios autenticados
Broadcast::channel('orders.admin', function ($user) {
    // TODO: Agregar verificación de rol cuando exista sistema de permisos
    return $user !== null;
});

// Canal de producción - Solo usuarios autenticados
Broadcast::channel('orders.production', function ($user) {
    // TODO: Agregar verificación de rol cuando exista sistema de permisos
    return $user !== null;
});

// Canal específico de pedido - Solo usuarios con acceso al pedido
Broadcast::channel('order.{orderId}', function ($user, $orderId) {
    // Por ahora: cualquier usuario autenticado puede ver cualquier pedido
    // TODO: Restringir según roles cuando exista sistema de permisos
    $order = Order::find($orderId);
    return $order !== null && $user !== null;
});
