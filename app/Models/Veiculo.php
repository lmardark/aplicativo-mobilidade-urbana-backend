<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Veiculo extends Model
{
    protected $fillable = [
        'marca',
        'modelo',
        'ano_fabricacao',
        'ano_modelo',
        'cor',
        'placa',
        'renavam',
        'categoria',
        'status',
        'uf',
    ];
}
