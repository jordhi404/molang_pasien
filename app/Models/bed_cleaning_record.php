<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class bed_cleaning_record extends Model
{
    use HasFactory;

    protected $table = 'bed_cleaning_records';
    protected $connection = 'pgsql';
    protected $fillable = [
        'BedCode', 
        'ServiceUnitName', 
        'LastUnoccupiedDate', 
        'BedUnoccupiedInReality',
        'ExpectedDoneCleaning',
        'DoneCleaningInReality',
        'CleaningDuration'
    ];

    public $timestamps = false;
}
