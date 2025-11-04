<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Busqueda extends Model
{
    use HasFactory;

    protected $table = 'busquedas';
    protected $primaryKey = 'id_busqueda';

    protected $fillable = [
        'user_id',
        'termino_busqueda',
        'resultados_encontrados',
        'filtros_aplicados',
        'ip_address'
    ];

    protected $casts = [
        'filtros_aplicados' => 'array'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}