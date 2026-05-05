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
        Schema::create('tarifas', function (Blueprint $table) {
            $table->id();
            $table->integer('cidade_id')->nullable();
            $table->string('nome')->nullable();
            $table->decimal('tarifa_base', 10, 2)->nullable();
            $table->decimal('valor_por_km', 10, 2)->nullable();
            $table->decimal('valor_por_minuto', 10, 2)->nullable();
            $table->decimal('valor_por_minuto_espera', 10, 2)->nullable();

            $table->decimal('taxa_plataforma_valor_percentual', 10, 2)->nullable();

            $table->enum('categoria', [
                'economico',
                'premium',
            ])->nullable();

            $table->enum('tipo_corrida', [
                'pop',
                'pop express',
                'negociada',
            ])->nullable();

            $table->timestamp('horario_inicio')->nullable();
            $table->timestamp('horario_fim')->nullable();
            $table->integer('multiplicador_dinamico')->nullable();
            $table->boolean('ativo')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tarifas');
    }
};
