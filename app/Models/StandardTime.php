<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StandardTime extends Model
{
    use HasFactory;

    protected $table = 'standard_times';
    protected $connection = 'pgsql';
    protected $fillable = [
        'keterangan', 'standard_time'
    ];

    public $timestamps = false;
}
