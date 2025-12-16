<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Estado;

class EstadoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $estados = [
            'AGUASCALIENTES',
            'BAJA CALIFORNIA',
            'BAJA CALIFORNIA SUR',
            'CAMPECHE',
            'CHIAPAS',
            'CHIHUAHUA',
            'CIUDAD DE MÉXICO',
            'COAHUILA',
            'COLIMA',
            'DURANGO',
            'ESTADO DE MÉXICO',
            'GUANAJUATO',
            'GUERRERO',
            'HIDALGO',
            'JALISCO',
            'MICHOACÁN',
            'MORELOS',
            'NAYARIT',
            'NUEVO LEÓN',
            'OAXACA',
            'PUEBLA',
            'QUERÉTARO',
            'QUINTANA ROO',
            'SAN LUIS POTOSÍ',
            'SINALOA',
            'SONORA',
            'TABASCO',
            'TAMAULIPAS',
            'TLAXCALA',
            'VERACRUZ',
            'YUCATÁN',
            'ZACATECAS',
        ];

        foreach ($estados as $estado) {
            Estado::create([
                'nombre' => $estado,
                'activo' => true,
            ]);
        }
    }
}
