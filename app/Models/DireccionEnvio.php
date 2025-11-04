<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DireccionEnvio extends Model
{
    use HasFactory;

    protected $table = 'direcciones_envio';
    protected $primaryKey = 'id_direccion';

    protected $fillable = [
        'user_id',
        'nombre_completo',
        'direccion',
        'ciudad',
        'estado',
        'codigo_postal',
        'pais',
        'telefono',
        'predeterminada'
    ];

    protected $casts = [
        'predeterminada' => 'boolean'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}