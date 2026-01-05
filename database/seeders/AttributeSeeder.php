<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Attribute;

class AttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear atributo Color
        $colorAttr = Attribute::create([
            'name' => 'Color',
            'slug' => 'color',
            'type' => 'color',
            'is_required' => true,
            'order' => 1
        ]);

        // Valores de color
        $colorAttr->values()->createMany([
            ['value' => 'Rojo', 'hex_color' => '#FF0000', 'order' => 1],
            ['value' => 'Azul', 'hex_color' => '#0000FF', 'order' => 2],
            ['value' => 'Verde', 'hex_color' => '#00FF00', 'order' => 3],
            ['value' => 'Amarillo', 'hex_color' => '#FFFF00', 'order' => 4],
            ['value' => 'Morado', 'hex_color' => '#800080', 'order' => 5],
            ['value' => 'Rosa', 'hex_color' => '#FFC0CB', 'order' => 6],
        ]);

        // Crear atributo Tamaño
        $sizeAttr = Attribute::create([
            'name' => 'Tamaño',
            'slug' => 'tamano',
            'type' => 'select',
            'is_required' => false,
            'order' => 2
        ]);

        // Valores de tamaño
        $sizeAttr->values()->createMany([
            ['value' => 'XS', 'order' => 1],
            ['value' => 'S', 'order' => 2],
            ['value' => 'M', 'order' => 3],
            ['value' => 'L', 'order' => 4],
            ['value' => 'XL', 'order' => 5],
        ]);
    }
}
