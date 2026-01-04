<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class ActivityLog extends Model
{
    /**
     * Inhabilitar la columna updated_at ya que los logs son inmutables.
     */
    public const UPDATED_AT = null;

    protected $table = 'activity_logs';

    protected $fillable = [
        'uuid',
        'user_id',
        'user_name',
        'action',
        'model_type',
        'model_id',
        'model_name',
        'old_values',
        'new_values',
        'changed_fields',
        'ip_address',
        'user_agent',
        'url',
        'method',
        'description',
        'metadata',
    ];

    protected $casts = [
        'old_values'     => 'array',
        'new_values'     => 'array',
        'changed_fields' => 'array',
        'metadata'       => 'array',
        'created_at'     => 'datetime',
    ];

    /**
     * Boot del modelo para lógica automática.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (ActivityLog $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Relación con el usuario responsable.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Método estático de registro centralizado de actividad.
     */
    public static function register(
        string $action,
        string $modelType,
        ?int $modelId = null,
        ?string $modelName = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null,
        ?array $metadata = null
    ): self {
        $user = Auth::user();
        $request = request();

        $changedFields = null;
        if ($oldValues && $newValues) {
            $changedFields = array_keys(array_diff_assoc(
                array_map('strval', $newValues),
                array_map('strval', $oldValues)
            ));
        }

        return self::create([
            'user_id'        => $user?->id,
            'user_name'      => $user ? Str::limit($user->name, 100, '') : 'Sistema',
            'action'         => Str::limit($action, 30, ''),
            'model_type'     => Str::limit($modelType, 100, ''),
            'model_id'       => $modelId,
            'model_name'     => $modelName ? Str::limit($modelName, 150, '') : null,
            'old_values'     => $oldValues,
            'new_values'     => $newValues,
            'changed_fields' => $changedFields,
            'ip_address'     => $request?->ip(),
            'user_agent'     => $request ? Str::limit($request->userAgent() ?? '', 500, '') : null,
            'url'            => $request ? Str::limit($request->fullUrl(), 500, '') : null,
            'method'         => $request?->method(),
            'description'    => $description,
            'metadata'       => $metadata,
        ]);
    }

    /**
     * Accesors modernos utilizando la clase Attribute de Laravel.
     */

    protected function shortModelType(): Attribute
    {
        return Attribute::make(
            get: fn() => class_basename($this->model_type)
        );
    }

    protected function actionIcon(): Attribute
    {
        return Attribute::make(
            get: fn() => match ($this->action) {
                'created'  => 'fas fa-plus-circle text-success',
                'updated'  => 'fas fa-edit text-warning',
                'deleted'  => 'fas fa-trash text-danger',
                'restored' => 'fas fa-undo text-info',
                'login'    => 'fas fa-sign-in-alt text-primary',
                'logout'   => 'fas fa-sign-out-alt text-secondary',
                default    => 'fas fa-circle text-muted',
            }
        );
    }

    protected function actionLabel(): Attribute
    {
        return Attribute::make(
            get: fn() => match ($this->action) {
                'created'  => 'Creó',
                'updated'  => 'Actualizó',
                'deleted'  => 'Eliminó',
                'restored' => 'Restauró',
                'login'    => 'Inició sesión',
                'logout'   => 'Cerró sesión',
                default    => Str::ucfirst($this->action),
            }
        );
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
