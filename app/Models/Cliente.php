<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;
    protected $table = 'clientes';
    protected $fillable = [
        'nombre',
        'apellidos',
        'telefono',
        'email',
        'rfc',
        'razon_social',
        'direccion',
        'ciudad',
        'codigo_postal',
        'activo',
        'fecha_baja',
        'motivo_baja',
        'observaciones',
        'estado_id',
        'recomendacion_id',
        'busto',
        'alto_cintura',
        'cintura',
        'cadera',
        'largo',
        'largo_vestido',
    ];
    public function estado()
    {
        return $this->belongsTo(Estado::class);
    }
    public function recomendacion()
    {
        return $this->belongsTo(Recomendacion::class);
    }

    /**
     * Relación con los pedidos del cliente
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'cliente_id');
    }

    /**
     * Relación con el historial de medidas del cliente
     */
    public function measurementHistory()
    {
        return $this->hasMany(ClientMeasurementHistory::class, 'cliente_id');
    }

    /**
     * Obtener las últimas medidas del cliente (del historial, order_items, o campos directos)
     */
    public function getLatestMeasurementsAttribute(): array
    {
        // 1. Primero intentar obtener del historial de medidas
        $latestHistory = $this->measurementHistory()
            ->orderBy('captured_at', 'desc')
            ->first();

        if ($latestHistory && !empty($latestHistory->measurements)) {
            $measurements = $latestHistory->measurements;
            if (!empty(array_filter($measurements, fn($v) => !empty($v) && $v !== '0'))) {
                return $measurements;
            }
        }

        // 2. Buscar en los order_items de los pedidos del cliente
        $latestOrderItem = OrderItem::whereHas('order', function ($q) {
                $q->where('cliente_id', $this->id);
            })
            ->whereNotNull('measurements')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($latestOrderItem && !empty($latestOrderItem->measurements)) {
            $measurements = $latestOrderItem->measurements;
            // Filtrar save_to_client y valores vacíos
            $filtered = collect($measurements)
                ->except(['save_to_client'])
                ->filter(fn($v) => !empty($v) && $v !== '0')
                ->toArray();

            if (!empty($filtered)) {
                return $filtered;
            }
        }

        // 3. Fallback: campos directos del cliente (legacy)
        return [
            'busto' => $this->busto,
            'cintura' => $this->cintura,
            'cadera' => $this->cadera,
            'alto_cintura' => $this->alto_cintura,
            'largo' => $this->largo,
            'largo_vestido' => $this->largo_vestido,
        ];
    }

    /**
     * Verificar si el cliente tiene medidas registradas
     */
    public function getHasMeasurementsAttribute(): bool
    {
        $measurements = $this->latest_measurements;
        return !empty(array_filter($measurements, fn($v) => !empty($v) && $v !== '0'));
    }
}
