<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbsensiRadiusException extends Model
{
    protected $table    = 'absensi_radius_exceptions';
    protected $fillable = ['username', 'created_by'];
}
