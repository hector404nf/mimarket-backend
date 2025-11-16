<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TiendaMetodoPago extends Model
{
    protected $table = 'tienda_metodos_pago';
    protected $primaryKey = 'id_tienda_metodo';
    protected $fillable = [
        'id_tienda',
        'metodo',
        'activo',
        'configuracion_especial',
        'comision_tienda',
    ];
}