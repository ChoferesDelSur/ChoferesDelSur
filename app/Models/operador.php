<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;

class operador extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = "operador";
    protected $primaryKey = "idOperador";

    protected $fillable = [
        'nombre',
        'apellidoP',
        'apellidoM',
        'fechaNacimiento',
        'edad',
        'CURP',
        'RFC',
        'numTelefono',
        'NSS',
        'idEstado',
        'idTipoOperador',
        'idDireccion',
        'numLicencia',
        'vigenciaLicencia',
        'numINE',
        'vigenciaINE',
        'ultimoContrato',
        'antiguedad',
        'fechaAlta',
        'fechaBaja',
        'idEmpresa',
        'constanciaSF',
        'idConvenioPago',
        'cursoSemovi',
        'nombre_completo'
    ];

    public function unidad()
{
    return $this->hasOne(unidad::class, 'idOperador');
}

public function direccion(): HasOne
    {
        return $this->hasOne(direccion::class, 'idDireccion', 'idDireccion');
    }

    public function estado()
    {
        return $this->belongsTo(estado::class, 'idEstado', 'idEstado');
    }

}