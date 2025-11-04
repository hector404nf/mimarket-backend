<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class SuscripcionTienda extends Model
{
    protected $table = 'suscripciones_tienda';
    protected $primaryKey = 'id_suscripcion';

    protected $fillable = [
        'id_tienda',
        'id_plan',
        'tipo_facturacion',
        'precio_pagado',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'renovacion_automatica',
        'metodo_pago',
        'referencia_pago',
        'configuracion_personalizada',
        'fecha_cancelacion',
        'motivo_cancelacion',
        'proximo_cobro',
        'intentos_cobro_fallidos'
    ];

    protected $casts = [
        'precio_pagado' => 'decimal:2',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'renovacion_automatica' => 'boolean',
        'configuracion_personalizada' => 'array',
        'fecha_cancelacion' => 'datetime',
        'proximo_cobro' => 'datetime'
    ];

    /**
     * Relación con la tienda
     */
    public function tienda(): BelongsTo
    {
        return $this->belongsTo(Tienda::class, 'id_tienda', 'id_tienda');
    }

    /**
     * Relación con el plan
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(PlanTienda::class, 'id_plan', 'id_plan');
    }

    /**
     * Scope para suscripciones activas
     */
    public function scopeActivas($query)
    {
        return $query->where('estado', 'activa');
    }

    /**
     * Scope para suscripciones que vencen pronto
     */
    public function scopeVencenProximamente($query, int $dias = 7)
    {
        return $query->where('estado', 'activa')
                    ->where('fecha_fin', '<=', Carbon::now()->addDays($dias))
                    ->where('fecha_fin', '>=', Carbon::now());
    }

    /**
     * Scope para suscripciones vencidas
     */
    public function scopeVencidas($query)
    {
        return $query->where('fecha_fin', '<', Carbon::now())
                    ->whereIn('estado', ['activa', 'vencida']);
    }

    /**
     * Scope para suscripciones que necesitan renovación
     */
    public function scopeParaRenovar($query)
    {
        return $query->where('renovacion_automatica', true)
                    ->where('proximo_cobro', '<=', Carbon::now())
                    ->where('estado', 'activa');
    }

    /**
     * Verifica si la suscripción está activa
     */
    public function estaActiva(): bool
    {
        return $this->estado === 'activa' && 
               $this->fecha_fin >= Carbon::now()->toDateString();
    }

    /**
     * Verifica si la suscripción está vencida
     */
    public function estaVencida(): bool
    {
        return $this->fecha_fin < Carbon::now()->toDateString();
    }

    /**
     * Calcula los días restantes de la suscripción
     */
    public function diasRestantes(): int
    {
        if ($this->estaVencida()) {
            return 0;
        }
        
        return Carbon::now()->diffInDays(Carbon::parse($this->fecha_fin));
    }

    /**
     * Marca la suscripción como cancelada
     */
    public function cancelar(string $motivo = null): bool
    {
        $this->estado = 'cancelada';
        $this->fecha_cancelacion = Carbon::now();
        $this->motivo_cancelacion = $motivo;
        $this->renovacion_automatica = false;
        
        return $this->save();
    }

    /**
     * Suspende la suscripción
     */
    public function suspender(): bool
    {
        $this->estado = 'suspendida';
        return $this->save();
    }

    /**
     * Reactiva la suscripción
     */
    public function reactivar(): bool
    {
        if ($this->estaVencida()) {
            return false; // No se puede reactivar una suscripción vencida
        }
        
        $this->estado = 'activa';
        return $this->save();
    }

    /**
     * Renueva la suscripción
     */
    public function renovar(float $nuevoPrecio = null): bool
    {
        $precio = $nuevoPrecio ?? ($this->tipo_facturacion === 'anual' ? 
                                  $this->plan->precio_anual : 
                                  $this->plan->precio_mensual);
        
        $mesesARenovar = $this->tipo_facturacion === 'anual' ? 12 : 1;
        
        $this->fecha_inicio = Carbon::now();
        $this->fecha_fin = Carbon::now()->addMonths($mesesARenovar);
        $this->precio_pagado = $precio;
        $this->estado = 'activa';
        $this->proximo_cobro = $this->tipo_facturacion === 'anual' ? 
                              Carbon::now()->addYear() : 
                              Carbon::now()->addMonth();
        $this->intentos_cobro_fallidos = 0;
        
        return $this->save();
    }
}
