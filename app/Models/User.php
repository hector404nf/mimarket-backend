<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'apellido',
        'telefono',
        'activo',
        'foto_perfil',
        'onboarded',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
            'onboarded' => 'boolean',
        ];
    }

    // Relaciones
    public function tiendas()
    {
        return $this->hasMany(Tienda::class, 'user_id');
    }

    public function productos()
    {
        return $this->hasMany(Producto::class, 'user_id');
    }

    public function perfil()
    {
        return $this->hasOne(Perfil::class, 'user_id');
    }

    public function carrito()
    {
        return $this->hasMany(Carrito::class, 'user_id');
    }

    public function ordenes()
    {
        return $this->hasMany(Orden::class, 'user_id');
    }

    public function direccionesEnvio()
    {
        return $this->hasMany(DireccionEnvio::class, 'user_id');
    }

    public function resenas()
    {
        return $this->hasMany(Resena::class, 'user_id');
    }

    public function favoritos()
    {
        return $this->hasMany(Favorito::class, 'user_id');
    }

    public function notificaciones()
    {
        return $this->hasMany(Notificacion::class, 'user_id');
    }

    public function sesiones(): HasMany
    {
        return $this->hasMany(Sesion::class, 'user_id');
    }

    public function busquedas(): HasMany
    {
        return $this->hasMany(Busqueda::class, 'user_id');
    }

    public function mensajesEnviados(): HasMany
    {
        return $this->hasMany(Mensaje::class, 'user_id_remitente');
    }

    public function mensajesRecibidos(): HasMany
    {
        return $this->hasMany(Mensaje::class, 'user_id_destinatario');
    }

    public function reportes(): HasMany
    {
        return $this->hasMany(Reporte::class, 'user_id_reporta');
    }

    public function usoCupones(): HasMany
    {
        return $this->hasMany(UsoCupon::class, 'user_id');
    }

    public function logActividades(): HasMany
    {
        return $this->hasMany(LogActividad::class, 'user_id');
    }
}
