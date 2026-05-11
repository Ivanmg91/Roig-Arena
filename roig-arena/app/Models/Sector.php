<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sector extends Model
{
    use HasFactory;

    /**
     * Campos que se pueden asignar masivamente
     */
    protected $table = 'sectores';

    protected $fillable = [
        'nombre',
        'descripcion',
        'cantidad_filas',
        'cantidad_columnas',
        'color_hex',
        'activo',
        'fila_inicio',
        'fila_fin',
        'columna_inicio',
        'columna_fin',
        'posicion_x',
        'posicion_y',
        'orden_visual',
    ];

    /**
     * Casteo de tipos
     */
    protected $casts = [
        'activo' => 'boolean',
        'posicion_x' => 'decimal:2',
        'posicion_y' => 'decimal:2',
    ];

    // ============================================
    // RELACIONES
    // ============================================

    /**
     * Un sector tiene muchos asientos
     */
    public function asientos()
    {
        return $this->hasMany(Asiento::class);
    }

    /**
     * Un sector tiene muchos precios (uno por evento)
     */
    public function precios()
    {
        return $this->hasMany(Precio::class);
    }

    /**
     * Un sector está disponible en muchos eventos (a través de precios)
     */
    public function eventos()
    {
        return $this->belongsToMany(Evento::class, 'precios')
                    ->withPivot('precio', 'disponible')
                    ->withTimestamps();
    }

    // ============================================
    // MÉTODOS ÚTILES
    // ============================================

    // Método para obtener asientos del sector organizados
    public function obtenerAsientosOrganizados() {
        return $this->asientos()
            ->orderBy('numero_fila')
            ->orderBy('numero_asiento')
            ->get()
            ->groupBy('numero_fila')
            ->toArray();
    }

    public function contarDisponibles($eventoId): int
    {
        return $this->asientos()
            ->whereDoesntHave('estadoAsientos', function ($query) use ($eventoId) {
                $query->where('evento_id', $eventoId);
            })
            ->count();
    }

    public function contarReservados($eventoId): int
    {
        return $this->asientos()
            ->whereHas('estadoAsientos', function ($query) use ($eventoId) {
                $query->where('evento_id', $eventoId)
                      ->where('estado', 'reservado');
            })
            ->count();
    }

    public function contarOcupados($eventoId): int
    {
        return $this->asientos()
            ->whereHas('estadoAsientos', function ($query) use ($eventoId) {
                $query->where('evento_id', $eventoId)
                      ->where('estado', 'ocupado');
            })
            ->count();
    }

    /**
     * Verificar si el sector está activo globalmente
     */
    public function estaActivo(): bool
    {
        return $this->activo;
    }

    /**
     * Obtener el número total de asientos del sector
     */
    public function totalAsientos(): int
    {
        return $this->asientos()->count();
    }

    /**
     * Obtener asientos disponibles para un evento específico
     */
    public function asientosDisponiblesParaEvento($eventoId)
    {
        return $this->asientos()
            ->whereDoesntHave('estadoAsientos', function ($query) use ($eventoId) {
                $query->where('evento_id', $eventoId);
            })
            ->get();
    }

    /**
     * Scope: Solo sectores activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Normaliza dos coordenadas de asientos y devuelve los límites del rectángulo.
     *
     * Formato esperado de entrada:
     * ['fila' => 1, 'columna' => 3]
     */
    public static function normalizarRectanguloDesdeCoordenadas(array $inicio, array $fin): array // Para guardar datos en la tabla de sectores
    {
        // Validamos que ambas coordenadas traen los dos datos mínimos: fila y columna.
        if (!isset($inicio['fila'], $inicio['columna'], $fin['fila'], $fin['columna'])) {
            throw new \InvalidArgumentException('Las coordenadas deben incluir fila y columna.');
        }

        // Normalizamos filas y columnas como enteros para calcular límites y dimensiones.
        $filaInicio = (int) $inicio['fila'];
        $filaFin = (int) $fin['fila'];
        $columnaInicio = (int) $inicio['columna'];
        $columnaFin = (int) $fin['columna'];

        // Filas y columnas menores de 1 no tienen sentido en una rejilla de asientos.
        if ($filaInicio < 1 || $filaFin < 1 || $columnaInicio < 1 || $columnaFin < 1) {
            throw new \InvalidArgumentException('Filas y columnas deben ser mayores o iguales a 1.');
        }

        // Aunque el usuario seleccione "al revés", aquí ordenamos siempre de menor a mayor.
        $filaMin = min($filaInicio, $filaFin);
        $filaMax = max($filaInicio, $filaFin);
        $columnaMin = min($columnaInicio, $columnaFin);
        $columnaMax = max($columnaInicio, $columnaFin);

        // Devolvemos los límites listos para guardar, más dimensiones calculadas.
        return [
            'fila_inicio' => $filaMin,
            'fila_fin' => $filaMax,
            'columna_inicio' => $columnaMin,
            'columna_fin' => $columnaMax,
            // +1 porque el rango es inclusivo: de 1 a 1 hay 1 fila, no 0.
            'cantidad_filas' => ($filaMax - $filaMin) + 1,
            'cantidad_columnas' => ($columnaMax - $columnaMin) + 1,
            'total_asientos' => (($filaMax - $filaMin) + 1) * (($columnaMax - $columnaMin) + 1),
        ];
    }

    /**
     * Calcula y devuelve las dimensiones del sector a partir de los
     * límites almacenados en este modelo (`fila_inicio`, `fila_fin`,
     * `columna_inicio`, `columna_fin`).
     *
     * Propósito:
     * - Centralizar y normalizar el cálculo de filas/columnas para evitar
     *   duplicar la lógica en controladores o vistas.
     * - Asegurar que los límites siempre se traten como rangos inclusivos
     *   (por eso se usa +1 al calcular la cantidad de filas/columnas).
     *
     * Ejemplo de uso:
     * - `$sector->cantidad_filas` devuelve el número de filas del bloque.
     * - `$sector->computeDimensions()` devuelve también los límites ordenados
     *   y el total de asientos.
     *
     * Notas importantes:
     * - Si alguno de los límites es `null`, se devuelve 0 en las cantidades
     *   y `null` en los límites; esto permite manejar sectores parcialmente
     *   definidos sin lanzar errores.
     * - El método ordena automáticamente los límites por si el usuario
     *   indicó primero la esquina inferior o superior (min/max).
     *
     * @return array{
     *   fila_inicio: ?int,
     *   fila_fin: ?int,
     *   columna_inicio: ?int,
     *   columna_fin: ?int,
     *   cantidad_filas: int,
     *   cantidad_columnas: int,
     *   total_asientos: int
     * }
     */
    public function computeDimensions(): array // Para calcular con lo que tienes guardado en la tabla de sectores, sin necesidad de pasar coordenadas
    {
        // Leemos los campos guardados en el modelo.
        $fi = $this->fila_inicio;
        $ff = $this->fila_fin;
        $ci = $this->columna_inicio;
        $cf = $this->columna_fin;

        // Si falta algún límite, devolvemos un resultado neutro en lugar de
        // lanzar una excepción: así otras partes del código pueden comprobar
        // la existencia de datos antes de guardar o renderizar.
        if (is_null($fi) || is_null($ff) || is_null($ci) || is_null($cf)) {
            return [
                'fila_inicio'       => null,
                'fila_fin'          => null,
                'columna_inicio'    => null,
                'columna_fin'       => null,
                'cantidad_filas'    => 0,
                'cantidad_columnas' => 0,
                'total_asientos'    => 0,
            ];
        }

        // Forzamos enteros y normalizamos orden (min/max) por seguridad.
        $filaInicio = min((int)$fi, (int)$ff);
        $filaFin    = max((int)$fi, (int)$ff);
        $colInicio  = min((int)$ci, (int)$cf);
        $colFin     = max((int)$ci, (int)$cf);

        // Conteo inclusivo: si inicio==fin entonces hay 1 fila (no 0).
        $cantidadFilas    = max(0, ($filaFin - $filaInicio) + 1);
        $cantidadColumnas = max(0, ($colFin - $colInicio) + 1);

        return [
            // Límites ya ordenados, listos para persistir o serializar.
            'fila_inicio'       => $filaInicio,
            'fila_fin'          => $filaFin,
            'columna_inicio'    => $colInicio,
            'columna_fin'       => $colFin,
            // Dimensiones calculadas a partir de los límites.
            'cantidad_filas'    => $cantidadFilas,
            'cantidad_columnas' => $cantidadColumnas,
            'total_asientos'    => $cantidadFilas * $cantidadColumnas,
        ];
    }

    /**
     * Accessor: devuelve `cantidad_filas` calculada.
     * Uso: `$sector->cantidad_filas`.
     */
    public function getCantidadFilasAttribute(): int
    {
        return $this->computeDimensions()['cantidad_filas'] ?? 0;
    }

    /**
     * Accessor: devuelve `cantidad_columnas` calculada.
     * Uso: `$sector->cantidad_columnas`.
     */
    public function getCantidadColumnasAttribute(): int
    {
        return $this->computeDimensions()['cantidad_columnas'] ?? 0;
    }

    /**
     * Accessor: devuelve el total de asientos del rectángulo.
     * Uso: `$sector->total_asientos`.
     */
    public function getTotalAsientosAttribute(): int
    {
        return $this->computeDimensions()['total_asientos'] ?? 0;
    }
}
