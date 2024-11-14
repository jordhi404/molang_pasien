<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';
    protected $table = 'Registration';
    protected $primaryKey = 'RegistrationID';

    public function consultVisit()
    {
        return $this->hasOne(ConsultVisit::class, 'RegistrationID', 'RegistrationID');
    }
}
