<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class empresa extends Model
{
    use HasFactory;
    protected $table = "empresa";
    protected $primaryKey = 'idEmpresa';

    protected $fillable = [
        'empresa',
    ];
}