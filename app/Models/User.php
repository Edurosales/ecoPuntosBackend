<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\PuntoAcopio;
use App\Models\TransaccionPuntos;
use App\Models\Residuo;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    protected $primaryKey = 'id_usuario';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nombre',
        'apellido',
        'dni',
        'email',
        'password',
        'puntos',
        'preferencia_tema',
        'rol',
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
        ];
    }

    // ==================== RELACIONES ====================
    
    /**
     * Relación con el punto de acopio (si es recolector) - hasOne
     */
    public function puntoAcopio()
    {
        return $this->hasOne(PuntoAcopio::class, 'user_id_recolector', 'id_usuario');
    }

    /**
     * Relación con todos los puntos de acopio (si tiene múltiples) - hasMany
     */
    public function puntosAcopio()
    {
        return $this->hasMany(PuntoAcopio::class, 'user_id_recolector', 'id_usuario');
    }

    /**
     * Transacciones como cliente (puntos ganados y canjeados)
     */
    public function transaccionesPuntos()
    {
        return $this->hasMany(TransaccionPuntos::class, 'user_id_cliente', 'id_usuario');
    }

    /**
     * Transacciones generadas como recolector
     */
    public function transaccionesComoRecolector()
    {
        return $this->hasMany(TransaccionPuntos::class, 'user_id_recolector', 'id_usuario');
    }

    /**
     * Residuos registrados como recolector
     */
    public function residuosRegistrados()
    {
        return $this->hasMany(Residuo::class, 'user_id_recolector', 'id_usuario');
    }

    /**
     * Residuos reclamados como cliente
     */
    public function residuosReclamados()
    {
        return $this->hasMany(Residuo::class, 'user_id_cliente', 'id_usuario');
    }
}
