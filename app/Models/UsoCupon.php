<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsoCupon extends Model
{
    use HasFactory;

    protected $table = 'uso_cupones';
    protected $primaryKey = 'id_uso';

    protected $fillable = [
        'id_cupon',
        'user_id',
        'id_orden',
        'descuento_aplicado'
    ];

    protected $casts = [
        'descuento_aplicado' => 'decimal:2'
    ];

    public function cupon(): BelongsTo
    {
        return $this->belongsTo(Cupon::class, 'id_cupon');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orden(): BelongsTo
    {
        return $this->belongsTo(Orden::class, 'id_orden');
    }
}