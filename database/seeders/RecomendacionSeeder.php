<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Recomendacion;

class RecomendacionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $recomendaciones = [
            'INSTAGRAM',
            'FACEBOOK',
            'TIKTOK',
            'TWITTER',
            'YOUTUBE',
            'RECOMENDACION PERSONAL',
        ];

        foreach ($recomendaciones as $recomendacion) {
            Recomendacion::create([
                'nombre_recomendacion' => $recomendacion,
                'descripcion_recomendacion' => '',
                'activo' => true,
                'fecha_baja' => null,
            ]);
        }
    }
}
