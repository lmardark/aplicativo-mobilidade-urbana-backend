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
        Schema::create('produto_categorias', function (Blueprint $table) {
            $table->id();
            $table->boolean('produto_id')->nullable(0);
            $table->boolean('categoria')->nullable(0);
            // prioridade
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produto_categorias');
    }
};
