<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HorarioTienda extends Model
{
    protected $table = 'horarios_tienda';
    protected $primaryKey = 'id_horario';
    protected $fillable = [
        'id_tienda',
        'dia_semana',
        'hora_apertura',
        'hora_cierre',
        'cerrado',
        'notas_especiales',
    ];
}