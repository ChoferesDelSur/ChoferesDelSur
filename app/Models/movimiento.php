<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class movimiento extends Model
{
    use HasFactory;
    use softDeletes;

    protected $table = "movimiento";
    protected $primaryKey = "idMovimiento";

    protected $fillable = [
        'fechaMovimiento',
        'idEstado',
        'idTipoMovimiento',
        'idDirectivo',
        'idOperador',
    ];
}