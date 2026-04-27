<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'motorista_id',
    'veiculo_id',
])]

class MotoristaVeiculo extends Model
{
    public function motorista()
    {
        return $this->belongsTo(User::class, 'motorista_id');
    }
    public function veiculo()
    {
        return $this->belongsTo(Veiculo::class, 'motorista_id');
    }
}
