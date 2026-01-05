<?php

namespace App\Models;

use App\Enums\ReceptionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class PurchaseReception extends Model
{
    protected $table = 'purchase_receptions';

    protected $fillable = [
        'uuid',
        'purchase_id',
        'reception_number',
        'status',
        'delivery_note',
        'notes',
        'received_at',
        'received_by',
        'voided_at',
        'voided_by',
        'void_reason',
    ];

    protected $casts = [
        'status' => ReceptionStatus::class,
        'received_at' => 'datetime',
        'voided_at' => 'datetime',
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
            if (empty($model->reception_number)) {
                $model->reception_number = self::generateReceptionNumber($model->purchase_id);
            }
            if (empty($model->received_at)) {
                $model->received_at = now();
            }
            if (empty($model->received_by)) {
                $model->received_by = Auth::id();
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    public function items()
    {
        return $this->hasMany(PurchaseReceptionItem::class, 'purchase_reception_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function voidedByUser()
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', ReceptionStatus::COMPLETED->value);
    }

    public function scopeVoided(Builder $query): Builder
    {
        return $query->where('status', ReceptionStatus::VOIDED->value);
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

    public function getIsVoidedAttribute(): bool
    {
        return $this->status === ReceptionStatus::VOIDED;
    }

    public function getCanVoidAttribute(): bool
    {
        return $this->status === ReceptionStatus::COMPLETED;
    }

    public function getTotalItemsAttribute(): int
    {
        return $this->items()->count();
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS
    |--------------------------------------------------------------------------
    */

    public static function generateReceptionNumber(int $purchaseId): string
    {
        $purchase = Purchase::find($purchaseId);
        $prefix = $purchase ? $purchase->purchase_number : 'REC';

        $count = self::where('purchase_id', $purchaseId)->count() + 1;

        return sprintf('%s-R%02d', $prefix, $count);
    }
}
