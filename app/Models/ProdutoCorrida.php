<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProdutoCorrida extends Model
{
    use HasFactory;
    protected $fillable = [
        'nome',
        'codigo',
        'estrategia_precificacao',
    ];
}
