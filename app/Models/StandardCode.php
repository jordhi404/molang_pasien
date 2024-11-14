<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StandardCode extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';
    protected $table = 'StandardCode';
    protected $primaryKey = 'StandardCodeID';
    protected $keyType = 'string';
}
