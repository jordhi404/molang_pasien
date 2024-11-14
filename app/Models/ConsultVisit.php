<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultVisit extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';
    protected $table = 'ConsultVisit';
    protected $primaryKey = 'VisitID';

    public function standardCode()
    {
        return $this->belongsTo(StandardCode::class, 'GCPlanDischargeNotesType', 'StandardCodeID');
    }
}
