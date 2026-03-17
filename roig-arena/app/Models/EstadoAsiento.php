<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstadoAsiento extends Model
{
    use HasFactory;

    protected $table = 'estado_asientos';
    
    protected $fillable = [
        'evento_id',
        'asiento_id',
        'user_id',
        'estado',
        'reservado_hasta',
    ];

    /**
     * Casteo de tipos
     */
    protected $casts = [
        'reservado_hasta' => 'datetime',
    ];

    // ============================================
    // RELACIONES
    // ============================================

    /**
     * Un estado pertenece a un evento
     */
    public function evento()
    {
        return $this->belongsTo(Evento::class);
    }

    /**
     * Un estado pertenece a un asiento
     */
    public function asiento()
    {
        return $this->belongsTo(Asiento::class);
    }

    /**
     * Un estado pertenece a un usuario
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ============================================
    // MÉTODOS ÚTILES
    // ============================================

    /**
     * Verificar si la reserva ha expirado
     */
    public function haExpirado(): bool
    {
        if ($this->estado === 'vendido') {
            return false; // Las ventas no expiran
        }

        return $this->reservado_hasta && $this->reservado_hasta->isPast();
    }

    /**
     * Verificar si está bloqueado (en carrito)
     */
    public function estaBloqueado(): bool
    {
        return $this->estado === 'bloqueado' && !$this->haExpirado();
    }

    /**
     * Verificar si está vendido
     */
    public function estaVendido(): bool
    {
        return $this->estado === 'vendido';
    }

    /**
     * Obtener tiempo restante de la reserva en minutos
     */
    public function tiempoRestante(): ?int
    {
        if ($this->estado === 'vendido' || !$this->reservado_hasta) {
            return null;
        }

        $diff = now()->diffInMinutes($this->reservado_hasta, false);
        return $diff > 0 ? $diff : 0;
    }

    /**
     * Liberar el asiento (eliminar la reserva)
     */
    public function liberar(): bool
    {
        return $this->delete();
    }

    /**
     * Marcar como vendido
     */
    public function marcarComoVendido(): bool
    {
        $this->estado = 'vendido';
        $this->reservado_hasta = null;
        return $this->save();
    }

    /**
     * Scope: Solo reservas bloqueadas
     */
    public function scopeBloqueados($query)
    {
        return $query->where('estado', 'bloqueado');
    }

    /**
     * Scope: Solo ventas
     */
    public function scopeVendidos($query)
    {
        return $query->where('estado', 'vendido');
    }

    /**
     * Scope: Reservas expiradas
     */
    public function scopeExpirados($query)
    {
        return $query->where('estado', 'bloqueado')
                     ->where('reservado_hasta', '<', now());
    }

    /**
     * Scope: Reservas de un usuario
     */
    public function scopeDeUsuario($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Estados de un evento
     */
    public function scopeDeEvento($query, $eventoId)
    {
        return $query->where('evento_id', $eventoId);
    }
}