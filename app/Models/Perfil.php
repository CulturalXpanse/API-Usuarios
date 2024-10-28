<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Perfil extends Model
{
    use HasFactory;

    protected $table = 'perfiles';
    protected $fillable = [
        'user_id', 
        'ciudad_id', 
        'nombre_completo', 
        'biografia', 
        'foto_perfil'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}