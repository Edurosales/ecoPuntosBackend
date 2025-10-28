<?php

namespace App\Models;
use App\Models\TransaccionPuntos;

use Illuminate\Database\Eloquent\Model;

class ArticuloTienda extends Model
{
    protected $primaryKey = 'id_articulo';

    protected $fillable = [
        'nombre',
        'descripcion',
        'stock',
        'imagen_url',
        'puntos_requeridos',
    ];

    /**
 * Obtiene todas las transacciones (canjes) asociadas a este artículo.
 */
public function transacciones()
{
    // Un Artículo tiene muchas Transacciones
    return $this->hasMany(TransaccionPuntos::class, 'articulo_id', 'id_articulo');
}
    // --- IGNORE ---
}