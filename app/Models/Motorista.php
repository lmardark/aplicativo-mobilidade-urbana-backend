<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'user_id',
    'cnh_numero',
    'cnh_categoria',
    'cnh_expiracao',
    'ear',
])]

class Motorista extends Model
{
    use  SoftDeletes;
}
