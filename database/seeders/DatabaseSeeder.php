<?php

namespace Database\Seeders;

use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'master',
            'email' => 'master@gmail.com',
            'password' => bcrypt('1qaz2wsx'),
        ]);



        $this->call(EstadoSeeder::class);
        $this->call(GiroSeeder::class);

        Proveedor::create([
            'nombre_proveedor' => 'IGNIS SOLUCIONES INTEGRALES, S.A. DE C.V.',

            'direccion' => 'C 49 D X 42 Y 46 FCO DE MONTEJO',
            'codigo_postal' => '27160',
            'telefono' => '2421234567',
            'email' => 'ignis@gmail.com',
            'estado_id' => 1,
            'giro_id' => '1',
        ]);
        Proveedor::create([
            'nombre_proveedor' => 'GLOBAL SERVICES 4 IT, S.A. DE C.V.',
            'direccion' => 'C 49 D X 42 Y 46 FCO DE MONTEJO II',
            'codigo_postal' => '27160',
            'telefono' => '2421234567',
            'email' => 'global@gmail.com',
            'estado_id' => 1,
            'giro_id' => '3',
        ]);
    }
}
