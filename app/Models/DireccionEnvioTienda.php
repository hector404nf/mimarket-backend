<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DireccionEnvioTienda extends Model
{
    protected $table = 'direcciones_envio_tienda';
    protected $primaryKey = 'id_direccion_envio';
    protected $fillable = [
        'id_tienda',
        'nombre',
        'precio_envio',
        'minutos_entrega',
        'latitud',
        'longitud',
        'direccion_completa',
        'zona_cobertura',
        'activo',
    ];
}