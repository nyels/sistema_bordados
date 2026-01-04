<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $table = 'system_settings';

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'options',
        'updated_by',
    ];

    protected $casts = [
        'options' => 'array',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function updatedByUser()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        // Validar formato de key
        if (!preg_match('/^[a-z][a-z0-9_]{2,50}$/', $key)) {
            return $default;
        }

        return Cache::remember("setting_{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            if (!$setting) {
                return $default;
            }

            return match ($setting->type) {
                'boolean' => $setting->value === '1',
                'integer' => (int) $setting->value,
                'json' => json_decode($setting->value, true) ?? $default,
                default => $setting->value,
            };
        });
    }

    public static function getByGroup(string $group): \Illuminate\Database\Eloquent\Collection
    {
        // Validar formato de grupo
        if (!preg_match('/^[a-z]{3,50}$/', $group)) {
            return collect();
        }

        return static::where('group', $group)->orderBy('id')->get();
    }

    public static function getGroups(): array
    {
        return static::distinct()->pluck('group')->toArray();
    }
}
