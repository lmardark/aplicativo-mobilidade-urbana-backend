<?php

namespace App\Models;

use Database\Factories\VeiculoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $marca
 * @property string $modelo
 * @property string $cor
 * @property string $placa
 * @property string $categoria carro ou moto
 * @property bool $eletrico aprovado pela gestão: recebe corridas Elétrico
 * @property bool $taxi aprovado pela gestão: recebe corridas Táxi
 * @property bool $eletrico_solicitado pedido do motorista, em análise
 * @property bool $taxi_solicitado pedido do motorista, em análise
 */
class Veiculo extends Model
{
    /** @use HasFactory<VeiculoFactory> */
    use HasFactory;

    protected $fillable = [
        'marca',
        'modelo',
        'ano_fabricacao',
        'ano_modelo',
        'cor',
        'placa',
        'renavam',
        'categoria',
        'eletrico',
        'taxi',
        'eletrico_solicitado',
        'taxi_solicitado',
        'status',
        'uf',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'eletrico' => 'boolean',
            'taxi' => 'boolean',
            'eletrico_solicitado' => 'boolean',
            'taxi_solicitado' => 'boolean',
        ];
    }
}
