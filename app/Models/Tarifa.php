<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tarifa extends Model
{
    use HasFactory;

    protected $fillable = [
        'cidade_id',
        'produto_id',
        'nome',
        'tarifa_base',
        'valor_por_km',
        'valor_por_minuto',
        'dias_semana',
        'valor_por_minuto_espera',
        'taxa_plataforma_valor',
        'taxa_plataforma_valor_percentual',
        'ativo',
        'categoria',
        'tipo_corrida',
        'horario_inicio',
        'horario_fim',
        'vira_dia',
        'valor_minimo_corrida',
        'multiplicador_dinamico',
        'taxa_plataforma_percentual',
        'raio_busca_motorista_km',
    ];
}
