<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanTienda extends Model
{
    protected $table = 'planes_tienda';
    protected $primaryKey = 'id_plan';

    protected $fillable = [
        'nombre',
        'nombre_display',
        'descripcion',
        'precio_mensual',
        'precio_anual',
        'comision_porcentaje',
        'limite_productos',
        'limite_imagenes_por_producto',
        'analytics_avanzado',
        'soporte_prioritario',
        'personalizacion_tienda',
        'integracion_api',
        'promociones_destacadas',
        'reportes_detallados',
        'metodos_entrega_incluidos',
        'caracteristicas_adicionales',
        'activo',
        'orden_display'
    ];

    protected $casts = [
        'precio_mensual' => 'decimal:2',
        'precio_anual' => 'decimal:2',
        'comision_porcentaje' => 'decimal:2',
        'analytics_avanzado' => 'boolean',
        'soporte_prioritario' => 'boolean',
        'personalizacion_tienda' => 'boolean',
        'integracion_api' => 'boolean',
        'promociones_destacadas' => 'boolean',
        'reportes_detallados' => 'boolean',
        'metodos_entrega_incluidos' => 'array',
        'caracteristicas_adicionales' => 'array',
        'activo' => 'boolean'
    ];

    /**
     * Relación con tiendas que tienen este plan
     */
    public function tiendas(): HasMany
    {
        return $this->hasMany(Tienda::class, 'id_plan_actual', 'id_plan');
    }

    /**
     * Relación con suscripciones de este plan
     */
    public function suscripciones(): HasMany
    {
        return $this->hasMany(SuscripcionTienda::class, 'id_plan', 'id_plan');
    }

    /**
     * Scope para obtener solo planes activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para ordenar por orden de display
     */
    public function scopeOrdenado($query)
    {
        return $query->orderBy('orden_display');
    }

    /**
     * Verifica si el plan incluye un método de entrega específico
     */
    public function incluyeMetodoEntrega(string $metodo): bool
    {
        return in_array($metodo, $this->metodos_entrega_incluidos ?? []);
    }

    /**
     * Obtiene el precio con descuento anual (si aplica)
     */
    public function getPrecioAnualConDescuentoAttribute(): float
    {
        // Si el precio anual es menor que 12 veces el mensual, hay descuento
        $precioMensualAnualizado = $this->precio_mensual * 12;
        return $this->precio_anual < $precioMensualAnualizado ? $this->precio_anual : $precioMensualAnualizado;
    }

    /**
     * Calcula el ahorro anual
     */
    public function getAhorroAnualAttribute(): float
    {
        $precioMensualAnualizado = $this->precio_mensual * 12;
        return $precioMensualAnualizado - $this->precio_anual;
    }

    /**
     * Verifica si es el plan gratuito
     */
    public function esGratuito(): bool
    {
        return $this->precio_mensual == 0 && $this->precio_anual == 0;
    }
}
