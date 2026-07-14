<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckUpResult extends Model
{
    protected $table = "check_up_results";

    protected $dates = ['created_at', 'deleted_at'];

    protected $guarded = ['id'];

    protected $fillable = ['patient_registration_id', 'anamnesa', 'sign', 'diagnosa',
        'status_outpatient_inpatient', 'status_finish', 'alasan_tidak_pengabaran', 'status_pengabaran', 'status_paid_off', 'user_id'];

    protected $casts = [
        'created_at' => 'datetime:d M Y',
    ];

    // public function service()
    // {
    //     return $this->hasMany('App\Models\DetailServiceOutPatient')
    //         ->join('list_of_services', 'list_of_services.id', '=', 'detail_service_out_patients.service_id');
    //     //->join('list_of_services', 'list_of_services.id', '=', 'detail_service_out_patients.service_id');
    // }

    // public function service_inpatient()
    // {
    //     return $this->hasMany('App\Models\DetailServiceInPatient')
    //         ->join('list_of_services', 'list_of_services.id', '=', 'detail_service_in_patients.service_id');
    // }

    // public function item()
    // {
    //     return $this->hasMany('App\Models\DetailItemOutPatient')
    //         ->join('list_of_items', 'list_of_items.id', '=', 'detail_item_out_patients.item_id');
    // }

    // public function item_inpatient()
    // {
    //     return $this->hasMany('App\Models\DetailItemInPatient')
    //         ->join('list_of_items', 'list_of_items.id', '=', 'detail_item_in_patients.item_id');
    // }

    // public function registration()
    // {
    //     return $this->belongsTo('App\Models\Registration', 'id');
    // }

    // public function user()
    // {
    //     return $this->belongsTo('App\Models\User', 'id');
    // }

    // public function registration2()
    // {
    //     return $this->belongsTo('App\Models\CheckUpResult', 'patient_registration_id')
    //         ->join('registrations', 'registrations.id', '=', 'check_up_results.patient_registration_id')
    //         ->join('patients', 'patients.id', '=', 'registrations.patient_id');
    // }

    // public function getDateAttribute()
    // {
    //     //return Carbon::createFormFormat(format:'Y-m-d',$this->attributes['created_at'])->format(format:'m/d/Y');
    //     return (new Carbon($this->attributes['created_at']))->format('d/m/y');
    // }
}
