<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
  protected $table = "payrolls";

  protected $dates = ['deleted_at'];

  protected $guarded = ['id'];

  protected $fillable = [
    'user_employee_id',
    'date_payed',
    'basic_sallary',
    'accomodation',
    'eat',
    'amount_eat',
    'count_eat',
    'fine',
    'percentage_turnover',
    'amount_turnover',
    'total_turnover',
    'amount_inpatient',
    'count_inpatient',
    'total_inpatient',
    'percentage_surgery',
    'amount_surgery',
    'total_surgery',
    'total_overall',
    'user_id',
    'amount_grooming',
    'count_grooming',
    'total_grooming',
    'minus_turnover'
  ];
}
