<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Giro;

class GiroSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $giros = [
            'TELA',
            'ACCESORIOS',
            'TELA Y ACCESORIOS',
        ];

        foreach ($giros as $giro) {
            Giro::create([
                'nombre_giro' => $giro,
                'descripcion' => '',
                'activo' => true,
                'fecha_baja' => null,
            ]);
        }
    }
}
