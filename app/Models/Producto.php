<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Producto extends Model
{
    protected $table = 'productos';
    protected $primaryKey = 'id_producto';
    public $timestamps = false;

    protected $fillable = [
        'id_tienda',
        'id_usuario',
        'id_categoria',
        'nombre',
        'descripcion',
        'precio',
        'precio_oferta',
        'stock',
        'sku',
        'peso',
        'dimensiones',
        'imagen_principal',
        'imagenes_adicionales',
        'activo',
        'destacado',
        'calificacion_promedio',
        'total_ventas'
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'precio_oferta' => 'decimal:2',
        'stock' => 'integer',
        'peso' => 'decimal:2',
        'activo' => 'boolean',
        'destacado' => 'boolean',
        'calificacion_promedio' => 'decimal:2',
        'total_ventas' => 'integer',
        'imagenes_adicionales' => 'array',
        'fecha_creacion' => 'datetime',
        'fecha_actualizacion' => 'datetime'
    ];

    // Relaciones
    public function tienda(): BelongsTo
    {
        return $this->belongsTo(Tienda::class, 'id_tienda', 'id_tienda');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'id_categoria', 'id_categoria');
    }
}
