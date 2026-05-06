<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CorridaFinanceiro extends Model
{
    use HasFactory;
    protected $fillable = [
        'corrida_id',
        'valor_bruto',
        'tarifa_base',
        'valor_dinamico_aplicado',
        'valor_por_km',
        'valor_por_minuto',
        'valor_por_minuto_espera',
        'taxa_espera',
        'valor_descontos',
        'valor_sem_dinamica',
        'valor_pago_passageiro',
        'taxa_plataforma_valor_percentual',
        'percentual_plataforma',
        'valor_base_calculado',
        'valor_ajuste_negociado',
        'valor_motorista',
        'valor_liquido_motorista',
        'metodo_pagamento'
    ];

    public function corrida_desconto()
    {
        return $this->hasOne(CorridaDesconto::class, 'corrida_id', 'id');
    }
}
