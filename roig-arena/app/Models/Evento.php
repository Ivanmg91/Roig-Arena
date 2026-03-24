<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Evento extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'eventos';
    
    protected $fillable = [
        'nombre',
        'descripcion_corta',
        'descripcion_larga',
        'poster_url',
        'poster_ancho_url',
        'fecha',
        'hora',
    ];

    /**
     * Casteo de tipos
     */
    protected $casts = [
        'fecha' => 'date',
        'hora' => 'datetime:H:i',
    ];

    // ============================================
    // RELACIONES
    // ============================================

    /**
     * Un evento tiene muchos precios (uno por sector)
     */
    public function precios()
    {
        return $this->hasMany(Precio::class);
    }

    /**
     * Un evento tiene sectores disponibles (a través de precios)
     */
    public function sectores()
    {
        return $this->belongsToMany(Sector::class, 'precios')
                    ->withPivot('precio', 'disponible')
                    ->withTimestamps();
    }

    /**
     * Un evento tiene muchos estados de asientos
     */
    public function estadoAsientos()
    {
        return $this->hasMany(EstadoAsiento::class);
    }

    /**
     * Un evento tiene muchas entradas vendidas
     */
    public function entradas()
    {
        return $this->hasMany(Entrada::class);
    }

    /**
     * Un evento tiene muchos artistas
     */
    public function artistas()
    {
        return $this->hasMany(Artista::class);
    }

    // ============================================
    // MÉTODOS ÚTILES
    // ============================================

    /**
     * Obtener sectores disponibles (activos y con disponible=true)
     */
    public function sectoresDisponibles()
    {
        return $this->sectores()
            ->where('sectores.activo', true)
            ->wherePivot('disponible', true)
            ->get();
    }

    /**
     * Obtener el precio de un sector específico
     */
    public function precioDelSector($sectorId)
    {
        return $this->precios()
            ->where('sector_id', $sectorId)
            ->first();
    }

    /**
     * Verificar si un sector está disponible para este evento
     */
    public function sectorEstaDisponible($sectorId): bool
    {
        return $this->precios()
            ->where('sector_id', $sectorId)
            ->where('disponible', true)
            ->exists();
    }

    /**
     * Obtener total de asientos disponibles
     */
    public function totalAsientosDisponibles(): int
    {
        $sectoresDisponibles = $this->sectoresDisponibles()->pluck('id');
        
        $totalAsientos = Asiento::whereIn('sector_id', $sectoresDisponibles)->count();
        $asientosOcupados = $this->estadoAsientos()->count();
        
        return $totalAsientos - $asientosOcupados;
    }

    /**
     * Obtener total de entradas vendidas
     */
    public function totalEntradasVendidas(): int
    {
        return $this->entradas()->count();
    }

    /**
     * Verificar si el evento ya pasó
     */
    public function yaPaso(): bool
    {
        return $this->fecha->isPast();
    }

    /**
     * Verificar si el evento es hoy
     */
    public function esHoy(): bool
    {
        return $this->fecha->isToday();
    }

    /**
     * Scope: Solo eventos futuros
     */
    public function scopeFuturos($query)
    {
        return $query->where('fecha', '>=', now()->toDateString())
                     ->orderBy('fecha', 'asc');
    }

    /**
     * Scope: Solo eventos pasados
     */
    public function scopePasados($query)
    {
        return $query->where('fecha', '<', now()->toDateString())
                     ->orderBy('fecha', 'desc');
    }

    /**
     * Scope: Eventos de un mes específico
     */
    public function scopeDelMes($query, $mes, $anio)
    {
        return $query->whereMonth('fecha', $mes)
                     ->whereYear('fecha', $anio);
    }
}