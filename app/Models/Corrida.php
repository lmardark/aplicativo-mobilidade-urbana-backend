<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Corrida extends Model
{
    use HasFactory;

    protected $fillable = [
        'motorista_id',
        'passageiro_id',
        'veiculo_id',
        'status',
        'tempo_solicitacao',
        'tempo_solicitacao',
        'tempo_aceite',
        'tempo_inicio',
        'tempo_final',
        'distancia_total',
        'valor_total',
        'valor_motorista',
        'valor_plataforma',
        'metodo_pagamento',



    ];
}
