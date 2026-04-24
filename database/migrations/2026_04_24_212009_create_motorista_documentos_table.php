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
        Schema::create('motorista_documentos', function (Blueprint $table) {
            $table->id();
            $table->integer('motorista_id');
            $table->string('documento');
            $table->string('name');
            $table->string('type');
            $table->string('mime_type');
            $table->string('size');
            $table->string('path');
            $table->string('status');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('motorista_documentos');
    }
};
