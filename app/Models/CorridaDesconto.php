<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CorridaDesconto extends Model
{
    use HasFactory;
    protected $fillable = [
        'corrida_id',
        'tipo',
        'codigo',
        'valor_desconto',
        'percentual_desconto',
        'descricao',
    ];
}
