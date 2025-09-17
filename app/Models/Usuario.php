<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Usuario extends Model
{
    protected $table = 'usuarios';
    protected $primaryKey = 'id_usuario';
    public $timestamps = false;

    protected $fillable = [
        'email',
        'password',
        'nombre',
        'apellido',
        'telefono',
        'activo',
        'foto_perfil'
    ];

    protected $hidden = [
        'password'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'fecha_registro' => 'datetime',
        'ultimo_acceso' => 'datetime',
        'fecha_actualizacion' => 'datetime'
    ];

    // Relaciones
    public function tiendas(): HasMany
    {
        return $this->hasMany(Tienda::class, 'id_usuario', 'id_usuario');
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'id_usuario', 'id_usuario');
    }
}
