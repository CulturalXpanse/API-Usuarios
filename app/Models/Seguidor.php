<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seguidor extends Model
{
    protected $table = 'seguidores';

    protected $fillable = [
        'seguidor_id', 'seguido_id',
    ];
}
