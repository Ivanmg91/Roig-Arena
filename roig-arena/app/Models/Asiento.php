<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asiento extends Model
{
    use HasFactory;

    protected $table = 'asientos';
    
    protected $fillable = [
        'sector_id',
        'fila',
        'numero',
    ];

    // ============================================
    // RELACIONES
    // ============================================

    /**
     * Un asiento pertenece a un sector
     */
    public function sector()
    {
        return $this->belongsTo(Sector::class);
    }

    /**
     * Un asiento puede tener muchos estados (uno por evento)
     */
    public function estadoAsientos()
    {
        return $this->hasMany(EstadoAsiento::class);
    }

    /**
     * Un asiento puede tener muchas entradas vendidas (una por evento)
     */
    public function entradas()
    {
        return $this->hasMany(Entrada::class);
    }

    // ============================================
    // MÉTODOS ÚTILES
    // ============================================

    /**
     * Obtener el nombre completo del asiento
     * Ejemplo: "VIP - Fila A - Asiento 5"
     */
    public function nombreCompleto(): string
    {
        return "{$this->sector->nombre} - Fila {$this->fila} - Asiento {$this->numero}";
    }

    /**
     * Verificar si el asiento está disponible para un evento
     */
    public function estaDisponibleParaEvento($eventoId): bool
    {
        return !$this->estadoAsientos()
            ->where('evento_id', $eventoId)
            ->exists();
    }

    /**
     * Verificar si el asiento está reservado (bloqueado) para un evento
     */
    public function estaReservadoParaEvento($eventoId): bool
    {
        return $this->estadoAsientos()
            ->where('evento_id', $eventoId)
            ->where('estado', 'bloqueado')
            ->where('reservado_hasta', '>', now())
            ->exists();
    }

    /**
     * Verificar si el asiento está vendido para un evento
     */
    public function estaVendidoParaEvento($eventoId): bool
    {
        return $this->estadoAsientos()
            ->where('evento_id', $eventoId)
            ->where('estado', 'vendido')
            ->exists();
    }

    /**
     * Obtener el estado actual para un evento específico
     */
    public function estadoParaEvento($eventoId)
    {
        return $this->estadoAsientos()
            ->where('evento_id', $eventoId)
            ->first();
    }

    /**
     * Scope: Asientos de un sector específico
     */
    public function scopeDeSector($query, $sectorId)
    {
        return $query->where('sector_id', $sectorId);
    }

    /**
     * Scope: Asientos de una fila específica
     */
    public function scopeDeFila($query, $fila)
    {
        return $query->where('fila', $fila);
    }
}