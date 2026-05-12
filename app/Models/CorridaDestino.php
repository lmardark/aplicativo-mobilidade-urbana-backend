<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CorridaDestino extends Model
{
    use HasFactory;
    protected $fillable = [
        'corrida_id',
        'nome_local',
        'tipo',
        'ordem',
        'endereco',
        'latitude',
        'longitude',
        'tempo_estimado_ate_proximo_destino',
        'distancia_ate_proximo_destino'
    ];
}
