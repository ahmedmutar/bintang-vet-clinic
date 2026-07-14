<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'shift_id',
        'tanggal',
        'jam_masuk',
        'jam_keluar',
        'foto_masuk',
        'foto_keluar',
        'latitude',
        'longitude',
        'alamat',
        'jarak_meter',
        'keterangan',
        'alasan_terlambat_pulang',
        'status',
    ];
}
