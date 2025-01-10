<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class status_mapping extends Model
{
    use HasFactory;

    protected $table = 'status_mappings';
    protected $connection = 'pgsql';
    protected $fillable = [
        'keterangan', 'status_value'
    ];
}
