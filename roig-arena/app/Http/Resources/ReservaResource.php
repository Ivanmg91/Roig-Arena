<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'estado' => $this->estado,
            'reservado_hasta' => optional($this->reservado_hasta)->toIso8601String(),
            'tiempo_restante_min' => $this->tiempoRestante(),
            'evento' => [
                'id' => $this->evento?->id,
                'nombre' => $this->evento?->nombre,
                'fecha' => optional($this->evento?->fecha)->toDateString(),
                'hora' => $this->evento?->hora,
            ],
            'asiento' => [
                'id' => $this->asiento?->id,
                'sector' => $this->asiento?->sector?->nombre,
                'fila' => $this->asiento?->fila,
                'numero' => $this->asiento?->numero,
            ],
        ];
    }
}
