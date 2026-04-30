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
        Schema::create('corrida_financeiros', function (Blueprint $table) {
            $table->id();

            // Relacionamento com a corrida
            // $table->foreignId('corrida_id')->constrained('viagens')->cascadeOnDelete();
            $table->integer('corrida_id')->unique();

            // Valores base da corrida
            $table->decimal('valor_corrida', 10, 2)->nullable();
            $table->decimal('tarifa_base', 10, 2)->nullable();
            $table->decimal('taxa_espera', 10, 2)->nullable();

            // Valor final pago pelo passageiro
            $table->decimal('valor_pago_passageiro', 10, 2)->nullable();

            // Taxas da plataforma
            $table->decimal('taxa_plataforma_valor', 10, 2)->nullable();
            $table->decimal('taxa_plataforma_percentual', 5, 2)->nullable(); // ex: 15.50%

            // Valor que o motorista recebe
            $table->decimal('valor_motorista', 10, 2)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('corrida_financeiros');
    }
};
