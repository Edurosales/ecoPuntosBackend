<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Residuo extends Model
{
    protected $table = 'residuos';
    protected $primaryKey = 'id_residuo';

    protected $fillable = [
        'tipo_residuo',
        'cantidad_kg',
        'puntos_otorgados',
        'fecha_registro',
        'user_id_recolector',
        'punto_acopio_id',
        'codigo_qr',
        'estado',
        'user_id_cliente'
    ];

    protected $casts = [
        'cantidad_kg' => 'decimal:2',
        'fecha_registro' => 'datetime',
    ];

    // Relación con el recolector que registró el residuo
    public function recolector()
    {
        return $this->belongsTo(User::class, 'user_id_recolector', 'id_usuario');
    }

    // Relación con el cliente que reclamó los puntos
    public function cliente()
    {
        return $this->belongsTo(User::class, 'user_id_cliente', 'id_usuario');
    }

    // Relación con el punto de acopio
    public function puntoAcopio()
    {
        return $this->belongsTo(PuntoAcopio::class, 'punto_acopio_id', 'id_acopio');
    }
}
