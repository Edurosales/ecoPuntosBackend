<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class PuntoAcopio extends Model
{
    protected $primaryKey = 'id_acopio';

    protected $fillable = [
        'nombre_lugar',
        'direccion',
        'departamento',
        'provincia',
        'distrito',
        'referencia',
        'ubicacion_gps',
        'estado',
        'user_id_recolector',
    ];

    public function recolector()
{
    // Esta es la inversa de "hasOne".
    // Le indicamos el modelo al que pertenece (User).
    // Luego, la llave foránea ('user_id_recolector') en ESTA tabla.
    // Y finalmente, la llave local ('id_usuario') en la OTRA tabla (User).
    return $this->belongsTo(User::class, 'user_id_recolector', 'id_usuario');
}
}
