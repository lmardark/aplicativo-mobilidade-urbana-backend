<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tarifa extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
        'tarifa_base',
        'valor_por_km',
        'valor_por_minuto',
        'valor_por_minuto_espera',
        'taxa_plataforma_valor',
        'taxa_plataforma_valor_percentual',
        'ativo',
        'cidade_id',
        'categoria',
        'horario_inicio',
        'horario_fim',
        'multiplicador_dinamico'
    ];
}
