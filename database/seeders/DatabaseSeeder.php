<?php

namespace Database\Seeders;

use App\Models\Tarifa;
use App\Models\Corrida;
use App\Models\CorridaDesconto;
use App\Models\CorridaDestino;
use App\Models\CorridaFinanceiro;
use App\Models\Motorista;
use App\Models\User;
use App\Models\Veiculo;
use App\Models\Passageiro;
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
        $userMotorista = User::factory()->create([
            'name' => 'Diogo Guimarães',
            'data_nascimento' => '2022-04-11',
            'telefone' => '69981400661',
            'email' => 'test@example.com',
            'cpf' => "01149897295",
            'foto' => "",
            'foto_thumbnail' => "",
            'status' => "ativo"
        ]);
        $motorista = Motorista::factory()->create([
            'user_id' => $userMotorista->id,
            'cnh_numero' => '2022-04-11',
            'cnh_categoria' => 'ABC',
            'cnh_expiracao' => '04-12-2029',
            'ear' => true,
        ]);
        Veiculo::factory()->create([
            'marca' => 'hyundai',
            'modelo' => 'hb20 sedan',
            'ano_fabricacao' => 2016,
            'ano_modelo' => 2016,
            'cor' => 'preto',
            'placa' => 'NCH9110',
            'renavam' => '12345678910',
            'categoria' => 'pop',
            'status' => 'pendente',
            'uf' => 'RO',
        ]);

        $userPassageiro = User::factory()->create([
            'name' => 'Passageiro Sobrenome',
            'data_nascimento' => '2022-04-12',
            'telefone' => '69981400662',
            'email' => 'passageiro@example.com',
            'cpf' => "01149897296",
            'foto' => "",
            'foto_thumbnail' => "",
            'status' => "ativo"
        ]);

        $passageiro = Passageiro::factory()->create([
            'user_id' => $userPassageiro->id,
            'media_avaliacao' => 4,
        ]);


        // 1. Criar a configuração de tarifa base
        $tarifa = Tarifa::factory()->create();

        // 2. Simular dados de uma corrida para o cálculo
        $distanciaKm = 10.5; // Ex: 10.5 km
        $tempoMin = 15;      // Ex: 15 minutos de viagem
        $tempoEsperaMin = 2; // Ex: 2 minutos aguardando o passageiro
        $desconto = 5.00;    // Ex: Cupom de R$ 5,00

        // 3. Aplicar a lógica de cálculo
        $valorDistancia = $distanciaKm * $tarifa->valor_km;
        $valorTempo = $tempoMin * $tarifa->valor_min;
        $valorEspera = $tempoEsperaMin * $tarifa->valor_espera_min;

        $valorBruto = $tarifa->tarifa_base + $valorDistancia + $valorTempo + $valorEspera;
        $valorPago = max(0, $valorBruto - $desconto);
        $taxaPlataforma = ($valorPago * $tarifa->taxa_plataforma_valor_percentual) / 100;
        $valorMotorista = $valorPago - $taxaPlataforma;

        $corrida = Corrida::factory()->create([
            'motorista_id' => $motorista->id,
            'passageiro_id' => $passageiro->id,
            'veiculo_id' => Veiculo::inRandomOrder()->first()->id ?? 1,
            'tarifa_id' => $tarifa->id,
            'cidade_id' => 1,
            'tempo_chegada_origem' => function (array $attributes) {
                return fake()->dateTimeBetween($attributes['tempo_solicitacao'], 'now');
            },
            'status' => 'finalizada',
            'tempo_solicitacao' => fake()->dateTimeBetween('-1 month', 'now'),
            'tempo_aceite' => function (array $attributes) {
                return fake()->dateTimeBetween($attributes['tempo_solicitacao'], 'now');
            },
            'tempo_inicio' => function (array $attributes) {
                return fake()->dateTimeBetween($attributes['tempo_aceite'], 'now');
            },
            'tempo_final' => function (array $attributes) {
                return fake()->dateTimeBetween($attributes['tempo_inicio'], 'now');
            },

            'distancia_total' => fake()->randomFloat(2, 1, 50), // Até 50km


        ]);

        $valorCorrida = fake()->randomFloat(2, 15, 150);
        $valorDesconto = fake()->randomFloat(2, 15, 150);
        $tarifaBase = 5.00;
        $taxaEspera = fake()->optional(0.3, 0)->randomFloat(2, 0, 10); // 30% de chance de ter taxa de espera
        $percentualPlataforma = 15.00; // Ex: 15%

        $valorTotalPassageiro = $valorCorrida + $taxaEspera;
        $valorTaxaPlataforma = ($valorTotalPassageiro * ($percentualPlataforma / 100));
        $valorLiquidoMotorista = $valorTotalPassageiro - $valorTaxaPlataforma;

        CorridaFinanceiro::factory()->create([
            'corrida_id' => $corrida->id,
            'valor_bruto' => $valorCorrida,
            'tarifa_base' => $tarifaBase,
            'taxa_espera' => $taxaEspera,
            'valor_descontos' => $valorDesconto,
            'valor_pago_passageiro' => $valorTotalPassageiro,
            'percentual_plataforma' => $percentualPlataforma,
            'taxa_plataforma_valor_percentual' => $valorTaxaPlataforma,
            'valor_motorista' => $valorLiquidoMotorista,
            'valor_liquido_motorista' => $valorLiquidoMotorista,
        ]);

        // fake()->randomElement(['origem', 'parada', 'destino']
        CorridaDestino::factory()->create([
            'corrida_id' => $corrida->id,
            // Define se é o início, uma parada intermediária ou o fim
            'tipo' => 'origem',
            // A ordem deve ser única por corrida (0 para origem, 1+ para paradas/destino)
            'ordem' => 0,
            'endereco' => fake()->address(),
            // Coordenadas aproximadas de Porto Velho para testes realistas
            'latitude' => fake()->latitude(-8.79, -8.74),
            'longitude' => fake()->longitude(-63.92, -63.84),
            // Distância em KM até o próximo ponto (se houver)
            'distancia_ate_proximo_destino' => fake()->randomFloat(2, 0.5, 10),
        ]);

        CorridaDestino::factory()->create([
            'corrida_id' => $corrida->id,
            // Define se é o início, uma parada intermediária ou o fim
            'tipo' => 'parada',
            // A ordem deve ser única por corrida (0 para origem, 1+ para paradas/destino)
            'ordem' => 1,
            'endereco' => fake()->address(),
            // Coordenadas aproximadas de Porto Velho para testes realistas
            'latitude' => fake()->latitude(-8.79, -8.74),
            'longitude' => fake()->longitude(-63.92, -63.84),
            // Distância em KM até o próximo ponto (se houver)
            'distancia_ate_proximo_destino' => fake()->randomFloat(2, 0.5, 10),
        ]);

        CorridaDestino::factory()->create([
            'corrida_id' => $corrida->id,
            // Define se é o início, uma parada intermediária ou o fim
            'tipo' => 'destino',
            // A ordem deve ser única por corrida (0 para origem, 1+ para paradas/destino)
            'ordem' => 2,
            'endereco' => fake()->address(),
            // Coordenadas aproximadas de Porto Velho para testes realistas
            'latitude' => fake()->latitude(-8.79, -8.74),
            'longitude' => fake()->longitude(-63.92, -63.84),
            // Distância em KM até o próximo ponto (se houver)
            'distancia_ate_proximo_destino' => fake()->randomFloat(2, 0.5, 10),
        ]);

        CorridaDesconto::factory()->create([
            'corrida_id' => $corrida->id,
            'tipo' => 'cupom',
            'codigo' => 'PROMO10',
            'valor_desconto' => 10,
            'descricao' => 'Desconto Aplicado via Seeder',
        ]);
    }
}
