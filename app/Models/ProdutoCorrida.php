<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProdutoCorrida extends Model
{
    protected $fillable = [
        'nome',
        'codigo',
        'categoria_veiculo',
        'modo_precificacao',
        'aceita_multiplas_categorias',
    ];
}
