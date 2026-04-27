<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
    use HasFactory;

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
