<?php

namespace App\Models;

use App\Enums\PurchaseStatus;
use App\Traits\HasActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth; // <--- IMPORTACIÓN DE LA FACADE


class Purchase extends Model
{
    use SoftDeletes, HasActivityLog;

    protected $table = 'purchases';

    protected $activityLogNameField = 'purchase_number';

    protected $fillable = [
        'uuid',
        'purchase_number',
        'proveedor_id',
        'status',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'discount_amount',
        'total',
        'notes',
        'reference',
        'ordered_at',
        'expected_at',
        'received_at',
        'created_by',
        'updated_by',
        'received_by',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
        'activo',
    ];

    protected $casts = [
        'status' => PurchaseStatus::class,
        'subtotal' => 'decimal:4',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'total' => 'decimal:4',
        'ordered_at' => 'date',
        'expected_at' => 'date',
        'received_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'activo' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | BOOT
    |--------------------------------------------------------------------------
    */

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->purchase_number)) {
                $model->purchase_number = self::generatePurchaseNumber();
            }
            if (empty($model->status)) {
                $model->status = PurchaseStatus::DRAFT;
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class, 'purchase_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function scopeByStatus(Builder $query, PurchaseStatus $status): Builder
    {
        return $query->where('status', $status->value);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', [
            PurchaseStatus::PENDING->value,
            PurchaseStatus::PARTIAL->value,
        ]);
    }

    public function scopeByProveedor(Builder $query, int $proveedorId): Builder
    {
        return $query->where('proveedor_id', $proveedorId);
    }

    public function scopeDateRange(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $query->whereDate('ordered_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('ordered_at', '<=', $to);
        }
        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getStatusLabelAttribute(): string
    {
        return $this->status->label();
    }

    public function getStatusColorAttribute(): string
    {
        return $this->status->color();
    }

    public function getStatusIconAttribute(): string
    {
        return $this->status->icon();
    }

    public function getFormattedTotalAttribute(): string
    {
        return '$' . number_format($this->total, 2);
    }

    public function getFormattedSubtotalAttribute(): string
    {
        return '$' . number_format($this->subtotal, 2);
    }

    public function getItemsCountAttribute(): int
    {
        return $this->items()->count();
    }

    public function getCanEditAttribute(): bool
    {
        return $this->status->canEdit();
    }

    public function getCanReceiveAttribute(): bool
    {
        return $this->status->canReceive();
    }

    public function getCanCancelAttribute(): bool
    {
        return $this->status->canCancel();
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS DE NEGOCIO
    |--------------------------------------------------------------------------
    */

    public function recalculateTotals(): void
    {
        $subtotal = $this->items()->sum('subtotal');
        $taxAmount = $subtotal * ($this->tax_rate / 100);
        $total = $subtotal + $taxAmount - $this->discount_amount;

        $this->subtotal = $subtotal;
        $this->tax_amount = $taxAmount;
        $this->total = max(0, $total);
        $this->save();
    }

    public function markAsPending(): void
    {
        $this->status = PurchaseStatus::PENDING;
        $this->ordered_at = $this->ordered_at ?? now();
        $this->save();
    }

    public function markAsReceived(): void
    {
        $this->status = PurchaseStatus::RECEIVED;
        $this->received_at = now();
        $this->received_by = Auth::id();
        $this->save();
    }

    public function markAsPartial(): void
    {
        $this->status = PurchaseStatus::PARTIAL;
        $this->save();
    }

    public function markAsCancelled(string $reason): void
    {
        $this->status = PurchaseStatus::CANCELLED;
        $this->cancelled_at = now();
        $this->cancelled_by = Auth::id();
        $this->cancellation_reason = $reason;
        $this->save();
    }

    public function isFullyReceived(): bool
    {
        foreach ($this->items as $item) {
            if ($item->quantity_received < $item->quantity) {
                return false;
            }
        }
        return true;
    }

    public function hasReceivedItems(): bool
    {
        return $this->items()->where('quantity_received', '>', 0)->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS ESTÁTICOS
    |--------------------------------------------------------------------------
    */

    public static function generatePurchaseNumber(): string
    {
        $prefix = 'OC';
        $year = now()->format('y');
        $month = now()->format('m');

        $lastPurchase = self::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastPurchase && preg_match('/(\d+)$/', $lastPurchase->purchase_number, $matches)) {
            $sequence = (int) $matches[1] + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('%s%s%s-%04d', $prefix, $year, $month, $sequence);
    }
}
