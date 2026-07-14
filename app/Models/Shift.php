<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = [
        'branch_id',
        'nama_shift',
        'jam_masuk',
        'jam_keluar',
        'toleransi_menit',
        'status',
        'for_role',
        'created_by',
        'updated_by',
    ];
}
