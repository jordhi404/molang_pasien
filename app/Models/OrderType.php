<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderType extends Model
{
    use HasFactory;

    protected $table = 'order_types';
    protected $connection = 'pgsql';
    protected $fillable = ['code_prefix', 'icon_path'];
}
