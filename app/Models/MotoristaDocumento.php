<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;

use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'motorista_id',
    'documento',
    'name',
    'type',
    'mime_type',
    'size',
    "path",
    'status',
])]

class MotoristaDocumento extends Model
{
    //
}
