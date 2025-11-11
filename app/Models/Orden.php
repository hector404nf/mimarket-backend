<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Orden extends Model
{
    use HasFactory;

    protected $table = 'ordenes';
    protected $primaryKey = 'id_orden';

    protected $fillable = [
        'user_id',
        'numero_orden',
        'total',
        'subtotal',
        'impuestos',
        'costo_envio',
        'direccion_envio',
        'id_metodo_pago',
        'comision_total',
        'comisiones_calculadas',
        'fecha_calculo_comisiones',
        'estado',
        'metodo_pago',
        'estado_pago',
        'notas'
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'impuestos' => 'decimal:2',
        'costo_envio' => 'decimal:2',
        'comision_total' => 'decimal:2',
        'comisiones_calculadas' => 'boolean',
        'fecha_calculo_comisiones' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleOrden::class, 'id_orden');
    }

    public function usoCupones(): HasMany
    {
        return $this->hasMany(UsoCupon::class, 'id_orden');
    }

    public function comisiones(): HasMany
    {
        return $this->hasMany(Comision::class, 'id_orden');
    }

    public function envio(): HasOne
    {
        return $this->hasOne(OrdenEnvio::class, 'id_orden');
    }
}