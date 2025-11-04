<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mensaje extends Model
{
    use HasFactory;

    protected $table = 'mensajes';
    protected $primaryKey = 'id_mensaje';

    protected $fillable = [
        'user_id_remitente',
        'user_id_destinatario',
        'asunto',
        'contenido',
        'leido'
    ];

    protected $casts = [
        'leido' => 'boolean'
    ];

    public function remitente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id_remitente');
    }

    public function destinatario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id_destinatario');
    }
}