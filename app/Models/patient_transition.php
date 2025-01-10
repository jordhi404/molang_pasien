<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class patient_transition extends Model
{
    use HasFactory;

    protected $table = 'patient_transitions';
    protected $connection = 'pgsql';
    protected $fillable = [
        'MedicalNo', 
        'PatientName', 
        'ServiceUnitName', 
        'RencanaPulang',
        'Keperawatan',
        'Farmasi',
        'Kasir',
        'SelesaiBilling',
    ];

    public $timestamps = false;
}
