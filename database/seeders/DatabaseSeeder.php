<?php

namespace Database\Seeders;

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
            'name' => 'my name',
            'data_nascimento' => '2022-04-11',
            'telefone' => '69981400661',
            'email' => 'test@example.com',
            'cpf' => "01149897295",
            'foto' => "https://api.pen6.app/images/1775760201_thumbnail_WhatsApp Image 2026-04-09 at 14.42.59.jpeg",
            'type' => "gestao",
            'status' => "ativo"
        ]);
    }
}
