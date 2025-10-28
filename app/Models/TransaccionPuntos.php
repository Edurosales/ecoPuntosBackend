<?php

namespace App\Models;

use App\Models\User;
use App\Models\PuntoAcopio;
use App\Models\ArticuloTienda;

use Illuminate\Database\Eloquent\Model;

class TransaccionPuntos extends Model
{
    protected $table = 'transaccion_puntos';
    protected $primaryKey = 'id_transaccion';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'tipo',
        'tipo_residuo',
        'cantidad_kg',
        'puntos',
        'status',
        'codigo_reclamacion',
        'user_id_recolector',
        'punto_acopio_id',
        'user_id_cliente',
        'articulo_id',
    ];

    /**
 * Obtiene el RECOLECTOR (Usuario) que registró esta transacción.
 */
public function recolector()
{
    return $this->belongsTo(User::class, 'user_id_recolector', 'id_usuario');
}

/**
 * Obtiene el CLIENTE (Usuario) que hizo la transacción.
 */
public function usuario()
{
    return $this->belongsTo(User::class, 'user_id_cliente', 'id_usuario');
}


/**
 * Obtiene el Punto de Acopio donde ocurrió esta transacción.
 */
public function puntoAcopio()
{
    return $this->belongsTo(PuntoAcopio::class, 'punto_acopio_id', 'id_acopio');
}

/**
 * Obtiene el Artículo (de la tienda) que se canjeó en esta transacción.
 */
public function articuloTienda()
{
    return $this->belongsTo(ArticuloTienda::class, 'articulo_id', 'id_articulo');
}

/**
 * Alias para articuloTienda (para compatibilidad con frontend)
 */
public function articulo()
{
    return $this->articuloTienda();
}
    // --- IGNORE ---
}
