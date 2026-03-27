<?php

namespace App\Services;

use App\Models\EstadoAsiento;
use App\Models\Asiento;
use App\Models\Evento;
use Illuminate\Support\Facades\DB;

class ReservaService
{
    /**
     * Reservar un asiento para un evento
     */
    public function reservarAsiento($eventoId, $asientoId, $userId)
    {
        DB::beginTransaction();
        try {
            // Bloqueo pesimista: evita race condition
            $existeReserva = EstadoAsiento::where('evento_id', $eventoId)
                ->where('asiento_id', $asientoId)
                ->lockForUpdate()
                ->first();
            
            if ($existeReserva) {
                throw new \Exception('El asiento no está disponible');
            }
            
            $asiento = Asiento::findOrFail($asientoId);
            $evento = Evento::findOrFail($eventoId);
            
            $this->verificarSectorDisponible($evento, $asiento->sector_id);
            
            $reserva = EstadoAsiento::create([
                'evento_id' => $eventoId,
                'asiento_id' => $asientoId,
                'user_id' => $userId,
                'estado' => 'RESERVADO',
                'reservado_hasta' => now()->addMinutes(15),
            ]);

            DB::commit();
            
            return $reserva->load(['evento', 'asiento.sector']);
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cancelar una reserva
     */
    public function cancelarReserva($reservaId, $userId)
    {
        $reserva = EstadoAsiento::where('id', $reservaId)
            ->where('user_id', $userId)
            ->where('estado', 'RESERVADO')
            ->firstOrFail();

        $reserva->delete();
        
        return true;
    }

    /**
     * Obtener reservas activas de un usuario
     */
    public function obtenerReservasActivas($userId)
    {
        return EstadoAsiento::where('user_id', $userId)
            ->where('estado', 'RESERVADO')
            ->where('reservado_hasta', '>', now())
            ->with(['evento', 'asiento.sector'])
            ->get();
    }

    /**
     * Verificar que el sector esté disponible para el evento
     */
    private function verificarSectorDisponible($evento, $sectorId)
    {
        if (!$evento->sectorEstaDisponible($sectorId)) {
            throw new \Exception('El sector no está disponible para este evento');
        }
    }
}