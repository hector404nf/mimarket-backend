<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cupon extends Model
{
    use HasFactory;

    protected $table = 'cupones';
    protected $primaryKey = 'id_cupon';

    protected $fillable = [
        'codigo',
        'tipo',
        'valor',
        'monto_minimo',
        'usos_maximos',
        'usos_actuales',
        'fecha_inicio',
        'fecha_expiracion',
        'activo'
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'monto_minimo' => 'decimal:2',
        'fecha_inicio' => 'date',
        'fecha_expiracion' => 'date',
        'activo' => 'boolean'
    ];

    public function usos(): HasMany
    {
        return $this->hasMany(UsoCupon::class, 'id_cupon');
    }
}