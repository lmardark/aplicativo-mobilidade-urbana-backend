<?php

namespace Database\Seeders;

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
        $user = User::factory()->create([
            'name' => 'Diogo Guimarães',
            'data_nascimento' => '2022-04-11',
            'telefone' => '69981400661',
            'email' => 'test@example.com',
            'cpf' => "01149897295",
            'foto' => "",
            'foto_thumbnail' => "",
            'status' => "ativo"
        ]);
        Motorista::factory()->create([
            'user_id' => $user->id,
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

        $passageiro = User::factory()->create([
            'name' => 'Passageiro Sobrenome',
            'data_nascimento' => '2022-04-12',
            'telefone' => '69981400662',
            'email' => 'test@test.com',
            'cpf' => "01149897296",
            'foto' => "",
            'foto_thumbnail' => "",
            'status' => "ativo"
        ]);

        Passageiro::factory()->create([
            'user_id' => $passageiro->id,
            'media_avaliacao' => 4,
        ]);
    }
}
