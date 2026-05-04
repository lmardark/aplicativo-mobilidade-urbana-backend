<?php

namespace Database\Factories;

use App\Models\Tarifa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tarifa>
 */
class TarifaFactory extends Factory
{
    protected $model = Tarifa::class;

    public function definition(): array
    {
        return [
            'tarifa_base' => 4.10,
            'valor_por_km' => 1.50,
            'valor_por_minuto' => 0.25,
            'valor_por_minuto_espera' => 0.15,
            'taxa_plataforma_valor_percentual' => 5,
            'ativo' => true,
        ];
    }
}
