<?php

namespace App\Listeners;

use App\Models\ActivityLog;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Request;

class LogAuthenticationActivity
{
    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        $action = null;
        $description = null;

        if ($event instanceof Login) {
            $action = 'login';
            $description = 'Inicio de sesión exitoso';
        } elseif ($event instanceof Logout) {
            $action = 'logout';
            $description = 'Cierre de sesión';
        }

        if ($action) {
            ActivityLog::register(
                action: $action,
                modelType: 'Auth',
                modelId: $event->user->id ?? null,
                modelName: $event->user->name ?? 'Usuario',
                description: $description,
                metadata: [
                    'ip' => Request::ip(),
                    'user_agent' => Request::userAgent()
                ]
            );
        }
    }
}
