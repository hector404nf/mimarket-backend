<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdenEnvio extends Model
{
    use HasFactory;

    protected $table = 'orden_envios';
    protected $primaryKey = 'id_orden_envio';

    protected $fillable = [
        'id_orden',
        'id_direccion_envio',
        'nombre_completo',
        'direccion',
        'ciudad',
        'estado',
        'codigo_postal',
        'pais',
        'telefono',
        'latitud',
        'longitud',
    ];

    protected $casts = [
        'latitud' => 'float',
        'longitud' => 'float',
    ];

    public function orden(): BelongsTo
    {
        return $this->belongsTo(Orden::class, 'id_orden');
    }

    public function direccionUsuario(): BelongsTo
    {
        return $this->belongsTo(DireccionEnvio::class, 'id_direccion_envio', 'id_direccion');
    }
}