<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MotoristaDocumento extends Model
{
    protected $fillable = [
        'motorista_id',
        'documento',
        'name',
        'type',
        'mime_type',
        'size',
        'path',
        'status',
    ];
}
