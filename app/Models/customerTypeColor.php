<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class customerTypeColor extends Model
{
    use HasFactory;

    protected $table = 'customer_type_colors';
    protected $connection = 'pgsql';
    protected $fillable = [
        'customer_type', 'color'
    ];
}
