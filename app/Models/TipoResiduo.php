<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoResiduo extends Model
{
    protected $table = 'tipos_residuo';
    protected $primaryKey = 'id_tipo';

    protected $fillable = [
        'nombre',
        'descripcion',
        'puntos_por_kg',
        'color_hex',
        'activo',
    ];

    protected $casts = [
        'puntos_por_kg' => 'decimal:2',
        'activo' => 'boolean',
    ];

    /**
     * Relación con residuos
     */
    public function residuos()
    {
        return $this->hasMany(Residuo::class, 'tipo_residuo', 'nombre');
    }
}
