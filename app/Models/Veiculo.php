<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Veiculo extends Model
{
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
        'status',
        'uf',
    ];
}
