<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
  protected $table = "registrations";

  protected $dates = ['deleted_at'];

  protected $guarded = ['id'];

  protected $fillable = [
    'id_number', 'patient_id', 'complaint', 'complaint_id', 'other_complaint',
    'registrant','pet_year_age', 'pet_month_age', 'pet_day_age', 'user_id', 'doctor_user_id', 'acceptance_status', 'is_hide_from_drop_down'
  ];

  public function complaint()
  {
    return $this->belongsTo(Complaint::class, 'complaint_id');
  }

  // public function check_up_results()
  // {
  //     return $this->hasMany('App\Models\CheckUpResult','patient_registration_id');
  // }
}
