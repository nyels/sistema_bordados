<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Application_types;

class ApplicationTypeSeeder extends Seeder
{
    public function run(): void
    {
        $applicationTypes = [
            [
                'slug' => 'pecho_izquierdo',
                'nombre' => 'PECHO IZQUIERDO',
                'descripcion' => 'Posición clásica sobre el corazón, ideal para logotipos empresariales.'
            ],
            [
                'slug' => 'pecho_derecho',
                'nombre' => 'PECHO DERECHO',
                'descripcion' => 'Lado opuesto al corazón, usado frecuentemente para nombres de empleados.'
            ],
            [
                'slug' => 'espalda_superior',
                'nombre' => 'ESPALDA SUPERIOR',
                'descripcion' => 'Debajo del cuello, usualmente para logos grandes o nombres de marca.'
            ],
            [
                'slug' => 'espalda_central',
                'nombre' => 'ESPALDA CENTRAL',
                'descripcion' => 'Área de mayor tamaño, ideal para diseños complejos o publicitarios.'
            ],
            [
                'slug' => 'manga_izquierda',
                'nombre' => 'MANGA IZQUIERDA',
                'descripcion' => 'Aplicación lateral en el brazo izquierdo.'
            ],
            [
                'slug' => 'manga_derecha',
                'nombre' => 'MANGA DERECHA',
                'descripcion' => 'Aplicación lateral en el brazo derecho.'
            ],
            [
                'slug' => 'cuello_posterior',
                'nombre' => 'CUELLO POSTERIOR',
                'descripcion' => 'Bordado pequeño justo debajo de la nuca.'
            ],
            [
                'slug' => 'frente_gorra',
                'nombre' => 'FRENTE GORRA',
                'descripcion' => 'Área frontal central para gorras o viseras.'
            ],
            [
                'slug' => 'lateral_gorra',
                'nombre' => 'LATERAL GORRA',
                'descripcion' => 'Costados de la gorra, frecuentemente para banderas o logos pequeños.'
            ],
            [
                'slug' => 'puño_izquierdo',
                'nombre' => 'PUÑO IZQUIERDO',
                'descripcion' => 'Detalle elegante en la terminación de la manga izquierda.'
            ],
            [
                'slug' => 'puño_derecho',
                'nombre' => 'PUÑO DERECHO',
                'descripcion' => 'Detalle elegante en la terminación de la manga derecha.'
            ],
        ];

        foreach ($applicationTypes as $type) {
            Application_types::updateOrCreate(
                ['slug' => $type['slug']],
                [
                    'nombre_aplicacion' => $type['nombre'],
                    'descripcion' => $type['descripcion'],
                    'activo' => true,
                ]
            );
        }
    }
}
