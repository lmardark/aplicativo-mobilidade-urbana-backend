<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('corridas', function (Blueprint $table) {
            $table->id();

            // Relacionamentos
            // $table->foreignId('motorista_id')->constrained('users')->cascadeOnDelete();
            // $table->foreignId('passageiro_id')->constrained('users')->cascadeOnDelete();
            $table->integer('motorista_id');
            $table->integer('passageiro_id');
            $table->integer('veiculo_id');

            // Status da viagem
            $table->enum('status', [
                'solicitada',
                'aceita',
                'em_andamento',
                'finalizada',
                'cancelada'
            ])->default('solicitada');

            // Tempos
            $table->timestamp('tempo_solicitacao')->nullable();
            $table->timestamp('tempo_aceite')->nullable();
            $table->timestamp('tempo_inicio')->nullable();
            $table->timestamp('tempo_final')->nullable();

            // Dados da viagem
            $table->decimal('distancia_total', 10, 2)->nullable(); // km

            // Valores
            $table->decimal('valor_total', 10, 2)->nullable();
            $table->decimal('valor_motorista', 10, 2)->nullable();
            $table->decimal('valor_plataforma', 10, 2)->nullable();

            // Método de pagamento
            $table->enum('metodo_pagamento', [
                'dinheiro',
                'cartao',
                'pix'
            ])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('corridas');
    }
};
