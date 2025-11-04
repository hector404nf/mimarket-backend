<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Liquidacion extends Model
{
    protected $table = 'liquidaciones';
    protected $primaryKey = 'id_liquidacion';

    protected $fillable = [
        'id_tienda',
        'numero_liquidacion',
        'monto_total',
        'cantidad_ordenes',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'fecha_procesamiento',
        'fecha_pago',
        'notas'
    ];

    protected $casts = [
        'monto_total' => 'decimal:2',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'fecha_procesamiento' => 'datetime',
        'fecha_pago' => 'datetime',
    ];

    // Estados posibles de la liquidación
    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_PROCESADA = 'procesada';
    const ESTADO_PAGADA = 'pagada';
    const ESTADO_CANCELADA = 'cancelada';

    /**
     * Relación con la tienda
     */
    public function tienda(): BelongsTo
    {
        return $this->belongsTo(Tienda::class, 'id_tienda', 'id_tienda');
    }

    /**
     * Relación con las comisiones (muchos a muchos)
     */
    public function comisiones(): BelongsToMany
    {
        return $this->belongsToMany(
            Comision::class,
            'comisiones_liquidaciones',
            'id_liquidacion',
            'id_comision'
        );
    }

    /**
     * Scope para liquidaciones pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    /**
     * Scope para liquidaciones procesadas
     */
    public function scopeProcesadas($query)
    {
        return $query->where('estado', self::ESTADO_PROCESADA);
    }

    /**
     * Scope para liquidaciones pagadas
     */
    public function scopePagadas($query)
    {
        return $query->where('estado', self::ESTADO_PAGADA);
    }

    /**
     * Scope para liquidaciones de una tienda específica
     */
    public function scopeDeTienda($query, $idTienda)
    {
        return $query->where('id_tienda', $idTienda);
    }

    /**
     * Generar número de liquidación único
     */
    public static function generarNumeroLiquidacion($idTienda): string
    {
        $fecha = now()->format('Ymd');
        $contador = self::where('id_tienda', $idTienda)
                       ->whereDate('created_at', now())
                       ->count() + 1;
        
        return "LIQ-{$idTienda}-{$fecha}-" . str_pad($contador, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Procesar liquidación
     */
    public function procesar()
    {
        $this->update([
            'estado' => self::ESTADO_PROCESADA,
            'fecha_procesamiento' => now()
        ]);

        // Marcar todas las comisiones asociadas como procesadas
        $this->comisiones()->update(['estado' => Comision::ESTADO_PAGADA]);
    }

    /**
     * Marcar como pagada
     */
    public function marcarComoPagada()
    {
        $this->update([
            'estado' => self::ESTADO_PAGADA,
            'fecha_pago' => now()
        ]);
    }

    /**
     * Cancelar liquidación
     */
    public function cancelar($motivo = null)
    {
        $this->update([
            'estado' => self::ESTADO_CANCELADA,
            'notas' => $motivo ? "Cancelada: {$motivo}" : 'Cancelada'
        ]);

        // Revertir el estado de las comisiones a pendiente
        $this->comisiones()->update(['estado' => Comision::ESTADO_PENDIENTE]);
    }

    /**
     * Calcular el monto total basado en las comisiones asociadas
     */
    public function calcularMontoTotal(): float
    {
        return $this->comisiones()->sum('monto_comision');
    }

    /**
     * Verificar si puede ser procesada
     */
    public function puedeSerProcesada(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE && 
               $this->comisiones()->count() > 0;
    }

    /**
     * Obtener resumen de la liquidación
     */
    public function getResumen(): array
    {
        return [
            'numero_liquidacion' => $this->numero_liquidacion,
            'tienda' => $this->tienda->nombre_tienda,
            'periodo' => $this->fecha_inicio->format('d/m/Y') . ' - ' . $this->fecha_fin->format('d/m/Y'),
            'monto_total' => $this->monto_total,
            'cantidad_ordenes' => $this->cantidad_ordenes,
            'cantidad_comisiones' => $this->comisiones()->count(),
            'estado' => $this->estado,
            'fecha_creacion' => $this->created_at->format('d/m/Y H:i'),
            'fecha_procesamiento' => $this->fecha_procesamiento?->format('d/m/Y H:i'),
            'fecha_pago' => $this->fecha_pago?->format('d/m/Y H:i'),
        ];
    }
}
