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
        Schema::create('corria_destinos', function (Blueprint $table) {
            $table->id();

            // Relacionamento com a viagem
            // $table->foreignId('corrida_id')->constrained('viagens')->cascadeOnDelete();
            $table->integer('corrida_id');

            // Tipo do ponto
            $table->enum('tipo', [
                'origem',
                'parada',
                'destino'
            ]);

            // Ordem no trajeto
            $table->unsignedInteger('ordem');

            // Localização
            $table->string('endereco');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            // Distância até o próximo ponto
            $table->decimal('distancia_ate_proximo', 10, 2)->nullable();

            // Timestamp
            $table->timestamp('created_at')->useCurrent();

            // Índices importantes
            $table->index(['corrida_id', 'ordem']);
            $table->unique(['corrida_id', 'ordem']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('corria_destinos');
    }
};
