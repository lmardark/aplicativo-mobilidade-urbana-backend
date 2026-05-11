<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Corrida extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo_corrida',
        'produto_id',
        // 'dinamica_regioes',
        'motorista_id',
        'passageiro_id',
        'cidade_id',
        'veiculo_id',
        'tarifa_id',
        'multiplicador_dinamico',
        'tempo_chegada_origem',
        'status_corrida',
        'status_negociacao',
        'cancelado_por',
        'tempo_solicitacao',
        'tempo_solicitacao',
        'tempo_aceite',
        'tempo_embarque',
        'tempo_inicio',
        'tempo_final',
        'distancia_total',
        'valor_estimado_inicial',
        'valor_negociado_final',
        'motivo_cancelamento',
        'distancia_ate_motorista',
        'metodo_pagamento',
        'status_pagamento',
    ];

    public function corrida_financeiro()
    {
        return $this->hasOne(CorridaFinanceiro::class, 'corrida_id', 'id');
    }

    public function corrida_destinos()
    {
        return $this->hasMany(CorridaDestino::class);
    }

    public function motorista()
    {
        return $this->belongsTo(Motorista::class);
    }

    public function passageiro()
    {
        return $this->belongsTo(Passageiro::class);
    }

    public function veiculo()
    {
        return $this->belongsTo(Veiculo::class);
    }
}
