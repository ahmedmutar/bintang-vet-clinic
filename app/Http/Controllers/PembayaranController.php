<?php

namespace App\Http\Controllers;

use App\Models\CheckUpResult;
use App\Models\DetailServicePatient;
use App\Models\Detail_medicine_group_check_up_result;
use App\Models\ListofItemsPetShop;
use App\Models\ListofPaymentItem;
use App\Models\ListofPayments;
use App\Models\ListofPaymentService;
use App\Models\list_of_payment_medicine_groups;
use App\Models\payment_petshop_with_clinic;
use DB;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class PembayaranController extends Controller
{
  public function DropDownPatient(Request $request)
  {
    $data = DB::table('check_up_results')
      ->join('registrations',          'check_up_results.patient_registration_id', '=', 'registrations.id')
      ->join('users',                  'registrations.user_id',                    '=', 'users.id')
      ->join('users as user_doctor',   'registrations.doctor_user_id',             '=', 'user_doctor.id')
      ->join('branches',               'user_doctor.branch_id',                    '=', 'branches.id')
      ->join('patients',               'registrations.patient_id',                 '=', 'patients.id')
      ->select(
        'check_up_results.id  as check_up_result_id',
        'registrations.id_number as registration_number',
        'patients.pet_name'
      )
      ->where(function ($query) {
        // Show if not yet paid (status_paid_off = 0)
        $query->where('check_up_results.status_paid_off', '=', 0)
          // OR show if no payment record exists yet
          ->orWhereNotExists(function ($sub) {
            $sub->select(DB::raw(1))
              ->from('list_of_payments')
              ->whereColumn('list_of_payments.check_up_result_id', 'check_up_results.id');
          });
      })
      ->whereNotBetween(
        DB::raw('DATE(check_up_results.created_at)'),
        ['2021-07-01', '2023-12-31']
      );

    // Branch filter only applies to receptionist / doctor roles
    if (in_array($request->user()->role, ['resepsionis', 'dokter'])) {
      $data->where('user_doctor.branch_id', '=', $request->user()->branch_id);
    }

    return response()->json($data->get(), 200);
  }

  public function index(Request $request)
  {

    $items_per_page = 50;

    $page = $request->page;

    $data = DB::table('list_of_payments')
      ->join('check_up_results', 'list_of_payments.check_up_result_id', '=', 'check_up_results.id')
      ->join('users', 'list_of_payments.user_id', '=', 'users.id')
      ->join('branches', 'users.branch_id', '=', 'branches.id')
      ->join('registrations', 'check_up_results.patient_registration_id', '=', 'registrations.id')
      ->join('patients', 'registrations.patient_id', '=', 'patients.id')
      ->select(
        'list_of_payments.id as list_of_payment_id',
        'check_up_results.id as check_up_result_id',
        'registrations.id_number as registration_number',
        'patients.id_member as patient_number',
        'patients.pet_category',
        'patients.pet_name',
        'registrations.complaint',
        'check_up_results.status_outpatient_inpatient',
        'users.fullname as created_by',
        DB::raw("DATE_FORMAT(list_of_payments.created_at, '%d %b %Y') as created_at")
      );

    if ($request->keyword) {
      $res = $this->Search($request);

      if ($res) {
        $data = $data->where($res, 'like', '%' . $request->keyword . '%');
      } else {
        $data = [];
        return response()->json(
          [
            'total_paging' => 0,
            'data' => $data
          ],
          200
        );
      }
    }

    if ($request->user()->role == 'resepsionis' || $request->user()->role == 'dokter') {
      $data = $data->where('users.branch_id', '=', $request->user()->branch_id);
    }

    if ($request->branch_id && $request->user()->role == 'admin') {
      $data = $data->where('users.branch_id', '=', $request->branch_id);
    }

    if ($request->orderby) {
      $data = $data->orderBy($request->column, $request->orderby);
    }

    $data = $data->orderBy('list_of_payments.id', 'desc');

    $offset = ($page - 1) * $items_per_page;

    $count_data = $data->count();
    $count_result = $count_data - $offset;

    if ($count_result < 0) {
      $data = $data->offset(0)->limit($items_per_page)->get();
    } else {
      $data = $data->offset($offset)->limit($items_per_page)->get();
    }

    $total_paging = $count_data / $items_per_page;

    return response()->json([
      'total_paging' => ceil($total_paging),
      'data' => $data
    ], 200);
  }

  private function Search($request)
  {
    $temp_column = '';

    $data = DB::table('list_of_payments')
      ->join('check_up_results', 'list_of_payments.check_up_result_id', '=', 'check_up_results.id')
      ->join('users', 'check_up_results.user_id', '=', 'users.id')
      ->join('branches', 'users.branch_id', '=', 'branches.id')
      ->join('registrations', 'check_up_results.patient_registration_id', '=', 'registrations.id')
      ->join('patients', 'registrations.patient_id', '=', 'patients.id')
      ->select(
        'registrations.id_number',
        'patients.id_member',
        'patients.pet_category',
        'patients.pet_name',
        'registrations.complaint',
        'users.fullname'
      );

    if ($request->branch_id && $request->user()->role == 'admin') {
      $data = $data->where('users.branch_id', '=', $request->branch_id);
    }

    if ($request->user()->role == 'resepsionis' || $request->user()->role == 'dokter') {
      $data = $data->where('users.branch_id', '=', $request->user()->branch_id);
    }

    if ($request->orderby) {
      $data = $data->orderBy($request->column, $request->orderby);
    }

    if ($request->keyword) {
      $data = $data->where('registrations.id_number', 'like', '%' . $request->keyword . '%');
    }

    $data = $data->get();

    if (count($data)) {
      $temp_column = 'registrations.id_number';
      return $temp_column;
    }
    //=======================================================

    $data = DB::table('list_of_payments')
      ->join('check_up_results', 'list_of_payments.check_up_result_id', '=', 'check_up_results.id')
      ->join('users', 'check_up_results.user_id', '=', 'users.id')
      ->join('branches', 'users.branch_id', '=', 'branches.id')
      ->join('registrations', 'check_up_results.patient_registration_id', '=', 'registrations.id')
      ->join('patients', 'registrations.patient_id', '=', 'patients.id')
      ->select(
        'registrations.id_number',
        'patients.id_member',
        'patients.pet_category',
        'patients.pet_name',
        'registrations.complaint',
        'users.fullname'
      );

    if ($request->branch_id && $request->user()->role == 'admin') {
      $data = $data->where('users.branch_id', '=', $request->branch_id);
    }

    if ($request->user()->role == 'resepsionis' || $request->user()->role == 'dokter') {
      $data = $data->where('users.branch_id', '=', $request->user()->branch_id);
    }

    if ($request->orderby) {
      $data = $data->orderBy($request->column, $request->orderby);
    }

    if ($request->keyword) {
      $data = $data->where('patients.id_member', 'like', '%' . $request->keyword . '%');
    }

    $data = $data->get();

    if (count($data)) {
      $temp_column = 'patients.id_member';
      return $temp_column;
    }
    //=======================================================

    $data = DB::table('list_of_payments')
      ->join('check_up_results', 'list_of_payments.check_up_result_id', '=', 'check_up_results.id')
      ->join('users', 'check_up_results.user_id', '=', 'users.id')
      ->join('branches', 'users.branch_id', '=', 'branches.id')
      ->join('registrations', 'check_up_results.patient_registration_id', '=', 'registrations.id')
      ->join('patients', 'registrations.patient_id', '=', 'patients.id')
      ->select(
        'registrations.id_number',
        'patients.id_member',
        'patients.pet_category',
        'patients.pet_name',
        'registrations.complaint',
        'users.fullname'
      );

    if ($request->branch_id && $request->user()->role == 'admin') {
      $data = $data->where('users.branch_id', '=', $request->branch_id);
    }

    if ($request->user()->role == 'resepsionis' || $request->user()->role == 'dokter') {
      $data = $data->where('users.branch_id', '=', $request->user()->branch_id);
    }

    if ($request->orderby) {
      $data = $data->orderBy($request->column, $request->orderby);
    }

    if ($request->keyword) {
      $data = $data->where('patients.pet_category', 'like', '%' . $request->keyword . '%');
    }

    $data = $data->get();

    if (count($data)) {
      $temp_column = 'patients.pet_category';
      return $temp_column;
    }
    //=======================================================

    $data = DB::table('list_of_payments')
      ->join('check_up_results', 'list_of_payments.check_up_result_id', '=', 'check_up_results.id')
      ->join('users', 'check_up_results.user_id', '=', 'users.id')
      ->join('branches', 'users.branch_id', '=', 'branches.id')
      ->join('registrations', 'check_up_results.patient_registration_id', '=', 'registrations.id')
      ->join('patients', 'registrations.patient_id', '=', 'patients.id')
      ->select(
        'registrations.id_number',
        'patients.id_member',
        'patients.pet_category',
        'patients.pet_name',
        'registrations.complaint',
        'users.fullname'
      );

    if ($request->branch_id && $request->user()->role == 'admin') {
      $data = $data->where('users.branch_id', '=', $request->branch_id);
    }

    if ($request->user()->role == 'resepsionis' || $request->user()->role == 'dokter') {
      $data = $data->where('users.branch_id', '=', $request->user()->branch_id);
    }

    if ($request->orderby) {
      $data = $data->orderBy($request->column, $request->orderby);
    }

    if ($request->keyword) {
      $data = $data->where('patients.pet_name', 'like', '%' . $request->keyword . '%');
    }

    $data = $data->get();

    if (count($data)) {
      $temp_column = 'patients.pet_name';
      return $temp_column;
    }
    //=======================================================

    $data = DB::table('list_of_payments')
      ->join('check_up_results', 'list_of_payments.check_up_result_id', '=', 'check_up_results.id')
      ->join('users', 'check_up_results.user_id', '=', 'users.id')
      ->join('branches', 'users.branch_id', '=', 'branches.id')
      ->join('registrations', 'check_up_results.patient_registration_id', '=', 'registrations.id')
      ->join('patients', 'registrations.patient_id', '=', 'patients.id')
      ->select(
        'registrations.id_number',
        'patients.id_member',
        'patients.pet_category',
        'patients.pet_name',
        'registrations.complaint',
        'users.fullname'
      );

    if ($request->branch_id && $request->user()->role == 'admin') {
      $data = $data->where('users.branch_id', '=', $request->branch_id);
    }

    if ($request->user()->role == 'resepsionis' || $request->user()->role == 'dokter') {
      $data = $data->where('users.branch_id', '=', $request->user()->branch_id);
    }

    if ($request->orderby) {
      $data = $data->orderBy($request->column, $request->orderby);
    }

    if ($request->keyword) {
      $data = $data->where('registrations.complaint', 'like', '%' . $request->keyword . '%');
    }

    $data = $data->get();

    if (count($data)) {
      $temp_column = 'registrations.complaint';
      return $temp_column;
    }
    //=======================================================

    $data = DB::table('list_of_payments')
      ->join('check_up_results', 'list_of_payments.check_up_result_id', '=', 'check_up_results.id')
      ->join('users', 'check_up_results.user_id', '=', 'users.id')
      ->join('branches', 'users.branch_id', '=', 'branches.id')
      ->join('registrations', 'check_up_results.patient_registration_id', '=', 'registrations.id')
      ->join('patients', 'registrations.patient_id', '=', 'patients.id')
      ->select(
        'registrations.id_number',
        'patients.id_member',
        'patients.pet_category',
        'patients.pet_name',
        'registrations.complaint',
        'users.fullname'
      );

    if ($request->branch_id && $request->user()->role == 'admin') {
      $data = $data->where('users.branch_id', '=', $request->branch_id);
    }

    if ($request->user()->role == 'resepsionis' || $request->user()->role == 'dokter') {
      $data = $data->where('users.branch_id', '=', $request->user()->branch_id);
    }

    if ($request->orderby) {
      $data = $data->orderBy($request->column, $request->orderby);
    }

    if ($request->keyword) {
      $data = $data->where('users.fullname', 'like', '%' . $request->keyword . '%');
    }

    $data = $data->get();

    if (count($data)) {
      $temp_column = 'users.fullname';
      return $temp_column;
    }
    //=======================================================
  }

  public function detail(Request $request)
  {

    $data = ListofPayments::find($request->list_of_payment_id);

    if (is_null($data)) {

      return response()->json([
        'message' => 'The data was invalid.',
        'errors' => ['Data Pembayaran Tidak Ditemukan!'],
      ], 422);
    }

    $data_check_up_result = CheckUpResult::find($data->check_up_result_id);

    $data->check_up_result = $data_check_up_result;

    $registration = DB::table('registrations')
      ->join('patients', 'registrations.patient_id', '=', 'patients.id')
      ->join('owners', 'patients.owner_id', '=', 'owners.id')
      ->select(
        'registrations.id_number as registration_number',
        'patients.id as patient_id',
        'patients.id_member as patient_number',
        'patients.pet_category',
        'patients.pet_name',
        'patients.pet_gender',
        'patients.pet_year_age',
        'patients.pet_month_age',
        DB::raw('(CASE WHEN patients.owner_name = "" THEN owners.owner_name ELSE patients.owner_name END) AS owner_name'),
        DB::raw('(CASE WHEN patients.owner_address = "" THEN owners.owner_address ELSE patients.owner_address END) AS owner_address'),
        DB::raw('(CASE WHEN patients.owner_phone_number = "" THEN owners.owner_phone_number ELSE patients.owner_phone_number END) AS owner_phone_number'),
        'registrations.complaint',
        'registrations.registrant'
      )
      ->where('registrations.id', '=', $data_check_up_result->patient_registration_id)
      ->first();

    $data->registration = $registration;

    $user = DB::table('check_up_results')
      ->join('users', 'check_up_results.user_id', '=', 'users.id')
      ->select('users.id as user_id', 'users.username as username')
      ->where('users.id', '=', $data->user_id)
      ->first();

    $data->user = $user;

    $services = DB::table('detail_service_patients')
      ->join('price_services', 'detail_service_patients.price_service_id', '=', 'price_services.id')
      ->join('list_of_services', 'price_services.list_of_services_id', '=', 'list_of_services.id')
      ->join('service_categories', 'list_of_services.service_category_id', '=', 'service_categories.id')
      ->join('users', 'detail_service_patients.user_id', '=', 'users.id')
      ->select(
        'detail_service_patients.id as detail_service_patient_id',
        'price_services.id as price_service_id',
        'list_of_services.id as list_of_service_id',
        'list_of_services.service_name',
        'detail_service_patients.quantity',
        DB::raw("TRIM(detail_service_patients.price_overall)+0 as price_overall"),
        'detail_service_patients.status_paid_off',
        'service_categories.category_name',
        DB::raw("TRIM(price_services.selling_price)+0 as selling_price"),
        'users.fullname as created_by',
        DB::raw("DATE_FORMAT(detail_service_patients.created_at, '%d %b %Y') as created_at")
      )
      ->where('detail_service_patients.check_up_result_id', '=', $data->check_up_result_id)
      ->orderBy('detail_service_patients.id', 'desc')
      ->get()
      ->map(function ($item) {
        $item->status_paid_off = (int) $item->status_paid_off;
        return $item;
      });

    $data['services'] = $services;

    $check = DB::table('detail_medicine_group_check_up_results')
      ->select('quantity')
      ->where('check_up_result_id', '=', $data->check_up_result_id)
      ->get();

    $valid = false;

    foreach ($check as $key) {

      if ($key->quantity == 0) {
        $valid = false;
      } else {
        $valid = true;
      }
    }

    if ($valid == true) {

      $item = DB::table('detail_medicine_group_check_up_results as dmg')
        ->join('price_medicine_groups as pmg', 'dmg.medicine_group_id', '=', 'pmg.id')
        ->join('medicine_groups', 'pmg.medicine_group_id', '=', 'medicine_groups.id')
        ->join('users', 'dmg.user_id', '=', 'users.id')
        ->select(
          'dmg.id as id',
          'dmg.check_up_result_id',
          'dmg.medicine_group_id',
          'medicine_groups.group_name',
          DB::raw("TRIM(pmg.selling_price)+0 as each_price"),
          'dmg.quantity as quantity',
          DB::raw("TRIM(pmg.selling_price * dmg.quantity)+0 as price_overall"),
          'dmg.status_paid_off',
          'users.fullname as created_by',
          DB::raw("DATE_FORMAT(dmg.created_at, '%d %b %Y') as created_at")
        )
        ->where('dmg.check_up_result_id', '=', $data->check_up_result_id)
        // ->groupby('dmg.medicine_group_id')
        ->orderBy('dmg.id', 'asc')
        ->get()
        ->map(function ($item) {
          $item->status_paid_off = (int) $item->status_paid_off;
          return $item;
        });
    } else {

      $item = DB::table('detail_medicine_group_check_up_results as dmg')
        ->join('price_medicine_groups as pmg', 'dmg.medicine_group_id', '=', 'pmg.id')
        ->join('medicine_groups', 'pmg.medicine_group_id', '=', 'medicine_groups.id')
        ->join('users', 'dmg.user_id', '=', 'users.id')
        ->select(
          'dmg.id as id',
          'dmg.check_up_result_id',
          'dmg.medicine_group_id',
          'medicine_groups.group_name',
          DB::raw("TRIM(pmg.selling_price)+0 as each_price"),
          DB::raw("COUNT(dmg.check_up_result_id) as quantity"),
          DB::raw("TRIM(SUM(pmg.selling_price))+0 as price_overall"),
          'dmg.status_paid_off',
          'users.fullname as created_by',
          DB::raw("DATE_FORMAT(dmg.created_at, '%d %b %Y') as created_at")
        )
        ->where('dmg.check_up_result_id', '=', $data->check_up_result_id)
        ->groupby('dmg.medicine_group_id')
        ->orderBy('dmg.id', 'asc')
        ->get()
        ->map(function ($item) {
          $item->status_paid_off = (int) $item->status_paid_off;
          return $item;
        });
    }

    $data['item'] = $item;

    $paid_services = DB::table('list_of_payment_services as lops')
      ->join('detail_service_patients as dsp', 'lops.detail_service_patient_id', '=', 'dsp.id')
      ->join('price_services', 'dsp.price_service_id', '=', 'price_services.id')
      ->join('list_of_services as los', 'price_services.list_of_services_id', '=', 'los.id')
      ->join('service_categories', 'los.service_category_id', '=', 'service_categories.id')
      ->join('check_up_results', 'lops.check_up_result_id', '=', 'check_up_results.id')
      ->join('users', 'dsp.user_id', '=', 'users.id')
      ->join('payment_methods', 'lops.payment_method_id', '=', 'payment_methods.id')
      ->select(
        'lops.id as list_of_payment_service_id',
        'dsp.id as detail_service_patient_id',
        DB::raw("DATE_FORMAT(lops.created_at, '%d %b %Y') as paid_date"),
        DB::raw("DATE_FORMAT(lops.created_at, '%d %b %Y') as created_at"),
        'users.fullname as created_by',
        'los.service_name',
        'dsp.quantity',
        DB::raw("TRIM(lops.discount)+0 as discount"),
        DB::raw("TRIM(dsp.price_overall - lops.amount_discount)+0 as price_overall_after_discount"),
        'service_categories.category_name',
        DB::raw("TRIM(lops.discount)+0 as discount"),
        'payment_methods.payment_name as payment_method',
        DB::raw("TRIM(price_services.selling_price)+0 as selling_price")
      )
      ->where('lops.list_of_payment_id', '=', $request->list_of_payment_id)
      ->orderBy('lops.id', 'desc')
      ->get();

    $data['paid_services'] = $paid_services;

    $check_paid_item = DB::table('detail_medicine_group_check_up_results')
      ->select('quantity')
      ->where('check_up_result_id', '=', $data->check_up_result_id)
      ->get();

    $valid = false;

    foreach ($check_paid_item as $key) {

      if ($key->quantity == 0) {
        $valid = false;
      } else {
        $valid = true;
      }
    }

    if ($valid == true) {
      //data baru
      $paid_item = DB::table('list_of_payment_medicine_groups as lop')
        ->join('detail_medicine_group_check_up_results as dmg', 'dmg.id', '=', 'lop.detail_medicine_group_check_up_result_id')
        ->join('price_medicine_groups as pmg', 'lop.medicine_group_id', '=', 'pmg.id')
        ->join('medicine_groups', 'pmg.medicine_group_id', '=', 'medicine_groups.id')
        ->join('users', 'lop.user_id', '=', 'users.id')
        ->join('payment_methods as pm', 'lop.payment_method_id', '=', 'pm.id')
        ->select(
          'lop.id as id',
          //'lop.check_up_result_id',
          'lop.medicine_group_id',
          'lop.detail_medicine_group_check_up_result_id',
          'medicine_groups.group_name',
          DB::raw("TRIM(pmg.selling_price)+0 as each_price"),
          'dmg.quantity as quantity',
          DB::raw("TRIM(lop.discount)+0 as discount"),
          DB::raw("TRIM((pmg.selling_price * dmg.quantity) - lop.amount_discount)+0 as price_overall_after_discount"),
          'pm.payment_name as payment_method',
          'users.fullname as created_by',
          DB::raw("DATE_FORMAT(lop.created_at, '%d %b %Y') as created_at"),
          DB::raw("DATE_FORMAT(lop.created_at, '%d %b %Y') as paid_date"),
        )
        ->where('lop.list_of_payment_id', '=', $request->list_of_payment_id)
        ->orderBy('lop.id', 'desc')
        ->get();
    } else {
      //data lama
      $paid_item = DB::table('list_of_payment_medicine_groups as lop')
        //->join('list_of_payment_medicine_groups as pmg', 'lop.medicine_group_id', '=', 'pmg.id')
        ->join('price_medicine_groups as pmg', 'lop.medicine_group_id', '=', 'pmg.id')
        ->join('medicine_groups', 'pmg.medicine_group_id', '=', 'medicine_groups.id')
        ->join('users', 'lop.user_id', '=', 'users.id')
        ->join('payment_methods as pm', 'lop.payment_method_id', '=', 'pm.id')
        ->select(
          'lop.id as id',
          //'lop.check_up_result_id',
          'lop.medicine_group_id',
          'lop.detail_medicine_group_check_up_result_id',
          'medicine_groups.group_name',
          DB::raw("TRIM(pmg.selling_price)+0 as each_price"),
          DB::raw("COUNT(lop.medicine_group_id) as quantity"),
          //'lop.quantity as quantity',
          DB::raw("TRIM(lop.discount)+0 as discount"),
          DB::raw("TRIM(lop.amount_discount * lop.quantity)+0 as amount_discount"),
          DB::raw("TRIM(SUM(pmg.selling_price) - (lop.amount_discount * lop.quantity))+0 as price_overall_after_discount"),
          'pm.payment_name as payment_method',
          'users.fullname as created_by',
          DB::raw("DATE_FORMAT(lop.created_at, '%d %b %Y') as created_at"),
          DB::raw("DATE_FORMAT(lop.created_at, '%d %b %Y') as paid_date"),
        )
        ->where('lop.list_of_payment_id', '=', $request->list_of_payment_id)
        ->groupby('lop.medicine_group_id')
        ->orderBy('lop.id', 'desc')
        ->get();
    }

    $data['paid_item'] = $paid_item;

    $pet_shop = DB::table('payment_petshop_with_clinics as ppwc')
      ->join('price_item_pet_shops as pip', 'ppwc.price_item_pet_shop_id', '=', 'pip.id')
      ->join('list_of_item_pet_shops as loi', 'pip.list_of_item_pet_shop_id', '=', 'loi.id')
      ->join('users', 'ppwc.user_id', '=', 'users.id')
      ->join('payment_methods as pm', 'ppwc.payment_method_id', '=', 'pm.id')
      ->select(
        'loi.item_name',
        'ppwc.total_item',
        DB::raw("TRIM(pip.selling_price)+0 as selling_price"),
        DB::raw("TRIM(pip.selling_price * ppwc.total_item)+0 as price_overall"),
        'users.fullname as created_by',
        DB::raw("DATE_FORMAT(ppwc.created_at, '%d %b %Y') as created_at"),
        'pm.payment_name as payment_method'
      )
      ->where('ppwc.list_of_payment_id', '=', $request->list_of_payment_id)
      ->orderBy('ppwc.id', 'desc')
      ->get();

    $data['pet_shop'] = $pet_shop;

    return response()->json($data, 200);
  }

  public function create(Request $request)
  {
    $check_list_of_payment = DB::table('list_of_payments')
      ->where('check_up_result_id', '=', $request->check_up_result_id)
      ->count();

    if ($check_list_of_payment != 0) {

      return response()->json([
        'message' => 'The given data was invalid.',
        'errors' => ['Data Pembayaran ini sudah pernah ada!'],
      ], 422);
    }

    $check_up_result = DB::table('check_up_results')
      ->select('status_paid_off')
      ->where('id', '=', $request->check_up_result_id)
      ->first();

    if ($check_up_result->status_paid_off == 1) {

      return response()->json([
        'message' => 'The given data was invalid.',
        'errors' => ['Data Pemeriksaan ini sudah pernah dilunaskan!'],
      ], 422);
    }

    $services = $request->service_payment;
    $result_services = json_decode($services, true);
    //$services;
    //json_encode($services)

    if (count($result_services) != 0) {

      foreach ($result_services as $key_service) {

        $check_service = DetailServicePatient::find($key_service['detail_service_patient_id']);

        if (is_null($check_service)) {
          return response()->json([
            'message' => 'The data was invalid.',
            'errors' => ['Data Hasil Pemeriksaan Layanan Pasien tidak ditemukan!'],
          ], 404);
        }

        $check_service_name = DB::table('detail_service_patients')
          ->join('price_services', 'detail_service_patients.price_service_id', '=', 'price_services.id')
          ->join('list_of_services', 'price_services.list_of_services_id', '=', 'list_of_services.id')
          ->select('list_of_services.service_name as service_name')
          ->where('detail_service_patients.id', '=', $key_service['detail_service_patient_id'])
          ->first();

        if (is_null($check_service_name)) {
          return response()->json([
            'message' => 'The data was invalid.',
            'errors' => ['Data List of Services not found!'],
          ], 404);
        }

        $check_detail_service = DB::table('detail_service_patients')
          ->select('id')
          ->where('status_paid_off', '=', 1)
          ->where('id', '=', $key_service['detail_service_patient_id'])
          ->first();

        if ($check_detail_service) {
          return response()->json([
            'message' => 'The data was invalid.',
            'errors' => ['Jasa ' . $check_service_name->service_name . ' sudah pernah dibayar sebelumnya!'],
          ], 422);
        }
      }
    }

    $items = $request->item_payment;
    $result_item = json_decode($items, true);
    //json_encode($items, true);
    //$items;

    if (count($result_item) != 0) {

      foreach ($result_item as $value_item) {

        $check_item_name = DB::table('detail_medicine_group_check_up_results as dmg')
          ->where('dmg.check_up_result_id', '=', $request->check_up_result_id)
          ->where('dmg.medicine_group_id', '=', $value_item['medicine_group_id'])
          ->get();

        if (is_null($check_item_name)) {
          return response()->json([
            'message' => 'The data was invalid.',
            'errors' => ['Data tidak ditemukan!'],
          ], 404);
        }
      }
    }

    //validation pet shop
    $item_pet_shops = $request->petshop_payment;
    $result_pet_shops = json_decode($item_pet_shops, true);

    if (count($result_pet_shops) != 0) {

      foreach ($result_pet_shops as $value_item) {

        $check_item = DB::table('price_item_pet_shops as pip')
          ->where('pip.id', '=', $value_item['id'])
          ->get();

        if (is_null($check_item)) {
          return response()->json([
            'message' => 'The data was invalid.',
            'errors' => ['Data tidak ditemukan!'],
          ], 404);
        }

        $check_item = DB::table('price_item_pet_shops as pip')
          ->join('list_of_item_pet_shops as loi', 'loi.id', 'pip.list_of_item_pet_shop_id')
          ->where('pip.id', '=', $value_item['id'])
          ->select('loi.total_item as total_item')
          ->first();

        $calculate_stock = $check_item->total_item - $value_item['total_item'];

        if ($calculate_stock < 0) {
          return response()->json([
            'message' => 'The given data was invalid.',
            'errors' => ['Jumlah stok ' . $value_item['item_name'] . ' Pet Shop kurang atau habis!'],
          ], 422);
        }
      }
    }

    $list_of_payment = ListofPayments::create([
      'check_up_result_id' => $request->check_up_result_id,
      'user_id' => $request->user()->id,
    ]);

    //simpan data jasa
    if (count($result_services) != 0) {
      foreach ($result_services as $key_service) {

        $discount = 0;
        $amount_discount = 0;

        if (isset($key_service['discount'])) {
          $discount = $key_service['discount'];
        }

        if (isset($key_service['amount_discount'])) {
          $amount_discount = $key_service['amount_discount'];
        }

        $check_quantity = DB::table('detail_service_patients')
          ->select('quantity')
          ->where('id', '=', $key_service['detail_service_patient_id'])
          ->first();

        $item = ListofPaymentService::create([
          'detail_service_patient_id' => $key_service['detail_service_patient_id'],
          'check_up_result_id' => $request->check_up_result_id,
          'list_of_payment_id' => $list_of_payment->id,
          'user_id' => $request->user()->id,
          'payment_method_id' => $request->payment_method_id,
          'discount' => $discount,
          'amount_discount' => $amount_discount,
          'quantity' => $check_quantity->quantity,
        ]);

        $check_service = DetailServicePatient::find($key_service['detail_service_patient_id']);

        $check_service->status_paid_off = 1;
        $check_service->user_update_id = $request->user()->id;
        $check_service->updated_at = \Carbon\Carbon::now();
        $check_service->save();
      }
    }

    //simpan data barang
    if (count($result_item) != 0) {

      foreach ($result_item as $value_item) {

        $detail_medicine_group = DB::table('detail_medicine_group_check_up_results as dmg')
          ->select('id', 'quantity')
          ->where('dmg.id', '=', $value_item['id'])
          // ->where('dmg.check_up_result_id', '=', $request->check_up_result_id)
          // ->where('dmg.medicine_group_id', '=', $value_item['medicine_group_id'])
          ->get();

        foreach ($detail_medicine_group as $mdc_group) {

          $discount = 0;
          $amount_discount = 0;

          if (isset($value_item['discount'])) {
            $discount = $value_item['discount'];
          }

          if (isset($value_item['amount_discount'])) {
            $amount_discount = $value_item['amount_discount'];
          }

          $quantity = $value_item['quantity'];

          if ($detail_medicine_group->count() > 1 && $amount_discount != 0 && $mdc_group->quantity == 0) {

            $amount_discount = $amount_discount / $value_item['quantity'];
            $quantity = 1;
          }

          $payment_medicine_group = list_of_payment_medicine_groups::create([
            'detail_medicine_group_check_up_result_id' => $mdc_group->id,
            'list_of_payment_id' => $list_of_payment->id,
            'medicine_group_id' => $value_item['medicine_group_id'],
            'user_id' => $request->user()->id,
            'payment_method_id' => $request->payment_method_id,
            'discount' => $discount,
            'amount_discount' => $amount_discount,
            'quantity' => $quantity,
          ]);

          $check_medicine_group_check_up = Detail_medicine_group_check_up_result::where('medicine_group_id', '=', $value_item['medicine_group_id'])
            ->where('check_up_result_id', '=', $request->check_up_result_id)
            ->update(['status_paid_off' => 1, 'user_update_id' => $request->user()->id, 'updated_at' => \Carbon\Carbon::now()]);

          $detail_item_patient = DB::table('detail_item_patients as dip')
            ->select('id', 'price_item_id', 'price_overall', 'quantity')
            ->where('dip.detail_medicine_group_id', '=', $mdc_group->id)
            ->get();

          foreach ($detail_item_patient as $value_detail) {

            $item = ListofPaymentItem::create([
              'list_of_payment_medicine_group_id' => $payment_medicine_group->id,
              'price_item_id' => $value_detail->price_item_id,
              'price_overall' => $value_detail->price_overall,
              'quantity' => $value_detail->quantity,
              'user_id' => $request->user()->id,
            ]);
          }
        }
      }
    }

    //simpan data pet shop
    if (count($result_pet_shops) != 0) {

      foreach ($result_pet_shops as $value_item) {

        $get_stock = DB::table('price_item_pet_shops as pip')
          ->join('list_of_item_pet_shops as loi', 'loi.id', 'pip.list_of_item_pet_shop_id')
          ->where('pip.id', '=', $value_item['id'])
          ->select('loi.id')
          ->first();

        $list_of_item_pet_shop = ListofItemsPetShop::find($get_stock->id);
        $list_of_item_pet_shop->total_item = $list_of_item_pet_shop->total_item - $value_item['total_item'];
        $list_of_item_pet_shop->diff_item = $list_of_item_pet_shop->diff_item - $value_item['total_item'];
        $list_of_item_pet_shop->user_update_id = $request->user()->id;
        $list_of_item_pet_shop->updated_at = \Carbon\Carbon::now();
        $list_of_item_pet_shop->save();

        payment_petshop_with_clinic::create([
          'price_item_pet_shop_id' => $value_item['id'],
          'list_of_payment_id' => $list_of_payment->id,
          'payment_method_id' => $request->payment_method_id,
          'total_item' => $value_item['total_item'],
          'user_id' => $request->user()->id,
        ]);
      }
    }
    //cek kelunasan jasa

    $count_payed_service = DB::table('list_of_payment_services')
      ->where('check_up_result_id', '=', $request->check_up_result_id)
      ->count();

    $count_service = DB::table('detail_service_patients')
      ->select('id')
      ->where('check_up_result_id', '=', $request->check_up_result_id)
      ->count();

    //cek kelunasan barang

    $count_payed_item = DB::table('list_of_payment_medicine_groups')
      ->where('list_of_payment_id', '=', $list_of_payment->id)
      ->count();

    $count_item = DB::table('detail_medicine_group_check_up_results')
      ->select('id')
      ->where('check_up_result_id', '=', $request->check_up_result_id)
      ->count();

    if ($count_payed_service == $count_service && $count_payed_item == $count_item) {

      $check_up_result = CheckUpResult::find($request->check_up_result_id);

      $check_up_result->status_paid_off = 1;
      $check_up_result->user_update_id = $request->user()->id;
      $check_up_result->updated_at = \Carbon\Carbon::now();
      $check_up_result->save();
    }

    return response()->json(
      [
        'message' => 'Tambah Data Berhasil!',
      ],
      200
    );
  }

  public function update(Request $request)
  {
    $check_list_of_payment = DB::table('list_of_payments')
      ->where('check_up_result_id', '=', $request->check_up_result_id)
      ->count();

    $data_list_of_payment = DB::table('list_of_payments')
      ->where('check_up_result_id', '=', $request->check_up_result_id)
      ->first();

    if ($check_list_of_payment == 0) {

      return response()->json([
        'message' => 'The given data was invalid.',
        'errors' => ['Data Pembayaran ini belum pernah ada!'],
      ], 422);
    }

    $status_paid_off = true;

    $services = $request->service_payment;
    $result_services = json_decode(json_encode($services), true);

    if (count($result_services) != 0) {

      foreach ($result_services as $key_service) {

        if ($key_service['detail_service_patient_id']) {

          $check_service = DetailServicePatient::find($key_service['detail_service_patient_id']);

          if (is_null($check_service)) {
            return response()->json([
              'message' => 'The data was invalid.',
              'errors' => ['Data tidak ditemukan!'],
            ], 404);
          }

          $check_service_name = DB::table('detail_service_patients')
            ->join('price_services', 'detail_service_patients.price_service_id', '=', 'price_services.id')
            ->join('list_of_services', 'price_services.list_of_services_id', '=', 'list_of_services.id')
            ->select('list_of_services.service_name as service_name')
            ->where('detail_service_patients.id', '=', $key_service['detail_service_patient_id'])
            ->first();

          if (is_null($check_service_name)) {
            return response()->json([
              'message' => 'The data was invalid.',
              'errors' => ['Data List of Services not found!'],
            ], 404);
          }

          if (is_null($key_service['status'])) {

            $check_detail_service = DB::table('detail_service_patients')
              ->select('id')
              ->where('status_paid_off', '=', 1)
              ->where('id', '=', $key_service['detail_service_patient_id'])
              ->first();

            if ($check_detail_service) {
              return response()->json([
                'message' => 'The data was invalid.',
                'errors' => ['Jasa ' . $check_service_name->service_name . ' sudah pernah dibayar sebelumnya!'],
              ], 422);
            }
          }
        }

        if ($key_service['status'] == 'del') {
          $status_paid_off = false;
        }
      }
    }

    $items = $request->item_payment;
    $result_item = json_decode(json_encode($items), true);

    if (count($result_item) != 0) {

      foreach ($result_item as $value_item) {

        if ($value_item['medicine_group_id']) {

          $check_medicine_group = Detail_medicine_group_check_up_result::where('medicine_group_id', '=', $value_item['medicine_group_id'])
            ->where('check_up_result_id', '=', $request->check_up_result_id)
            ->get();

          if (is_null($check_medicine_group)) {
            return response()->json([
              'message' => 'The data was invalid.',
              'errors' => ['Data tidak ditemukan!'],
            ], 404);
          }

          $check_item_name = DB::table('detail_medicine_group_check_up_results as dmg')
            ->join('price_medicine_groups as pmg', 'dmg.medicine_group_id', '=', 'pmg.id')
            ->join('medicine_groups as mg', 'pmg.medicine_group_id', '=', 'mg.id')
            ->select('mg.group_name as group_name')
            ->where('dmg.medicine_group_id', '=', $value_item['medicine_group_id'])
            ->where('dmg.check_up_result_id', '=', $request->check_up_result_id)
            ->first();

          if (is_null($check_item_name)) {
            return response()->json([
              'message' => 'The data was invalid.',
              'errors' => ['Data List of Item not found!'],
            ], 404);
          }

          if (is_null($value_item['status'])) {

            $check_detail_item = DB::table('detail_medicine_group_check_up_results')
              ->select('id')
              ->where('status_paid_off', '=', 1)
              ->where('medicine_group_id', '=', $value_item['medicine_group_id'])
              ->where('check_up_result_id', '=', $request->check_up_result_id)
              ->first();

            if ($check_detail_item) {
              return response()->json([
                'message' => 'The data was invalid.',
                'errors' => ['Data Barang ' . $check_item_name->group_name . ' sudah pernah dibayar sebelumnya!'],
              ], 422);
            }
          }
        }

        if ($value_item['status'] == 'del') {
          $status_paid_off = false;
        }
      }
    }

    //simpan data jasa
    foreach ($result_services as $key_service) {

      if ($key_service['detail_service_patient_id'] && is_null($key_service['status'])) {

        $discount = 0;
        $amount_discount = 0;

        if (isset($key_service['discount'])) {
          $discount = $key_service['discount'];
        }

        if (isset($key_service['amount_discount'])) {
          $amount_discount = $key_service['amount_discount'];
        }

        $check_quantity = DB::table('detail_service_patients')
          ->select('quantity')
          ->where('id', '=', $key_service['detail_service_patient_id'])
          ->first();

        $item = ListofPaymentService::create([
          'detail_service_patient_id' => $key_service['detail_service_patient_id'],
          'check_up_result_id' => $request->check_up_result_id,
          'list_of_payment_id' => $data_list_of_payment->id,
          'user_id' => $request->user()->id,
          'payment_method_id' => $request->payment_method_id,
          'discount' => $discount,
          'amount_discount' => $amount_discount,
          'quantity' => $check_quantity->quantity,
        ]);

        $check_service = DetailServicePatient::find($key_service['detail_service_patient_id']);

        $check_service->status_paid_off = 1;
        $check_service->user_update_id = $request->user()->id;
        $check_service->updated_at = \Carbon\Carbon::now();
        $check_service->save();
      } elseif ($key_service['status'] == "del") {

        $check_service = DetailServicePatient::find($key_service['detail_service_patient_id']);

        $check_service->status_paid_off = 0;
        $check_service->user_update_id = $request->user()->id;
        $check_service->updated_at = \Carbon\Carbon::now();
        $check_service->save();

        $delete_payment_service = DB::table('list_of_payment_services')
          ->where('detail_service_patient_id', $key_service['detail_service_patient_id'])->delete();
      }
    }

    //simpan data barang
    if (count($result_item) != 0) {
      foreach ($result_item as $value_item) {

        if ($value_item['medicine_group_id'] && is_null($value_item['status'])) {

          // $detail_medicine_group = DB::table('detail_medicine_group_check_up_results as dmg')
          //     ->where('dmg.check_up_result_id', '=', $request->check_up_result_id)
          //     ->where('dmg.medicine_group_id', '=', $value_item['medicine_group_id'])
          //     ->first();

          $list_of_payment = DB::table('list_of_payments')
            ->select('id')
            ->where('check_up_result_id', '=', $request->check_up_result_id)
            ->first();

          $detail_medicine_group = DB::table('detail_medicine_group_check_up_results as dmg')
            ->select('id', 'quantity')
            ->where('dmg.check_up_result_id', '=', $request->check_up_result_id)
            ->where('dmg.medicine_group_id', '=', $value_item['medicine_group_id'])
            ->get();

          foreach ($detail_medicine_group as $res) {

            $discount = 0;
            $amount_discount = 0;

            if (isset($value_item['discount'])) {
              $discount = $value_item['discount'];
            }

            if (isset($value_item['amount_discount'])) {
              $amount_discount = $value_item['amount_discount'];
            }

            $quantity = $value_item['quantity'];

            if ($detail_medicine_group->count() > 1 && $amount_discount != 0 && $res->quantity == 0) {

              $amount_discount = $amount_discount / $value_item['quantity'];
              $quantity = 1;
            }

            $payment_medicine_group = list_of_payment_medicine_groups::create([
              'detail_medicine_group_check_up_result_id' => $res->id,
              'list_of_payment_id' => $list_of_payment->id,
              'medicine_group_id' => $value_item['medicine_group_id'],
              'user_id' => $request->user()->id,
              'payment_method_id' => $request->payment_method_id,
              'discount' => $discount,
              'amount_discount' => $amount_discount,
              'quantity' => $quantity,
            ]);

            $check_medicine_group_check_up = Detail_medicine_group_check_up_result::where('medicine_group_id', '=', $value_item['medicine_group_id'])
              ->where('check_up_result_id', '=', $request->check_up_result_id)
              ->update(['status_paid_off' => 1, 'user_update_id' => $request->user()->id, 'updated_at' => \Carbon\Carbon::now()]);

            $detail_item_patient = DB::table('detail_item_patients as dip')
              ->select('id', 'price_item_id', 'price_overall', 'quantity')
              ->where('dip.detail_medicine_group_id', '=', $res->id)
              ->get();

            foreach ($detail_item_patient as $value_detail) {

              $item = ListofPaymentItem::create([
                'list_of_payment_medicine_group_id' => $payment_medicine_group->id,
                'price_item_id' => $value_detail->price_item_id,
                'price_overall' => $value_detail->price_overall,
                'quantity' => $value_detail->quantity,
                'user_id' => $request->user()->id,
              ]);
            }
          }

          // $values = Detail_medicine_group_check_up_result::where('check_up_result_id', '=', $request->check_up_result_id)
          //     ->where('medicine_group_id', '=', $value_item['medicine_group_id'])
          //     ->update(['status_paid_off' => true, 'user_update_id' => $request->user()->id, 'updated_at' => \Carbon\Carbon::now()]);

        } elseif ($value_item['status'] == "del") {

          $values = Detail_medicine_group_check_up_result::where('check_up_result_id', '=', $request->check_up_result_id)
            ->where('medicine_group_id', '=', $value_item['medicine_group_id'])
            ->update(['status_paid_off' => false, 'user_update_id' => $request->user()->id, 'updated_at' => \Carbon\Carbon::now()]);

          $check_list_of_payment_medicine_group = DB::table('list_of_payment_medicine_groups as lopm')
            ->join('detail_medicine_group_check_up_results as dmg', 'lopm.detail_medicine_group_check_up_result_id', 'dmg.id')
            ->select('lopm.id as id')
            ->where('lopm.medicine_group_id', '=', $value_item['medicine_group_id'])
            ->where('dmg.check_up_result_id', '=', $request->check_up_result_id)
            ->first();

          $check_medicine_group_check_up = list_of_payment_medicine_groups::where('medicine_group_id', '=', $value_item['medicine_group_id'])
            ->where('id', '=', $check_list_of_payment_medicine_group->id)
            ->delete();
          //->update(['status_paid_off' => 1, 'user_update_id' => $request->user()->id, 'updated_at' => \Carbon\Carbon::now()]);

          $delete_payment_item = ListofPaymentItem::where('list_of_payment_medicine_group_id', $check_list_of_payment_medicine_group->id)
            // ->where('medicine_group_id', $value_item['medicine_group_id'])
            ->delete();
        }
      }
    }

    //cek kelunasan jasa

    $count_payed_service = DB::table('list_of_payment_services')
      ->where('check_up_result_id', '=', $request->check_up_result_id)
      ->count();

    $count_service = DB::table('detail_service_patients')
      ->select('id')
      ->where('status_paid_off', '=', 1)
      ->where('check_up_result_id', '=', $request->check_up_result_id)
      ->count();

    //cek kelunasan barang

    // $check_list_of_payment_medicine_group = DB::table('list_of_payment_medicine_groups as lopm')
    //                 ->join('detail_medicine_group_check_up_results as dmg', 'lopm.detail_medicine_group_check_up_result_id', 'dmg.id')
    //                 ->where('dmg.check_up_result_id', '=', $request->check_up_result_id)
    //                 ->count();

    $count_payed_item = DB::table('list_of_payment_medicine_groups as lopm')
      ->join('detail_medicine_group_check_up_results as dmg', 'lopm.detail_medicine_group_check_up_result_id', 'dmg.id')
      ->where('dmg.check_up_result_id', '=', $request->check_up_result_id)
      ->count();

    // DB::table('list_of_payment_medicine_groups')
    //     ->where('check_up_result_id', '=', $request->check_up_result_id)
    //     ->count();

    $count_item = DB::table('detail_medicine_group_check_up_results')
      ->select('id')
      ->where('check_up_result_id', '=', $request->check_up_result_id)
      ->count();

    if ($count_payed_service == $count_service && $count_payed_item == $count_item) {

      $check_up_result = CheckUpResult::find($request->check_up_result_id);

      $check_up_result->status_paid_off = 1;
      $check_up_result->user_update_id = $request->user()->id;
      $check_up_result->updated_at = \Carbon\Carbon::now();
      $check_up_result->save();
    }

    $list_of_payment = ListofPayments::where('check_up_result_id', '=', $request->check_up_result_id)
      ->update(['user_update_id' => $request->user()->id, 'updated_at' => \Carbon\Carbon::now()]);

    if ($status_paid_off == false) {
      $check_up_result = CheckUpResult::find($request->check_up_result_id);

      $check_up_result->status_paid_off = 0;
      $check_up_result->user_update_id = $request->user()->id;
      $check_up_result->updated_at = \Carbon\Carbon::now();
      $check_up_result->save();
    }

    return response()->json(
      [
        'message' => 'Ubah Data Berhasil!',
      ],
      200
    );
  }

  public function delete(Request $request)
  {

    $check_payment = ListofPayments::find($request->list_of_payment_id);

    if (is_null($check_payment)) {
      return response()->json([
        'message' => 'The data was invalid.',
        'errors' => ['Data tidak ditemukan!'],
      ], 404);
    }

    $check_payment_service = ListofPaymentService::where('list_of_payment_id', '=', $request->list_of_payment_id)
      ->get();

    if ($check_payment_service) {

      $data_service = [];

      $data_service = $check_payment_service;

      foreach ($data_service as $service) {

        $check_service = DetailServicePatient::find($service['detail_service_patient_id']);

        if ($check_service) {
          $check_service->status_paid_off = 0;
          $check_service->user_update_id = $request->user()->id;
          $check_service->updated_at = \Carbon\Carbon::now();
          $check_service->save();
        }
      }

      $check_payment_service = DB::table('list_of_payment_services')
        ->where('list_of_payment_id', $request->list_of_payment_id)
        ->delete();
    }

    $check_medicine_group = DB::table('list_of_payment_medicine_groups')
      ->select('id', 'detail_medicine_group_check_up_result_id')
      ->where('list_of_payment_id', '=', $request->list_of_payment_id)
      ->get();

    foreach ($check_medicine_group as $val) {

      $check_payment_item = ListofPaymentItem::where('list_of_payment_medicine_group_id', '=', $val->id)
        ->get();

      if ($check_payment_item) {

        $values = DB::table('detail_medicine_group_check_up_results')
          ->where('id', $val->detail_medicine_group_check_up_result_id)
          ->update(['status_paid_off' => 0, 'user_update_id' => $request->user()->id, 'updated_at' => \Carbon\Carbon::now()]);

        $check_payment_item = DB::table('list_of_payment_items')
          ->where('list_of_payment_medicine_group_id', $val->id)
          ->delete();
      }
    }

    $check_paid_off = DB::table('check_up_results')
      ->where('id', '=', $check_payment->check_up_result_id)
      ->get();

    if ($check_paid_off[0]->status_paid_off == 1) {

      $update_paid_off = CheckUpResult::find($check_payment->check_up_result_id);

      $update_paid_off->status_paid_off = 0;
      $update_paid_off->user_update_id = $request->user()->id;
      $update_paid_off->updated_at = \Carbon\Carbon::now();
      $update_paid_off->save();
    }

    list_of_payment_medicine_groups::where('list_of_payment_id', '=', $request->list_of_payment_id)
      ->delete();

    $check_stock = DB::table('payment_petshop_with_clinics as ppwc')
      ->join('price_item_pet_shops as pip', 'ppwc.price_item_pet_shop_id', 'pip.id')
      ->join('list_of_item_pet_shops as loi', 'pip.list_of_item_pet_shop_id', 'loi.id')
      ->select('loi.id as list_of_item_id', 'ppwc.total_item as total_item_buy', 'loi.total_item as total_item_stock', 'loi.diff_item')
      ->where('ppwc.list_of_payment_id', '=', $request->list_of_payment_id)
      ->get();

    foreach ($check_stock as $res) {

      $list_of_item_pet_shop = ListofItemsPetShop::find($res->list_of_item_id);

      $list_of_item_pet_shop->total_item = $res->total_item_stock + $res->total_item_buy;
      $list_of_item_pet_shop->diff_item = $res->diff_item + $res->total_item_buy;
      $list_of_item_pet_shop->save();
    }

    payment_petshop_with_clinic::where('list_of_payment_id', '=', $request->list_of_payment_id)
      ->delete();

    $check_payment->delete();

    return response()->json(
      [
        'message' => 'Hapus Data Berhasil!',
      ],
      200
    );
  }

  public function print_pdf(Request $request)
  {
    $res_service = "";
    $res_medicine_group_id = "";
    $res_check_up_result_id = "";

    $services = $request->service_payment;
    $result_service = json_decode($services, true);
    //$services;
    //json_decode($services, true);

    if ($result_service) {

      foreach ($result_service as $dat) {
        $res_service = $res_service . (string) $dat['detail_service_patient_id'] . ",";
      }
    }

    $res_service = rtrim($res_service, ", ");

    $myArray_service = explode(',', $res_service);

    $items = $request->item_payment;
    $result_item = json_decode($items, true);
    //json_decode($items, true);
    //$items;
    //

    if ($result_item) {

      foreach ($result_item as $key) {
        $res_medicine_group_id = $res_medicine_group_id . (string) $key['medicine_group_id'] . ",";
      }
    }

    $res_medicine_group_id = rtrim($res_medicine_group_id, ", ");
    $myArray_medicine_group_id = explode(',', $res_medicine_group_id);

    $check_list_of_payment = DB::table('list_of_payments')
      ->select('id')
      ->where('check_up_result_id', '=', $request->check_up_result_id)
      ->first();

    $check = DB::table('detail_medicine_group_check_up_results')
      ->select('quantity')
      ->where('check_up_result_id', '=', $request->check_up_result_id)
      ->get();

    $valid = false;

    foreach ($check as $key) {

      if ($key->quantity == 0) {
        $valid = false;
      } else {
        $valid = true;
      }
    }

    if ($valid == true) {
      $data_item = DB::table('list_of_payment_medicine_groups as lopm')
        ->join('price_medicine_groups as pmg', 'lopm.medicine_group_id', '=', 'pmg.id')
        ->join('medicine_groups', 'pmg.medicine_group_id', '=', 'medicine_groups.id')
        ->join('users', 'lopm.user_id', '=', 'users.id')
        ->join('detail_medicine_group_check_up_results as dmg', 'lopm.detail_medicine_group_check_up_result_id', '=', 'dmg.id')
        ->select(
          'medicine_groups.group_name',
          DB::raw("TRIM(pmg.selling_price)+0 as each_price"),
          DB::raw("dmg.quantity as quantity"),
          DB::raw("TRIM((pmg.selling_price * dmg.quantity))+0 as price_overall"),
          DB::raw("TRIM(lopm.amount_discount)+0 as amount_discount"),
          'users.fullname as created_by',
          DB::raw("DATE_FORMAT(lopm.created_at, '%d %b %Y') as created_at")
        )
        ->where('lopm.list_of_payment_id', '=', $check_list_of_payment->id)
        ->whereIn('lopm.medicine_group_id', $myArray_medicine_group_id)
        ->orderBy('lopm.id', 'desc')
        ->get();
    } else {
      $data_item = DB::table('list_of_payment_medicine_groups as lopm')
        ->join('price_medicine_groups as pmg', 'lopm.medicine_group_id', '=', 'pmg.id')
        ->join('medicine_groups', 'pmg.medicine_group_id', '=', 'medicine_groups.id')
        ->join('users', 'lopm.user_id', '=', 'users.id')
        ->select(
          'medicine_groups.group_name',
          DB::raw("TRIM(pmg.selling_price)+0 as each_price"),
          DB::raw("COUNT(lopm.medicine_group_id) as quantity"),
          DB::raw("TRIM(lopm.amount_discount)+0 as amount_discount"),
          DB::raw("TRIM(pmg.selling_price * COUNT(lopm.medicine_group_id))+0 as price_overall"),
          'users.fullname as created_by',
          DB::raw("DATE_FORMAT(lopm.created_at, '%d %b %Y') as created_at")
        )
        ->where('lopm.list_of_payment_id', '=', $check_list_of_payment->id)
        ->whereIn('lopm.medicine_group_id', $myArray_medicine_group_id)
        ->groupby('lopm.medicine_group_id')
        ->orderBy('lopm.id', 'desc')
        ->get();
    }

    $data_service = DB::table('list_of_payment_services')
      ->join('detail_service_patients', 'list_of_payment_services.detail_service_patient_id', '=', 'detail_service_patients.id')
      ->join('price_services', 'detail_service_patients.price_service_id', '=', 'price_services.id')
      ->join('list_of_services', 'price_services.list_of_services_id', '=', 'list_of_services.id')
      ->join('service_categories', 'list_of_services.service_category_id', '=', 'service_categories.id')
      ->join('users', 'detail_service_patients.user_id', '=', 'users.id')
      ->select(
        'list_of_services.service_name as item_name',
        'detail_service_patients.quantity',
        DB::raw("TRIM(price_services.selling_price)+0 as selling_price"),
        DB::raw("TRIM(list_of_payment_services.amount_discount)+0 as amount_discount"),
        DB::raw("TRIM(detail_service_patients.price_overall)+0 as price_overall")
      )
      ->whereIn('detail_service_patients.id', $myArray_service)
      ->get();

    $price_overall_service = DB::table('detail_service_patients')
      ->select(
        DB::raw("TRIM(SUM(detail_service_patients.price_overall))+0 as price_overall")
      )
      ->whereIn('detail_service_patients.id', $myArray_service)
      ->groupby('detail_service_patients.check_up_result_id')
      ->first();

    $discount_service = DB::table('list_of_payment_services')
      ->select(
        DB::raw("TRIM(SUM(list_of_payment_services.amount_discount))+0 as discount_service")
      )
      ->whereIn('detail_service_patient_id', $myArray_service)
      ->groupby('list_of_payment_services.check_up_result_id')
      ->first();

    if ($valid == true) {
      $price_overall_item = DB::table('list_of_payment_medicine_groups as lop')
        ->join('price_medicine_groups as pmg', 'lop.medicine_group_id', '=', 'pmg.id')
        ->join('detail_medicine_group_check_up_results as dmg', 'lop.detail_medicine_group_check_up_result_id', '=', 'dmg.id')
        ->select(
          DB::raw("TRIM(SUM(pmg.selling_price * dmg.quantity))+0 as price_overall")
        )
        ->where('lop.list_of_payment_id', '=', $check_list_of_payment->id)
        ->whereIn('lop.medicine_group_id', $myArray_medicine_group_id)
        ->first();
    } else {
      $price_overall_item = DB::table('list_of_payment_medicine_groups as lop')
        ->join('price_medicine_groups as pmg', 'lop.medicine_group_id', '=', 'pmg.id')
        ->select(
          DB::raw("TRIM(SUM(pmg.selling_price))+0 as price_overall")
        )
        ->where('lop.list_of_payment_id', '=', $check_list_of_payment->id)
        ->whereIn('lop.medicine_group_id', $myArray_medicine_group_id)
        ->first();
    }

    $discount_item = DB::table('list_of_payment_medicine_groups as lop')
      ->select(
        DB::raw("TRIM(SUM(lop.amount_discount))+0 as discount_item")
      )
      ->where('lop.list_of_payment_id', '=', $check_list_of_payment->id)
      ->whereIn('lop.medicine_group_id', $myArray_medicine_group_id)
      ->first();

    $price_service = 0;
    $price_item = 0;
    $price_petshop = 0;

    $res_discount_service = 0;

    if ($discount_service) {
      $res_discount_service = $discount_service->discount_service;
    }

    if ($price_overall_service) {
      $price_service = $price_overall_service->price_overall - $res_discount_service;
    }

    $res_discount_item = 0;

    if ($discount_item) {
      $res_discount_item = $discount_item->discount_item;
    }

    if ($price_overall_item) {
      $price_item = $price_overall_item->price_overall - $res_discount_item;
    }

    $address = DB::table('check_up_results')
      ->join('registrations', 'check_up_results.patient_registration_id', 'registrations.id')
      ->join('users', 'registrations.doctor_user_id', 'users.id')
      ->join('branches', 'users.branch_id', 'branches.id')
      ->select('branches.address')
      ->where('check_up_results.id', '=', $request->check_up_result_id)
      ->first();

    $data_patient = DB::table('check_up_results')
      ->join('registrations', 'check_up_results.patient_registration_id', 'registrations.id')
      ->join('patients', 'registrations.patient_id', 'patients.id')
      ->join('owners', 'patients.owner_id', '=', 'owners.id')
      ->join('users', 'registrations.doctor_user_id', 'users.id')
      ->join('branches', 'users.branch_id', 'branches.id')
      ->select(
        'registrations.id_number',
        'patients.id_member as id_patient',
        'pet_name',
        'owners.owner_phone_number',
        'branches.branch_name as clinic_name',
        'branches.address as address',
        'branches.payment_instruction as payment_instruction',
        DB::raw('(CASE WHEN patients.owner_name = "" THEN owners.owner_name ELSE patients.owner_name END) AS owner_name')
      )
      ->where('check_up_results.id', '=', $request->check_up_result_id)
      ->get();

    $data_cashier = DB::table('users')
      ->join('list_of_payments', 'users.id', 'list_of_payments.user_id')
      ->join('check_up_results', 'list_of_payments.check_up_result_id', 'check_up_results.id')
      ->select(
        'users.fullname as cashier_name',
        DB::raw("DATE_FORMAT(list_of_payments.updated_at, '%d %b %Y %H:%i:%s') as paid_time")
      )
      ->where('list_of_payments.check_up_result_id', '=', $request->check_up_result_id)
      ->first();

    $data_petshop = DB::table('payment_petshop_with_clinics as ppwc')
      ->join('price_item_pet_shops as pip', 'ppwc.price_item_pet_shop_id', 'pip.id')
      ->join('list_of_item_pet_shops as loi', 'pip.list_of_item_pet_shop_id', 'loi.id')
      ->select(
        'ppwc.total_item as quantity',
        'loi.item_name',
        DB::raw("TRIM(pip.selling_price)+0 as selling_price"),
        DB::raw("TRIM(pip.selling_price * ppwc.total_item)+0 as price_overall")
      )
      ->where('ppwc.list_of_payment_id', '=', $check_list_of_payment->id)
      ->get();

    foreach ($data_petshop as $dp) {
      $price_petshop += $dp->price_overall;
    }

    $price_overall = $price_service + $price_item + $price_petshop;

    $invoiceData = [
      'customer_name' => $data_patient[0]->owner_name,
      'customer_phone' => $data_patient[0]->owner_phone_number,

      // Detail Invoice (Mengganti 12345 dengan asumsi data yang logis)
      'no_invoice' => $data_patient[0]->id_number,
      'tanggal_invoice' => $data_cashier->paid_time,
      // 'subtotal' => 740000.00,
      'total' => $price_overall,

      // Items (Mengambil data dari tabel dan mengasumsikan Rp 12345 adalah contoh harga unit)
      'data_item' => $data_item,
      'data_service' => $data_service,
      'data_petshop' => $data_petshop,

      // Instruksi Bank
      // 'bank' => [
      //   'atas_nama' => 'udin',
      //   'nomor_rekening' => '1234567890', // Asumsi nomor rekening
      // ],
    ];

    $clinicData = [
      'name' => $data_patient[0]->clinic_name,
      'address' => $data_patient[0]->address,
      'payment_instruction' => $data_patient[0]->payment_instruction,
    ];

    $pdf = Pdf::loadView('invoice', compact('invoiceData', 'clinicData'));
    $filename = $data_patient[0]->id_number . "-" . $data_patient[0]->pet_name . ".pdf";
    return $pdf->download($filename);
    // return view('invoice', compact('invoiceData', 'clinicData'));
    // $pdf = PDF::loadview('invoice', $data);

    // return $pdf->download($data_patient[0]->id_number . ' - ' . $data_patient[0]->pet_name . '.pdf');
  }
}
