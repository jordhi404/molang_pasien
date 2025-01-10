<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class service_unit extends Model
{
    use HasFactory;

    protected $table = 'service_units';
    protected $connection = 'pgsql';
    protected $fillable = [
        'unit_code', 'unit_service_name'
    ];
}
