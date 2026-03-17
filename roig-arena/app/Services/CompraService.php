<?php

namespace App\Services;

use App\Models\Entrada;
use App\Models\EstadoAsiento;
use App\Models\Precio;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CompraService
{
    /**
     * Procesa la compra de reservas activas del usuario.
     *
     * @param array<int, int|string> $reservasIds
     * @throws \Exception
     */
    public function procesarCompra(array $reservasIds, int $userId): Collection
    {
        return DB::transaction(function () use ($reservasIds, $userId) {
            $ids = array_map('intval', $reservasIds);

            $reservas = EstadoAsiento::query()
                ->whereIn('id', $ids)
                ->where('user_id', $userId)
                ->where('estado', 'bloqueado')
                ->where('reservado_hasta', '>', now())
                ->with(['asiento.sector', 'evento'])
                ->lockForUpdate()
                ->get();

            if ($reservas->count() !== count($ids)) {
                throw new \Exception('Alguna reserva no existe, no te pertenece o ha expirado.');
            }

            $entradas = collect();

            foreach ($reservas as $reserva) {
                $precio = Precio::query()
                    ->where('evento_id', $reserva->evento_id)
                    ->where('sector_id', $reserva->asiento->sector_id)
                    ->where('disponible', true)
                    ->whereHas('sector', function ($query) {
                        $query->where('activo', true);
                    })
                    ->first();

                if (!$precio) {
                    throw new \Exception('No hay precio disponible para uno de los asientos seleccionados.');
                }

                $entrada = Entrada::create([
                    'user_id' => $userId,
                    'evento_id' => $reserva->evento_id,
                    'asiento_id' => $reserva->asiento_id,
                    'precio_pagado' => $precio->precio,
                ]);

                $reserva->marcarComoVendido();
                $entradas->push($entrada);
            }

            return Entrada::query()
                ->whereIn('id', $entradas->pluck('id'))
                ->with(['evento', 'asiento.sector'])
                ->get();
        });
    }
}
