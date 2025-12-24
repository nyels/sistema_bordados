<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Animales',
            'Flores',
            'Abstracto',
            'Autos',
            'Edificios',
            'Naturaleza',
            'Personas',
            'Paisajes',
            'Arte',
            'Tecnología',
            'Deportes',
            'Comida',
            'Bebidas',
            'Mascotas',
            'Ciencia',
            'Espacio',
            'Fantasía',
            'Mitología',
            'Historia',
            'Viajes',
            'Música',
            'Instrumentos',
            'Moda',
            'Calzado',
            'Accesorios',
            'Joyas',
            'Arquitectura',
            'Hogar',
            'Muebles',
            'Decoración',
            'Minimalista',
            'Geométrico',
            'Tipografía',
            'Frases',
            'Emociones',
            'Espiritual',
            'Religión',
            'Símbolos',
            'Logotipos',
            'Negocios',
            'Educación',
            'Salud',
            'Medicina',
            'Fitness',
            'Yoga',
            'Artes Marciales',
            'Militar',
            'Vehículos',
            'Motocicletas',
            'Bicicletas',
            'Aviación',
            'Marítimo',
            'Industria',
            'Herramientas',
            'Robótica',
            'Inteligencia Artificial',
            'Videojuegos',
            'Cine',
            'Series',
            'Caricaturas',
            'Infantil',
            'Festividades',
            'Halloween',
            'Navidad',
            'Tradicional',
            'Cultural'
        ];

        $order = 1;

        foreach ($categories as $name) {
            Category::create([
                'name'        => $name,
                'slug'        => Str::slug($name),
                'description' => "Diseños relacionados con {$name}",
                'is_active'   => true,
                'order'       => $order++
            ]);
        }
    }
}
