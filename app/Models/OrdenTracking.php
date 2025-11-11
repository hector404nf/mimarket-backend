<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdenTracking extends Model
{
    use HasFactory;

    protected $table = 'orden_trackings';
    protected $primaryKey = 'id_tracking';

    protected $fillable = [
        'id_orden',
        'latitud',
        'longitud',
        'precision',
        'velocidad',
        'heading',
        'fuente',
        'tracking_activo',
    ];

    protected $casts = [
        'latitud' => 'float',
        'longitud' => 'float',
        'precision' => 'float',
        'velocidad' => 'float',
        'heading' => 'integer',
        'tracking_activo' => 'boolean',
    ];

    public function orden(): BelongsTo
    {
        return $this->belongsTo(Orden::class, 'id_orden');
    }
}