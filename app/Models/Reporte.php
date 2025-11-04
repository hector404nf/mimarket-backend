<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reporte extends Model
{
    use HasFactory;

    protected $table = 'reportes';
    protected $primaryKey = 'id_reporte';

    protected $fillable = [
        'user_id_reporta',
        'tipo_contenido',
        'id_contenido',
        'motivo',
        'descripcion',
        'estado'
    ];

    public function userReporta(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id_reporta');
    }
}