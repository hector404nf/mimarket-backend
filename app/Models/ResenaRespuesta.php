<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResenaRespuesta extends Model
{
    use HasFactory;

    protected $table = 'resena_respuestas';
    protected $primaryKey = 'id_respuesta';

    protected $fillable = [
        'id_resena',
        'user_id',
        'respuesta',
    ];

    public function resena(): BelongsTo
    {
        return $this->belongsTo(Resena::class, 'id_resena', 'id_resena');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}