<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckUpFollowUp extends Model
{
    protected $table = 'check_up_follow_ups';

    protected $fillable = [
        'check_up_result_id',
        'branch_id',
        'owner_phone',
        'owner_name',
        'pet_name',
        'message',
        'scheduled_date',
        'status',
        'sent_at',
        'error_message',
        'user_id',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'sent_at'        => 'datetime',
    ];

    public function checkUpResult()
    {
        return $this->belongsTo(CheckUpResult::class);
    }
}
