<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientNotes extends Model
{
    use HasFactory;
    
    protected $connection = 'sqlsrv';
    protected $table = 'PatientNotes';
    protected $primaryKey = 'ID';

    public function bed()
    {
        return $this->belongsTo(Bed::class, 'MRN', 'MRN');
    }
}
