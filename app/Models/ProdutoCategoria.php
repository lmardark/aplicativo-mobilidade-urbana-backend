<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProdutoCategoria extends Model
{
    protected $fillable = [
        'produto_id',
        'categoria',
        'prioridade'
    ];
}
