<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $table = "branches";

    protected $dates = ['deleted_at'];

    protected $guarded = ['id'];

    protected $fillable = ['branch_code', 'branch_name', 'address','payment_instruction', 'user_id'];
}
