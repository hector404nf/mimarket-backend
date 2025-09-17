<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    protected $table = 'categorias';
    protected $primaryKey = 'id_categoria';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'slug',
        'description',
        'icono',
        'activo',
        'orden'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer',
        'fecha_creacion' => 'datetime'
    ];

    // Relaciones
    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'id_categoria', 'id_categoria');
    }
}
