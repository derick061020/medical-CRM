<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cita extends Model
{
    protected $fillable = [
        'user_id',
        'titulo',
        'descripcion',
        'fecha_hora',
        'confirmada'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFechaHoraAttribute($value)
    {
        return $this->attributes['fecha_hora'] = $value ? $value->format('Y-m-d H:i:s') : null;
    }
}
