<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Comision extends Model
{
    protected $table = 'comisiones';
    protected $primaryKey = 'id_comision';

    protected $fillable = [
        'id_orden',
        'id_tienda',
        'id_plan',
        'monto_venta',
        'porcentaje_comision',
        'monto_comision',
        'estado',
        'fecha_vencimiento',
        'fecha_pago',
        'notas'
    ];

    protected $casts = [
        'monto_venta' => 'decimal:2',
        'porcentaje_comision' => 'decimal:2',
        'monto_comision' => 'decimal:2',
        'fecha_vencimiento' => 'datetime',
        'fecha_pago' => 'datetime',
    ];

    // Estados posibles de la comisión
    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_PAGADA = 'pagada';
    const ESTADO_RETENIDA = 'retenida';

    /**
     * Relación con la orden
     */
    public function orden(): BelongsTo
    {
        return $this->belongsTo(Orden::class, 'id_orden', 'id_orden');
    }

    /**
     * Relación con la tienda
     */
    public function tienda(): BelongsTo
    {
        return $this->belongsTo(Tienda::class, 'id_tienda', 'id_tienda');
    }

    /**
     * Relación con el plan de tienda
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(PlanTienda::class, 'id_plan', 'id_plan');
    }

    /**
     * Relación con las liquidaciones (muchos a muchos)
     */
    public function liquidaciones(): BelongsToMany
    {
        return $this->belongsToMany(
            Liquidacion::class,
            'comisiones_liquidaciones',
            'id_comision',
            'id_liquidacion'
        );
    }

    /**
     * Scope para comisiones pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    /**
     * Scope para comisiones pagadas
     */
    public function scopePagadas($query)
    {
        return $query->where('estado', self::ESTADO_PAGADA);
    }

    /**
     * Scope para comisiones de una tienda específica
     */
    public function scopeDeTienda($query, $idTienda)
    {
        return $query->where('id_tienda', $idTienda);
    }

    /**
     * Scope para comisiones vencidas
     */
    public function scopeVencidas($query)
    {
        return $query->where('fecha_vencimiento', '<', now())
                    ->where('estado', self::ESTADO_PENDIENTE);
    }

    /**
     * Marcar comisión como pagada
     */
    public function marcarComoPagada()
    {
        $this->update([
            'estado' => self::ESTADO_PAGADA,
            'fecha_pago' => now()
        ]);
    }

    /**
     * Calcular fecha de vencimiento basada en el plan
     */
    public function calcularFechaVencimiento()
    {
        // Por defecto, 30 días después de la creación
        $diasVencimiento = $this->plan->dias_liquidacion ?? 30;
        return $this->created_at->addDays($diasVencimiento);
    }

    /**
     * Verificar si la comisión está vencida
     */
    public function estaVencida(): bool
    {
        return $this->fecha_vencimiento && 
               $this->fecha_vencimiento->isPast() && 
               $this->estado === self::ESTADO_PENDIENTE;
    }
}
