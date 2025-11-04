<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Cviebrock\EloquentSluggable\Sluggable;

class Tienda extends Model
{
    use HasFactory, Sluggable;

    protected $table = 'tiendas';
    protected $primaryKey = 'id_tienda';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'nombre_tienda',
        'slug',
        'descripcion',
        'categoria_principal',
        'logo',
        'banner',
        'direccion',
        'telefono_contacto',
        'email_contacto',
        'sitio_web',
        'latitud',
        'longitud',
        'calificacion_promedio',
        'total_productos',
        'verificada',
        'id_plan_actual',
        'estado_suscripcion',
        'fecha_vencimiento_plan',
        'notificacion_vencimiento_enviada',
        'dias_gracia_restantes',
        'configuracion_plan',
        'fecha_ultima_facturacion',
        'ingresos_mes_actual',
        'comisiones_pendientes'
    ];

    protected $casts = [
        'latitud' => 'decimal:8',
        'longitud' => 'decimal:8',
        'calificacion_promedio' => 'decimal:2',
        'total_productos' => 'integer',
        'verificada' => 'boolean',
        'fecha_creacion' => 'datetime',
        'fecha_actualizacion' => 'datetime',
        'fecha_vencimiento_plan' => 'date',
        'notificacion_vencimiento_enviada' => 'boolean',
        'configuracion_plan' => 'array',
        'fecha_ultima_facturacion' => 'date',
        'ingresos_mes_actual' => 'decimal:2',
        'comisiones_pendientes' => 'decimal:2'
    ];

    /**
     * Return the sluggable configuration array for this model.
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'nombre_tienda',
                'unique' => true,
                'separator' => '-',
                'includeTrashed' => false,
            ]
        ];
    }

    // Relaciones
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'id_tienda', 'id_tienda');
    }

    /**
     * Relación con el plan actual de la tienda
     */
    public function planActual(): BelongsTo
    {
        return $this->belongsTo(PlanTienda::class, 'id_plan_actual', 'id_plan');
    }

    /**
     * Relación con todas las suscripciones de la tienda
     */
    public function suscripciones(): HasMany
    {
        return $this->hasMany(SuscripcionTienda::class, 'id_tienda', 'id_tienda');
    }

    /**
     * Relación con la suscripción activa actual
     */
    public function suscripcionActiva(): HasMany
    {
        return $this->hasMany(SuscripcionTienda::class, 'id_tienda', 'id_tienda')
                    ->where('estado', 'activa')
                    ->latest('fecha_inicio');
    }

    /**
     * Scope para tiendas con suscripción activa
     */
    public function scopeConSuscripcionActiva($query)
    {
        return $query->where('estado_suscripcion', 'activa');
    }

    /**
     * Scope para tiendas con suscripción vencida
     */
    public function scopeConSuscripcionVencida($query)
    {
        return $query->where('estado_suscripcion', 'vencida');
    }

    /**
     * Scope para tiendas en período de gracia
     */
    public function scopeEnPeriodoGracia($query)
    {
        return $query->where('estado_suscripcion', 'gracia')
                    ->where('dias_gracia_restantes', '>', 0);
    }

    /**
     * Verifica si la tienda tiene una suscripción activa
     */
    public function tieneSuscripcionActiva(): bool
    {
        return $this->estado_suscripcion === 'activa' && 
               $this->fecha_vencimiento_plan >= now()->toDateString();
    }

    /**
     * Verifica si la tienda está en período de gracia
     */
    public function estaEnPeriodoGracia(): bool
    {
        return $this->estado_suscripcion === 'gracia' && 
               $this->dias_gracia_restantes > 0;
    }

    /**
     * Obtiene el porcentaje de comisión actual de la tienda
     */
    public function getPorcentajeComisionAttribute(): float
    {
        return $this->planActual ? $this->planActual->comision_porcentaje : 15.00; // 15% por defecto
    }

    /**
     * Calcula la comisión para un monto dado
     */
    public function calcularComision(float $monto): float
    {
        return $monto * ($this->porcentaje_comision / 100);
    }

    /**
     * Verifica si la tienda puede usar una característica específica
     */
    public function puedeUsar(string $caracteristica): bool
    {
        if (!$this->planActual) {
            return false; // Sin plan no puede usar características premium
        }

        return $this->planActual->{$caracteristica} ?? false;
    }

    /**
     * Obtiene los métodos de entrega disponibles para la tienda
     */
    public function getMetodosEntregaDisponiblesAttribute(): array
    {
        if (!$this->planActual) {
            return ['recogida']; // Solo recogida en tienda por defecto
        }

        return $this->planActual->metodos_entrega_incluidos ?? ['recogida'];
    }

    /**
     * Verificar si la tienda ha alcanzado el límite de productos según su plan
     */
    public function haAlcanzadoLimiteProductos(): bool
    {
        if (!$this->planActual || $this->planActual->limite_productos === null) {
            return false; // Sin límite
        }

        return $this->total_productos >= $this->planActual->limite_productos;
    }

    /**
     * Relación con las comisiones de la tienda
     */
    public function comisiones(): HasMany
    {
        return $this->hasMany(Comision::class, 'id_tienda', 'id_tienda');
    }

    /**
     * Relación con las liquidaciones de la tienda
     */
    public function liquidaciones(): HasMany
    {
        return $this->hasMany(Liquidacion::class, 'id_tienda', 'id_tienda');
    }

    /**
     * Obtener comisiones pendientes de la tienda
     */
    public function comisionesPendientes(): HasMany
    {
        return $this->hasMany(Comision::class, 'id_tienda', 'id_tienda')
                    ->where('estado', Comision::ESTADO_PENDIENTE);
    }
}
