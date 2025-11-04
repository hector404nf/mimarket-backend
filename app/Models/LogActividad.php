<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogActividad extends Model
{
    use HasFactory;

    protected $table = 'log_actividades';
    protected $primaryKey = 'id_log';

    protected $fillable = [
        'user_id',
        'accion',
        'tabla_afectada',
        'id_registro_afectado',
        'detalles',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'detalles' => 'array'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}