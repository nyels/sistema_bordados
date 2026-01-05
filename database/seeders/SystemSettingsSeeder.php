<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        // Opcional: Limpiar la tabla antes de sembrar para evitar datos mal formados previos
        // DB::table('system_settings')->truncate();

        $settings = [
            // --- GRUPO: GENERAL ---
            [
                'key' => 'company_name',
                'value' => 'Mi Empresa',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Nombre de la Empresa',
                'description' => 'Nombre que aparecerá en reportes y documentos',
                'options' => null,
            ],
            [
                'key' => 'company_logo',
                'value' => null,
                'type' => 'image',
                'group' => 'general',
                'label' => 'Logotipo de la Empresa',
                'description' => 'Logo visible en login, barra lateral y reportes (Formatos: PNG, JPG)',
                'options' => null,
            ],
            [
                'key' => 'currency',
                'value' => 'MXN',
                'type' => 'select',
                'group' => 'general',
                'label' => 'Moneda',
                'description' => 'Moneda predeterminada del sistema',
                'options' => [
                    'MXN' => 'Peso Mexicano (MXN)',
                    'USD' => 'Dólar (USD)',
                    'EUR' => 'Euro (EUR)'
                ],
            ],
            [
                'key' => 'timezone',
                'value' => 'America/Merida',
                'type' => 'select',
                'group' => 'general',
                'label' => 'Zona Horaria',
                'description' => 'Zona horaria para fechas y horas',
                'options' => [
                    'America/Mexico_City' => 'Ciudad de México',
                    'America/Merida' => 'Mérida',
                    'America/Cancun' => 'Cancún',
                    'America/Tijuana' => 'Tijuana',
                ],
            ],

            // --- GRUPO: INVENTARIO ---
            [
                'key' => 'inventory_costing_method',
                'value' => 'average',
                'type' => 'select',
                'group' => 'inventario',
                'label' => 'Método de Costeo',
                'description' => 'Método para calcular el costo de inventario',
                'options' => [
                    'average' => 'Promedio Ponderado (Recomendado)',
                    'fifo' => 'PEPS - Primero en Entrar, Primero en Salir',
                    'lifo' => 'UEPS - Último en Entrar, Primero en Salir',
                    'last_cost' => 'Último Costo de Compra',
                ],
            ],
            [
                'key' => 'low_stock_alert',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'inventario',
                'label' => 'Alertas de Stock Bajo',
                'description' => 'Activar notificaciones cuando el stock esté bajo',
                'options' => null,
            ],

            // --- GRUPO: FACTURACIÓN ---
            [
                'key' => 'default_tax_rate',
                'value' => '16',
                'type' => 'integer',
                'group' => 'facturacion',
                'label' => 'Tasa de IVA (%)',
                'description' => 'Porcentaje de IVA predeterminado',
                'options' => null,
            ],
        ];

        foreach ($settings as $data) {
            SystemSetting::updateOrCreate(
                ['key' => $data['key']],
                $data
            );
        }

        $this->command->info('✓ Configuraciones del sistema actualizadas correctamente.');
    }
}
