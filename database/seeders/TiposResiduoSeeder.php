<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TipoResiduo;

class TiposResiduoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tipos = [
            [
                'nombre' => 'Plástico',
                'descripcion' => 'Botellas PET, envases, bolsas plásticas',
                'puntos_por_kg' => 15.00,
                'color_hex' => '#FF6B6B',
                'activo' => true,
            ],
            [
                'nombre' => 'Papel',
                'descripcion' => 'Cartón, revistas, periódicos, hojas',
                'puntos_por_kg' => 10.00,
                'color_hex' => '#4ECDC4',
                'activo' => true,
            ],
            [
                'nombre' => 'Vidrio',
                'descripcion' => 'Botellas, frascos, cristales',
                'puntos_por_kg' => 8.00,
                'color_hex' => '#95E1D3',
                'activo' => true,
            ],
            [
                'nombre' => 'Metal',
                'descripcion' => 'Latas de aluminio, acero, cobre',
                'puntos_por_kg' => 20.00,
                'color_hex' => '#FFE66D',
                'activo' => true,
            ],
            [
                'nombre' => 'Electrónico',
                'descripcion' => 'Celulares, computadoras, cables, baterías',
                'puntos_por_kg' => 50.00,
                'color_hex' => '#A8E6CF',
                'activo' => true,
            ],
            [
                'nombre' => 'Orgánico',
                'descripcion' => 'Restos de comida, cáscaras, hojas',
                'puntos_por_kg' => 5.00,
                'color_hex' => '#C7CEEA',
                'activo' => true,
            ],
            [
                'nombre' => 'Textil',
                'descripcion' => 'Ropa, telas, zapatos',
                'puntos_por_kg' => 12.00,
                'color_hex' => '#FFDAC1',
                'activo' => true,
            ],
        ];

        foreach ($tipos as $tipo) {
            TipoResiduo::create($tipo);
        }
    }
}
