<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetodoPago extends Model
{
    use HasFactory;

    protected $table = 'metodos_pago';
    protected $primaryKey = 'id_metodo';

    protected $fillable = [
        'user_id',
        'tipo',           // tarjeta | efectivo | transferencia
        'marca',          // Visa, Mastercard, etc. (para tarjeta)
        'terminacion',    // últimos 4 dígitos
        'nombre_titular', // nombre del titular (para tarjeta/transferencia)
        'mes_venc',
        'anio_venc',
        'banco',          // para transferencia
        'predeterminada',
        'activo',
        'metadata',       // información auxiliar no sensible
    ];

    protected $casts = [
        'predeterminada' => 'boolean',
        'activo' => 'boolean',
        'mes_venc' => 'integer',
        'anio_venc' => 'integer',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}