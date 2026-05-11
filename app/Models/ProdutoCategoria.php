<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProdutoCategoria extends Model
{
    use HasFactory;
    protected $fillable = [
        'produto_id',
        'prioridade',
        'categoria',
    ];

    public function produto()
    {
        return $this->belongsTo(ProdutosCorrida::class, 'produto_id');
    }
}
