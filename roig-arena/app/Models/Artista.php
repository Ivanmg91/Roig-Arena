<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Artista extends Model
{
    use HasFactory;

    protected $table = 'artistas';
    
    protected $fillable = [
        'nombre',
        'evento_id',
        'descripcion',
        'imagen_url',
    ];

    /**
     * Relación con Evento
     */
    public function evento()
    {
        return $this->belongsTo(Evento::class);
    }
}
