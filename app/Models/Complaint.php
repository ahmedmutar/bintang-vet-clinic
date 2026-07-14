<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    protected $table = 'complaints';

    protected $fillable = ['name'];

    public function registrations()
    {
        return $this->hasMany(Registration::class, 'complaint_id');
    }
}
