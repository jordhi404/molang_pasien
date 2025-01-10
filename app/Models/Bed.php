<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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

    public static function getBedToClean() 
    {
        // Ambil data bed yang sedang dibersihkan
        return DB::connection('sqlsrv')
            ->table('vBed')
            ->select('BedID', 'BedCode', 'RoomCode', 'GCBedStatus', 'BedStatus', 'ServiceUnitName', 'LastUnoccupiedDate')
            ->where('IsDeleted', 0)
            ->whereIn('GCBedStatus', ['0116^H']) // Bed sedang dibersihkan
            ->whereNotNull('ServiceUnitCode')
            ->orderBy('ServiceUnitCode')
            ->orderBy('LastUnoccupiedDate')
            ->get();
    }
}
