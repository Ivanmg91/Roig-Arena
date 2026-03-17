<?php

namespace App\Services;

use App\Models\EstadoAsiento;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReservaService
{
    /**
     * Reserva un asiento durante 15 minutos.
     *
     * @throws \Exception
     */
    public function reservarAsiento(int $eventoId, int $asientoId, int $userId): EstadoAsiento
    {
        return DB::transaction(function () use ($eventoId, $asientoId, $userId) {
            $estado = EstadoAsiento::where('evento_id', $eventoId)
                ->where('asiento_id', $asientoId)
                ->lockForUpdate()
                ->first();

            if ($estado && $estado->estado === 'vendido') {
                throw new \Exception('El asiento ya ha sido vendido.');
            }

            if ($estado && $estado->estado === 'bloqueado' && !$estado->haExpirado() && $estado->user_id !== $userId) {
                throw new \Exception('El asiento está reservado por otro usuario.');
            }

            if (!$estado) {
                $estado = new EstadoAsiento();
                $estado->evento_id = $eventoId;
                $estado->asiento_id = $asientoId;
            }

            $estado->user_id = $userId;
            $estado->estado = 'bloqueado';
            $estado->reservado_hasta = now()->addMinutes(15);
            $estado->save();

            return $estado->load(['evento', 'asiento.sector', 'user']);
        });
    }

    /**
     * Obtiene reservas activas del usuario autenticado.
     */
    public function obtenerReservasActivas(int $userId): Collection
    {
        return EstadoAsiento::query()
            ->deUsuario($userId)
            ->bloqueados()
            ->where('reservado_hasta', '>', now())
            ->with(['evento', 'asiento.sector'])
            ->orderBy('reservado_hasta')
            ->get();
    }

    /**
     * Cancela una reserva del usuario.
     *
     * @throws \Exception
     */
    public function cancelarReserva(int $reservaId, int $userId): void
    {
        $reserva = EstadoAsiento::query()
            ->where('id', $reservaId)
            ->where('user_id', $userId)
            ->bloqueados()
            ->first();

        if (!$reserva) {
            throw new \Exception('La reserva no existe o no pertenece al usuario.');
        }

        $reserva->delete();
    }

    /**
     * Libera todas las reservas expiradas.
     */
    public function liberarReservasExpiradas(): int
    {
        return EstadoAsiento::expirados()->delete();
    }
}
