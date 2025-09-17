<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tienda extends Model
{
    protected $table = 'tiendas';
    protected $primaryKey = 'id_tienda';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'nombre_tienda',
        'descripcion',
        'logo',
        'banner',
        'direccion',
        'telefono_contacto',
        'email_contacto',
        'calificacion_promedio',
        'total_productos',
        'verificada'
    ];

    protected $casts = [
        'calificacion_promedio' => 'decimal:2',
        'total_productos' => 'integer',
        'verificada' => 'boolean',
        'fecha_creacion' => 'datetime',
        'fecha_actualizacion' => 'datetime'
    ];

    // Relaciones
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'id_tienda', 'id_tienda');
    }
}
