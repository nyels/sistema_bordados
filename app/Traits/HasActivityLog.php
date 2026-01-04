<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Log;

trait HasActivityLog
{
    protected static function bootHasActivityLog(): void
    {
        // Evitar que ActivityLog se registre a sí mismo para prevenir bucles infinitos
        if (static::class === ActivityLog::class) {
            return;
        }

        static::created(function ($model) {
            self::logActivity('created', $model);
        });

        static::updated(function ($model) {
            // Solo registrar si hubo cambios reales en la base de datos
            if ($model->wasChanged()) {
                self::logActivity('updated', $model);
            }
        });

        static::deleted(function ($model) {
            self::logActivity('deleted', $model);
        });

        // Soporte para SoftDeletes si el modelo los usa
        if (method_exists(static::class, 'restored')) {
            static::restored(function ($model) {
                self::logActivity('restored', $model);
            });
        }
    }

    protected static function logActivity(string $action, $model): void
    {
        try {
            $oldValues = null;
            $newValues = null;
            $description = '';

            $modelName = self::getModelDisplayName($model);
            $modelType = get_class($model);

            switch ($action) {
                case 'created':
                    $newValues = self::filterLogAttributes($model->getAttributes(), $model);
                    $description = "Creó {$modelName}";
                    break;

                case 'updated':
                    // Obtenemos solo los campos que cambiaron realmente
                    $changes = $model->getChanges();
                    $newValues = self::filterLogAttributes($changes, $model);

                    // Reconstruimos los valores originales solo de los campos afectados
                    $oldValues = [];
                    foreach (array_keys($changes) as $key) {
                        $oldValues[$key] = $model->getRawOriginal($key);
                    }
                    $oldValues = self::filterLogAttributes($oldValues, $model);

                    $changedCount = count($newValues);
                    $description = "Actualizó {$modelName} ({$changedCount} campos)";
                    break;

                case 'deleted':
                    $oldValues = self::filterLogAttributes($model->getAttributes(), $model);
                    $description = "Eliminó {$modelName}";
                    break;

                case 'restored':
                    $description = "Restauró {$modelName}";
                    break;
            }

            ActivityLog::register(
                action: $action,
                modelType: $modelType,
                modelId: $model->id,
                modelName: $modelName,
                oldValues: $oldValues,
                newValues: $newValues,
                description: $description
            );
        } catch (\Exception $e) {
            Log::error('Error al registrar actividad en ' . get_class($model) . ': ' . $e->getMessage());
        }
    }

    protected static function getModelDisplayName($model): string
    {
        // Prioridad de campos para identificar el modelo visualmente
        $displayField = $model->activityLogNameField ?? (isset($model->name) ? 'name' : (isset($model->label) ? 'label' : 'id'));

        if (isset($model->{$displayField})) {
            return mb_substr((string)$model->{$displayField}, 0, 100);
        }

        return class_basename($model) . ' #' . $model->id;
    }

    protected static function filterLogAttributes(array $attributes, $model): array
    {
        // Campos que nunca deben guardarse en logs por seguridad
        $systemHidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'token'];

        // Obtener campos ocultos definidos en el modelo (property $hidden)
        $modelHidden = method_exists($model, 'getHidden') ? $model->getHidden() : [];

        $allHidden = array_merge($systemHidden, $modelHidden);

        // CORRECCIÓN: Se agrega 'use ($attributes, $allHidden)' para que las variables sean accesibles dentro del callback
        return array_filter($attributes, function ($key) use ($attributes, $allHidden) {
            return !in_array($key, $allHidden, true) && !is_resource($attributes[$key]);
        }, ARRAY_FILTER_USE_KEY);
    }
}
