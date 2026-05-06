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
        Schema::create('produto_corridas', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo_corrida', [
                'pop',
                'pop express',
                'negociada',
            ])->nullable();
            $table->enum('nome', [
                'Pop',
                'Electric Pro',
                'Negocia'
            ]);
            $table->enum('codigo', [
                'pop',
                'electric_pro',
                'Negocia'
            ]);
            $table->enum('categoria_veiculo', [
                'carro',
                'moto',
                'eletrico'
            ]);
            $table->enum('estrategia_precificacao', [
                'normal',
                'negociada',
            ]);
            $table->boolean('aceita_multiplas_categorias')->nullable(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produto_corridas');
    }
};
