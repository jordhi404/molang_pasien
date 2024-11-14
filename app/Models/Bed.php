<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bed extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';
    protected $table = 'Bed';
    protected $primaryKey = 'BedID';

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'MRN', 'MRN');
    }

    public function patientNotes()
    {
        return $this->hasMany(PatientNotes::class, 'MRN', 'MRN');
    }

    public function registration()
    {
        return $this->belongsTo(Registration::class, 'RegistrationID', 'RegistrationID');
    }
}
