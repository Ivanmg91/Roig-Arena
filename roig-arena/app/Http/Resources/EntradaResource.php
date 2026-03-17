<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EntradaResource extends JsonResource
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
            'codigo_qr' => $this->codigo_qr,
            'precio_pagado' => (float) $this->precio_pagado,
            'precio_formateado' => $this->precioFormateado(),
            'valida' => $this->esValida(),
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
                'nombre_completo' => $this->asiento?->nombreCompleto(),
            ],
            'comprada_en' => optional($this->created_at)->toIso8601String(),
        ];
    }
}
