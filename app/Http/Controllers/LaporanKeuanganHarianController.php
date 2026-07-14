<?php

namespace App\Http\Controllers;

use App\Exports\LaporanKeuanganHarian;
use App\Models\Branch;
use App\Models\ListofPayments;
use Carbon\Carbon;
use DB;
use Excel;
use Illuminate\Http\Request;
use DateTime;

class LaporanKeuanganHarianController extends Controller
{
  public function index(Request $request)
  {
    // if ($request->user()->role == 'resepsionis') {
    //     return response()->json([
    //         'message' => 'The user role was invalid.',
    //         'errors' => ['Akses User tidak diizinkan!'],
    //     ], 403);
    // }

    $fdate = $request->date;
    $tdate = new Carbon('2022-02-14');
    $diff = $tdate->diffInDays($fdate, false);

    if ($diff > 0) {

      $items_per_page = 50;

      $page = $request->page;

      $item = DB::table('list_of_payments as lop')
        ->join('check_up_results as cur', 'lop.check_up_result_id', '=', 'cur.id')
        ->join('list_of_payment_medicine_groups as lopm', 'lopm.list_of_payment_id', '=', 'lop.id')
        ->join('price_medicine_groups as pmg', 'lopm.medicine_group_id', '=', 'pmg.id')
        ->join('registrations as reg', 'cur.patient_registration_id', '=', 'reg.id')
        ->join('patients as pa', 'reg.patient_id', '=', 'pa.id')
        ->join('users', 'lop.user_id', '=', 'users.id')
        ->join('branches', 'users.branch_id', '=', 'branches.id')

        ->select(
          'lop.id as list_of_payment_id',
          'lop.check_up_result_id as check_up_result_id',
          'reg.id_number as registration_number',
          'pa.id_member as patient_number',
          'pa.pet_category',
          'pa.pet_name',
          'reg.complaint',
          'cur.status_outpatient_inpatient',
          DB::raw("TRIM(SUM(pmg.selling_price * lopm.quantity))+0 as price_overall"),
          DB::raw("TRIM(SUM(pmg.capital_price * lopm.quantity))+0 as capital_price"),
          DB::raw("TRIM(SUM(pmg.doctor_fee * lopm.quantity))+0 as doctor_fee"),
          DB::raw("TRIM(SUM(pmg.petshop_fee * lopm.quantity))+0 as petshop_fee"),
          DB::raw("TRIM(SUM(lopm.amount_discount))+0 as amount_discount"),
          DB::raw("TRIM(SUM(pmg.doctor_fee * lopm.quantity) - SUM(lopm.amount_discount))+0 as fee_doctor_after_discount"),
          'users.fullname as created_by',
          'lop.created_at as created_at',
          'branches.id as branchId'
        );

      if ($request->date) {

        $item = $item->where(DB::raw('DATE(lopm.updated_at)'), '=', $request->date);
      }
      $item = $item->groupBy('lop.check_up_result_id');

      $service = DB::table('list_of_payments')
        ->join('check_up_results', 'list_of_payments.check_up_result_id', '=', 'check_up_results.id')
        ->join('list_of_payment_services', 'check_up_results.id', '=', 'list_of_payment_services.check_up_result_id')
        ->join('detail_service_patients', 'list_of_payment_services.detail_service_patient_id', '=', 'detail_service_patients.id')
        ->join('price_services', 'detail_service_patients.price_service_id', '=', 'price_services.id')
        ->join('registrations', 'check_up_results.patient_registration_id', '=', 'registrations.id')
        ->join('patients', 'registrations.patient_id', '=', 'patients.id')
        ->join('users', 'check_up_results.user_id', '=', 'users.id')
        ->join('branches', 'users.branch_id', '=', 'branches.id')

        ->select(
          'list_of_payments.id as list_of_payment_id',
          'list_of_payments.check_up_result_id as check_up_result_id',
          'registrations.id_number as registration_number',
          'patients.id_member as patient_number',
          'patients.pet_category',
          'patients.pet_name',
          'registrations.complaint',
          'check_up_results.status_outpatient_inpatient',
          DB::raw("TRIM(SUM(detail_service_patients.price_overall))+0 as price_overall"),
          DB::raw("TRIM(SUM(price_services.capital_price * detail_service_patients.quantity))+0 as capital_price"),
          DB::raw("TRIM(SUM(price_services.doctor_fee * detail_service_patients.quantity))+0 as doctor_fee"),
          DB::raw("TRIM(SUM(price_services.petshop_fee * detail_service_patients.quantity))+0 as petshop_fee"),
          DB::raw("TRIM(SUM(list_of_payment_services.amount_discount))+0 as amount_discount"),
          DB::raw("TRIM(SUM(price_services.doctor_fee * detail_service_patients.quantity) - SUM(list_of_payment_services.amount_discount))+0 as fee_doctor_after_discount"),
          'users.fullname as created_by',
          'list_of_payment_services.created_at as created_at',
          'branches.id as branchId'
        );
      if ($request->date) {

        $service = $service->where(DB::raw('DATE(list_of_payment_services.updated_at)'), '=', $request->date);
      }

      $service = $service->groupBy('list_of_payments.check_up_result_id')
        ->union($item);

      $data = DB::query()->fromSub($service, 'p_pn')
        ->select(
          'list_of_payment_id',
          'check_up_result_id',
          'registration_number',
          'patient_number',
          'pet_category',
          'pet_name',
          'complaint',
          'status_outpatient_inpatient',
          DB::raw("TRIM(SUM(price_overall))+0 as price_overall"),
          DB::raw("TRIM(SUM(capital_price))+0 as capital_price"),
          DB::raw("TRIM(SUM(doctor_fee))+0 as doctor_fee"),
          DB::raw("TRIM(SUM(petshop_fee))+0 as petshop_fee"),
          DB::raw("TRIM(SUM(amount_discount))+0 as amount_discount"),
          DB::raw("TRIM(SUM(fee_doctor_after_discount))+0 as fee_doctor_after_discount"),
          'created_by',
          DB::raw("DATE_FORMAT(created_at, '%d %b %Y') as created_at")
        );

      if ($request->branch_id && $request->user()->role == 'admin') {
        $data = $data->where('branchId', '=', $request->branch_id);
      } else {
        $data = $data->where('branchId', '=', $request->user()->branch_id);
      }

      if ($request->orderby) {

        $data = $data->orderBy($request->column, $request->orderby);
      } else {
        $data = $data->orderBy('list_of_payment_id', 'desc');
      }



      // Count once and reuse below instead of re-running the same COUNT query twice.
      $mainDataCount = $data->groupBy('check_up_result_id')->count();

      if ($mainDataCount == 0) {

        $data = DB::table('payment_petshops as pp')
          ->join('master_payment_petshops as mpp', 'mpp.id', 'pp.master_payment_petshop_id')
          ->join('price_item_pet_shops as pi', 'pi.id', 'pp.price_item_pet_shop_id')
          // ->join('price_item_pet_shops as pi', 'pi.list_of_item_pet_shop_id', 'loi.id')
          ->join('users as u', 'u.id', 'pp.user_id')
          ->join('branches as b', 'b.id', 'mpp.branch_id')
          ->select(
            'pp.id as list_of_payment_id',
            DB::raw("'-' as check_up_result_id"),
            //'list_of_payments.check_up_result_id as check_up_result_id',

            DB::raw("'-' as registration_number"),
            DB::raw("'-' as patient_number"),
            DB::raw("'-' as pet_category"),
            DB::raw("'-' as pet_name"),
            DB::raw("'-' as complaint"),
            DB::raw("'-' as status_outpatient_inpatient"),
            // 'mpp.payment_number as registration_number',
            // 'patients.id_member as patient_number',
            // 'patients.pet_category',
            // 'patients.pet_name',
            // 'registrations.complaint',
            // 'check_up_results.status_outpatient_inpatient',
            DB::raw("TRIM(SUM(pi.selling_price * pp.total_item))+0 as price_overall"),

            DB::raw("TRIM(SUM(pi.capital_price * pp.total_item))+0 as capital_price"),
            DB::raw("0 as doctor_fee"),
            // DB::raw("TRIM(SUM(price_services.capital_price * detail_service_patients.quantity))+0 as capital_price"),
            // DB::raw("TRIM(SUM(price_services.doctor_fee * detail_service_patients.quantity))+0 as doctor_fee"),

            // DB::raw("TRIM(SUM(price_services.petshop_fee * detail_service_patients.quantity))+0 as petshop_fee"),
            // DB::raw("TRIM(SUM(list_of_payment_services.amount_discount))+0 as amount_discount"),

            DB::raw("0 as petshop_fee"),
            DB::raw("0 as amount_discount"),

            // DB::raw("TRIM(SUM(price_services.doctor_fee * detail_service_patients.quantity) - SUM(list_of_payment_services.amount_discount))+0 as fee_doctor_after_discount"),

            DB::raw("0 as fee_doctor_after_discount"),

            'u.fullname as created_by',
            DB::raw("DATE_FORMAT(pp.created_at, '%d %b %Y') as created_at"),
            'b.id as branchId'
          );

        if ($request->date) {

          $data = $data->where(DB::raw('DATE(pp.updated_at)'), '=', $request->date);
        }

        $count_data = $data->groupBy('mpp.id')->count();

        $offset = ($page - 1) * $items_per_page;

        $count_result = $count_data - $offset;

        if ($count_result < 0) {
          $data = $data->groupBy('mpp.id')->offset(0)->limit($items_per_page)->get();
        } else {
          $data = $data->groupBy('mpp.id')->offset($offset)->limit($items_per_page)->get();
        }

        $total_paging = $count_data / $items_per_page;

      } else {

        $count_data = $mainDataCount;

        $offset = ($page - 1) * $items_per_page;

        $count_result = $count_data - $offset;

        if ($count_result < 0) {
          $data = $data->groupBy('check_up_result_id')->offset(0)->limit($items_per_page)->get();
        } else {
          $data = $data->groupBy('check_up_result_id')->offset($offset)->limit($items_per_page)->get();
        }

        $total_paging = $count_data / $items_per_page;
      }

      // resolve branch id once for all aggregate queries
      $branchId = ($request->branch_id && $request->user()->role == 'admin')
        ? $request->branch_id
        : $request->user()->branch_id;

      // medicine aggregates: selling price, capital, doctor_fee, petshop_fee in 1 query
      $medicine_agg = DB::table('list_of_payments as lop')
        ->join('check_up_results as cur', 'lop.check_up_result_id', '=', 'cur.id')
        ->join('list_of_payment_medicine_groups as lopm', 'lopm.list_of_payment_id', '=', 'lop.id')
        ->join('price_medicine_groups as pmg', 'lopm.medicine_group_id', '=', 'pmg.id')
        ->join('registrations as reg', 'cur.patient_registration_id', '=', 'reg.id')
        ->join('patients as pa', 'reg.patient_id', '=', 'pa.id')
        ->join('users', 'lop.user_id', '=', 'users.id')
        ->join('branches', 'users.branch_id', '=', 'branches.id')
        ->select(
          DB::raw("(CASE WHEN lopm.quantity = 0 THEN TRIM(SUM(pmg.selling_price)) ELSE TRIM(SUM(pmg.selling_price * lopm.quantity)) END)+0 as price_overall"),
          DB::raw("(CASE WHEN lopm.quantity = 0 THEN TRIM(SUM(pmg.capital_price)) ELSE TRIM(SUM(pmg.capital_price * lopm.quantity)) END)+0 as capital_price"),
          DB::raw("(CASE WHEN lopm.quantity = 0 THEN TRIM(SUM(pmg.doctor_fee)) ELSE TRIM(SUM(pmg.doctor_fee * lopm.quantity)) END)+0 as doctor_fee"),
          DB::raw("(CASE WHEN lopm.quantity = 0 THEN TRIM(SUM(pmg.petshop_fee)) ELSE TRIM(SUM(pmg.petshop_fee * lopm.quantity)) END)+0 as petshop_fee")
        )
        ->where('branches.id', '=', $branchId);
      if ($request->date) {
        $medicine_agg = $medicine_agg->where(DB::raw('DATE(lopm.updated_at)'), '=', $request->date);
      }
      $medicine_agg = $medicine_agg->first();

      // medicine amount_discount: kept separate — uses different branch filter for 'dokter' role
      $amount_discount_item = DB::table('list_of_payments as lop')
        ->join('check_up_results as cur', 'lop.check_up_result_id', '=', 'cur.id')
        ->join('list_of_payment_medicine_groups as lopm', 'lopm.list_of_payment_id', '=', 'lop.id')
        ->join('price_medicine_groups as pmg', 'lopm.medicine_group_id', '=', 'pmg.id')
        ->join('registrations as reg', 'cur.patient_registration_id', '=', 'reg.id')
        ->join('patients as pa', 'reg.patient_id', '=', 'pa.id')
        ->join('users', 'lop.user_id', '=', 'users.id')
        ->join('branches', 'users.branch_id', '=', 'branches.id')
        ->select(DB::raw("TRIM(SUM(lopm.amount_discount))+0 as amount_discount"));
      if ($request->branch_id && $request->user()->role == 'admin') {
        $amount_discount_item = $amount_discount_item->where('branches.id', '=', $request->branch_id);
      } elseif ($request->user()->role == 'dokter') {
        $amount_discount_item = $amount_discount_item->where('branches.id', '=', $request->user()->branch_id);
      }
      if ($request->date) {
        $amount_discount_item = $amount_discount_item->where(DB::raw('DATE(lopm.updated_at)'), '=', $request->date);
      }
      $amount_discount_item = $amount_discount_item->first();

      // service aggregates: 5 metrics in 1 query
      $service_agg = DB::table('list_of_payments')
        ->join('check_up_results', 'list_of_payments.check_up_result_id', '=', 'check_up_results.id')
        ->join('list_of_payment_services', 'check_up_results.id', '=', 'list_of_payment_services.check_up_result_id')
        ->join('detail_service_patients', 'list_of_payment_services.detail_service_patient_id', '=', 'detail_service_patients.id')
        ->join('price_services', 'detail_service_patients.price_service_id', '=', 'price_services.id')
        ->join('users', 'check_up_results.user_id', '=', 'users.id')
        ->join('branches', 'users.branch_id', '=', 'branches.id')
        ->select(
          DB::raw("TRIM(SUM(detail_service_patients.price_overall))+0 as price_overall"),
          DB::raw("TRIM(SUM(price_services.capital_price * detail_service_patients.quantity))+0 as capital_price"),
          DB::raw("TRIM(SUM(price_services.doctor_fee * detail_service_patients.quantity))+0 as doctor_fee"),
          DB::raw("TRIM(SUM(price_services.petshop_fee * detail_service_patients.quantity))+0 as petshop_fee"),
          DB::raw("TRIM(SUM(list_of_payment_services.amount_discount))+0 as amount_discount")
        )
        ->where('branches.id', '=', $branchId);
      if ($request->date) {
        $service_agg = $service_agg->where(DB::raw('DATE(list_of_payment_services.updated_at)'), '=', $request->date);
      }
      $service_agg = $service_agg->first();

      // petshop_with_clinics: price, capital, profit in 1 query
      $petshop_clinic_agg = DB::table('payment_petshop_with_clinics as ppwc')
        ->join('price_item_pet_shops as pip', 'ppwc.price_item_pet_shop_id', 'pip.id')
        ->join('users', 'ppwc.user_id', '=', 'users.id')
        ->join('branches', 'users.branch_id', '=', 'branches.id')
        ->select(
          DB::raw("TRIM(SUM(pip.selling_price * ppwc.total_item))+0 as price_overall"),
          DB::raw("TRIM(SUM(pip.capital_price * ppwc.total_item))+0 as capital_price"),
          DB::raw("TRIM(SUM(pip.profit * ppwc.total_item))+0 as profit")
        )
        ->where('branches.id', '=', $branchId);
      if ($request->date) {
        $petshop_clinic_agg = $petshop_clinic_agg->where(DB::raw('DATE(ppwc.created_at)'), '=', $request->date);
      }
      $petshop_clinic_agg = $petshop_clinic_agg->first();

      // payment_petshops: price, capital, profit in 1 query
      $petshop_agg = DB::table('payment_petshops as pp')
        ->join('price_item_pet_shops as pip', 'pp.price_item_pet_shop_id', 'pip.id')
        ->join('users', 'pp.user_id', '=', 'users.id')
        ->join('branches', 'users.branch_id', '=', 'branches.id')
        ->select(
          DB::raw("TRIM(SUM(pip.selling_price * pp.total_item))+0 as price_overall"),
          DB::raw("TRIM(SUM(pip.capital_price * pp.total_item))+0 as capital_price"),
          DB::raw("TRIM(SUM(pip.profit * pp.total_item))+0 as profit")
        )
        ->where('branches.id', '=', $branchId);
      if ($request->date) {
        $petshop_agg = $petshop_agg->where(DB::raw('DATE(pp.created_at)'), '=', $request->date);
      }
      $petshop_agg = $petshop_agg->first();

      $price_overall   = $service_agg->price_overall + $medicine_agg->price_overall
        + $petshop_clinic_agg->price_overall + $petshop_agg->price_overall;
      $capital_price   = $service_agg->capital_price + $medicine_agg->capital_price
        + $petshop_clinic_agg->capital_price + $petshop_agg->capital_price;
      $doctor_fee      = $medicine_agg->doctor_fee + $service_agg->doctor_fee;
      $petshop_fee     = $medicine_agg->petshop_fee + $service_agg->petshop_fee;
      $amount_discount = $amount_discount_item->amount_discount + $service_agg->amount_discount;

      $expenses = DB::table('expenses as e')
        ->join('users as u', 'e.user_id_spender', '=', 'u.id')
        ->join('branches as b', 'u.branch_id', '=', 'b.id')
        ->select(DB::raw("TRIM(SUM(IFNULL(e.amount_overall,0)))+0 as amount_overall"));

      if ($request->branch_id && $request->user()->role == 'admin') {
        $expenses = $expenses->where('b.id', '=', $request->branch_id);
      } else {
        $expenses = $expenses->where('b.id', '=', $request->user()->branch_id);
      }

      if ($request->date) {
        $expenses = $expenses->where(DB::raw('DATE(e.date_spend)'), '=', $request->date);
      }

      $expenses = $expenses->first();

      $total_expenses = 0;

      if (!is_null($expenses->amount_overall)) {

        $total_expenses = $expenses->amount_overall;
      }

      $net_profit = $doctor_fee - $total_expenses + $petshop_clinic_agg->profit + $petshop_agg->profit;

      return response()->json([
        'data' => $data,
        'price_overall' => $price_overall,
        'capital_price' => $capital_price,
        'doctor_fee' => $doctor_fee,
        'petshop_fee' => $petshop_fee,
        'amount_discount' => $amount_discount,
        'expenses' => $total_expenses,
        'net_profit' => $net_profit,
        'total_paging' => ceil($total_paging),
      ], 200);
    } else {

      $items_per_page = 50;

      $page = $request->page;

      $item = DB::table('list_of_payments as lop')
        ->join('check_up_results as cur', 'lop.check_up_result_id', '=', 'cur.id')
        ->join('list_of_payment_medicine_groups as lopm', 'lopm.list_of_payment_id', '=', 'lop.id')
        ->join('price_medicine_groups as pmg', 'lopm.medicine_group_id', '=', 'pmg.id')
        ->join('registrations as reg', 'cur.patient_registration_id', '=', 'reg.id')
        ->join('patients as pa', 'reg.patient_id', '=', 'pa.id')
        ->join('users', 'lop.user_id', '=', 'users.id')
        ->join('branches', 'users.branch_id', '=', 'branches.id')

        ->select(
          'lop.id as list_of_payment_id',
          'lop.check_up_result_id as check_up_result_id',
          'reg.id_number as registration_number',
          'pa.id_member as patient_number',
          'pa.pet_category',
          'pa.pet_name',
          'reg.complaint',
          'cur.status_outpatient_inpatient',
          DB::raw("(CASE WHEN lopm.quantity = 0 THEN TRIM(SUM(pmg.selling_price)) ELSE TRIM(SUM(pmg.selling_price * lopm.quantity)) END)+0 as price_overall"),
          DB::raw("(CASE WHEN lopm.quantity = 0 THEN TRIM(SUM(pmg.capital_price)) ELSE TRIM(SUM(pmg.capital_price * lopm.quantity)) END)+0 as capital_price"),
          DB::raw("(CASE WHEN lopm.quantity = 0 THEN TRIM(SUM(pmg.doctor_fee)) ELSE TRIM(SUM(pmg.doctor_fee * lopm.quantity)) END)+0 as doctor_fee"),
          DB::raw("(CASE WHEN lopm.quantity = 0 THEN TRIM(SUM(pmg.petshop_fee)) ELSE TRIM(SUM(pmg.petshop_fee * lopm.quantity)) END)+0 as petshop_fee"),
          DB::raw("TRIM(SUM(lopm.amount_discount))+0 as amount_discount"),
          DB::raw("TRIM(CASE WHEN lopm.quantity = 0 THEN TRIM(SUM(pmg.doctor_fee)) ELSE TRIM(SUM(pmg.doctor_fee * lopm.quantity)) END - SUM(lopm.amount_discount))+0 as fee_doctor_after_discount"),
          'users.fullname as created_by',
          'lop.created_at as created_at',
          'branches.id as branchId'
        );

      if ($request->date) {

        $item = $item->where(DB::raw('DATE(lopm.updated_at)'), '=', $request->date);
      }
      $item = $item->groupBy('lop.check_up_result_id');

      $service = DB::table('list_of_payments')
        ->join('check_up_results', 'list_of_payments.check_up_result_id', '=', 'check_up_results.id')
        ->join('list_of_payment_services', 'check_up_results.id', '=', 'list_of_payment_services.check_up_result_id')
        ->join('detail_service_patients', 'list_of_payment_services.detail_service_patient_id', '=', 'detail_service_patients.id')
        ->join('price_services', 'detail_service_patients.price_service_id', '=', 'price_services.id')
        ->join('registrations', 'check_up_results.patient_registration_id', '=', 'registrations.id')
        ->join('patients', 'registrations.patient_id', '=', 'patients.id')
        ->join('users', 'check_up_results.user_id', '=', 'users.id')
        ->join('branches', 'users.branch_id', '=', 'branches.id')

        ->select(
          'list_of_payments.id as list_of_payment_id',
          'list_of_payments.check_up_result_id as check_up_result_id',
          'registrations.id_number as registration_number',
          'patients.id_member as patient_number',
          'patients.pet_category',
          'patients.pet_name',
          'registrations.complaint',
          'check_up_results.status_outpatient_inpatient',
          DB::raw("TRIM(SUM(detail_service_patients.price_overall))+0 as price_overall"),
          DB::raw("TRIM(SUM(price_services.capital_price * detail_service_patients.quantity))+0 as capital_price"),
          DB::raw("TRIM(SUM(price_services.doctor_fee * detail_service_patients.quantity))+0 as doctor_fee"),
          DB::raw("TRIM(SUM(price_services.petshop_fee * detail_service_patients.quantity))+0 as petshop_fee"),
          DB::raw("TRIM(SUM(list_of_payment_services.amount_discount))+0 as amount_discount"),
          DB::raw("TRIM(SUM(price_services.doctor_fee * detail_service_patients.quantity) - SUM(list_of_payment_services.amount_discount))+0 as fee_doctor_after_discount"),
          'users.fullname as created_by',
          'list_of_payment_services.created_at as created_at',
          'branches.id as branchId'
        );
      if ($request->date) {

        $service = $service->where(DB::raw('DATE(list_of_payment_services.updated_at)'), '=', $request->date);
      }

      $service = $service->groupBy('list_of_payments.check_up_result_id')
        ->union($item);

      $data = DB::query()->fromSub($service, 'p_pn')
        ->select(
          'list_of_payment_id',
          'check_up_result_id',
          'registration_number',
          'patient_number',
          'pet_category',
          'pet_name',
          'complaint',
          'status_outpatient_inpatient',
          DB::raw("TRIM(SUM(price_overall))+0 as price_overall"),
          DB::raw("TRIM(SUM(capital_price))+0 as capital_price"),
          DB::raw("TRIM(SUM(doctor_fee))+0 as doctor_fee"),
          DB::raw("TRIM(SUM(petshop_fee))+0 as petshop_fee"),
          DB::raw("TRIM(SUM(amount_discount))+0 as amount_discount"),
          DB::raw("TRIM(SUM(fee_doctor_after_discount))+0 as fee_doctor_after_discount"),
          'created_by',
          DB::raw("DATE_FORMAT(created_at, '%d %b %Y') as created_at")
        );

      if ($request->branch_id && $request->user()->role == 'admin') {
        $data = $data->where('branchId', '=', $request->branch_id);
      } else {
        $data = $data->where('branchId', '=', $request->user()->branch_id);
      }

      if ($request->orderby) {

        $data = $data->orderBy($request->column, $request->orderby);
      } else {
        $data = $data->orderBy('list_of_payment_id', 'desc');
      }

      $count_data = $data->groupBy('check_up_result_id')->count();

      $offset = ($page - 1) * $items_per_page;

      $count_result = $count_data - $offset;

      if ($count_result < 0) {
        $data = $data->groupBy('check_up_result_id')->offset(0)->limit($items_per_page)->get();
      } else {
        $data = $data->groupBy('check_up_result_id')->offset($offset)->limit($items_per_page)->get();
      }

      $total_paging = $count_data / $items_per_page;

      // resolve branch id once for all aggregate queries
      $branchId = ($request->branch_id && $request->user()->role == 'admin')
        ? $request->branch_id
        : $request->user()->branch_id;

      // medicine aggregates: 5 metrics in 1 query
      $medicine_agg = DB::table('list_of_payments as lop')
        ->join('check_up_results as cur', 'lop.check_up_result_id', '=', 'cur.id')
        ->join('list_of_payment_medicine_groups as lopm', 'lopm.list_of_payment_id', '=', 'lop.id')
        ->join('price_medicine_groups as pmg', 'lopm.medicine_group_id', '=', 'pmg.id')
        ->join('registrations as reg', 'cur.patient_registration_id', '=', 'reg.id')
        ->join('patients as pa', 'reg.patient_id', '=', 'pa.id')
        ->join('users', 'lop.user_id', '=', 'users.id')
        ->join('branches', 'users.branch_id', '=', 'branches.id')
        ->select(
          DB::raw("(CASE WHEN lopm.quantity = 0 THEN TRIM(SUM(pmg.selling_price)) ELSE TRIM(SUM(pmg.selling_price * lopm.quantity)) END)+0 as price_overall"),
          DB::raw("(CASE WHEN lopm.quantity = 0 THEN TRIM(SUM(pmg.capital_price)) ELSE TRIM(SUM(pmg.capital_price * lopm.quantity)) END)+0 as capital_price"),
          DB::raw("(CASE WHEN lopm.quantity = 0 THEN TRIM(SUM(pmg.doctor_fee)) ELSE TRIM(SUM(pmg.doctor_fee * lopm.quantity)) END)+0 as doctor_fee"),
          DB::raw("(CASE WHEN lopm.quantity = 0 THEN TRIM(SUM(pmg.petshop_fee)) ELSE TRIM(SUM(pmg.petshop_fee * lopm.quantity)) END)+0 as petshop_fee"),
          DB::raw("TRIM(SUM(lopm.amount_discount))+0 as amount_discount")
        )
        ->where('branches.id', '=', $branchId);
      if ($request->date) {
        $medicine_agg = $medicine_agg->where(DB::raw('DATE(lopm.updated_at)'), '=', $request->date);
      }
      $medicine_agg = $medicine_agg->first();

      // service aggregates: 5 metrics in 1 query
      $service_agg = DB::table('list_of_payments')
        ->join('check_up_results', 'list_of_payments.check_up_result_id', '=', 'check_up_results.id')
        ->join('list_of_payment_services', 'check_up_results.id', '=', 'list_of_payment_services.check_up_result_id')
        ->join('detail_service_patients', 'list_of_payment_services.detail_service_patient_id', '=', 'detail_service_patients.id')
        ->join('price_services', 'detail_service_patients.price_service_id', '=', 'price_services.id')
        ->join('users', 'check_up_results.user_id', '=', 'users.id')
        ->join('branches', 'users.branch_id', '=', 'branches.id')
        ->select(
          DB::raw("TRIM(SUM(detail_service_patients.price_overall))+0 as price_overall"),
          DB::raw("TRIM(SUM(price_services.capital_price * detail_service_patients.quantity))+0 as capital_price"),
          DB::raw("TRIM(SUM(price_services.doctor_fee * detail_service_patients.quantity))+0 as doctor_fee"),
          DB::raw("TRIM(SUM(price_services.petshop_fee * detail_service_patients.quantity))+0 as petshop_fee"),
          DB::raw("TRIM(SUM(list_of_payment_services.amount_discount))+0 as amount_discount")
        )
        ->where('branches.id', '=', $branchId);
      if ($request->date) {
        $service_agg = $service_agg->where(DB::raw('DATE(list_of_payment_services.updated_at)'), '=', $request->date);
      }
      $service_agg = $service_agg->first();

      $price_overall   = $service_agg->price_overall + $medicine_agg->price_overall;
      $capital_price   = $service_agg->capital_price + $medicine_agg->capital_price;
      $doctor_fee      = $medicine_agg->doctor_fee + $service_agg->doctor_fee;
      $petshop_fee     = $medicine_agg->petshop_fee + $service_agg->petshop_fee;
      $amount_discount = $medicine_agg->amount_discount + $service_agg->amount_discount;

      $expenses = DB::table('expenses as e')
        ->join('users as u', 'e.user_id_spender', '=', 'u.id')
        ->join('branches as b', 'u.branch_id', '=', 'b.id')
        ->select(DB::raw("TRIM(SUM(IFNULL(e.amount_overall,0)))+0 as amount_overall"));

      if ($request->branch_id && $request->user()->role == 'admin') {
        $expenses = $expenses->where('b.id', '=', $request->branch_id);
      } else {
        $expenses = $expenses->where('b.id', '=', $request->user()->branch_id);
      }

      if ($request->date) {
        $expenses = $expenses->where(DB::raw('DATE(e.date_spend)'), '=', $request->date);
      }

      $expenses = $expenses->first();

      $total_expenses = 0;

      if (!is_null($expenses->amount_overall)) {

        $total_expenses = $expenses->amount_overall;
      }

      $net_profit = $doctor_fee - $total_expenses;

      return response()->json([
        'data' => $data,
        'price_overall' => $price_overall,
        'capital_price' => $capital_price,
        'doctor_fee' => $doctor_fee,
        'petshop_fee' => $petshop_fee,
        'amount_discount' => $amount_discount,
        'expenses' => $total_expenses,
        'net_profit' => $net_profit,
        'total_paging' => ceil($total_paging),
      ], 200);
    }
  }

  public function detail(Request $request)
  {
    // if ($request->user()->role == 'resepsionis') {
    //     return response()->json([
    //         'message' => 'The user role was invalid.',
    //         'errors' => ['Akses User tidak diizinkan!'],
    //     ], 403);
    // }

    $data = ListofPayments::find($request->id);

    if (is_null($data)) {

      return response()->json([
        'message' => 'The data was invalid.',
        'errors' => ['Data Hasil Pemeriksaan tidak ditemukan!'],
      ], 404);
    }

    $user = DB::table('list_of_payments')
      ->join('users', 'list_of_payments.user_id', '=', 'users.id')
      ->select('users.id as user_id', 'users.fullname as fullname')
      ->where('users.id', '=', $data->user_id)
      ->first();

    $data->user = $user;

    $check_up_result = DB::table('check_up_results')
      ->where('id', '=', $data->check_up_result_id)
      ->first();

    $data->check_up_result = $check_up_result;

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
        DB::raw('COALESCE(registrations.pet_year_age, patients.pet_year_age) as pet_year_age'),
        DB::raw('COALESCE(registrations.pet_month_age, patients.pet_month_age) as pet_month_age'),
        DB::raw('(CASE WHEN patients.owner_name = "" THEN owners.owner_name ELSE patients.owner_name END) AS owner_name'),
        DB::raw('(CASE WHEN patients.owner_address = "" THEN owners.owner_address ELSE patients.owner_address END) AS owner_address'),
        DB::raw('(CASE WHEN patients.owner_phone_number = "" THEN owners.owner_phone_number ELSE patients.owner_phone_number END) AS owner_phone_number'),
        'registrations.complaint',
        'registrations.registrant'
      )
      ->where('registrations.id', '=', $check_up_result->patient_registration_id)
      ->first();

    $data->registration = $registration;

    $list_of_payment_services = DB::table('list_of_payment_services')
      ->join('detail_service_patients', 'list_of_payment_services.detail_service_patient_id', '=', 'detail_service_patients.id')
      ->join('price_services', 'detail_service_patients.price_service_id', '=', 'price_services.id')
      ->join('list_of_services', 'price_services.list_of_services_id', '=', 'list_of_services.id')
      ->join('service_categories', 'list_of_services.service_category_id', '=', 'service_categories.id')
      ->join('users', 'detail_service_patients.user_id', '=', 'users.id')
      ->select(
        'list_of_payment_services.id as id',
        'detail_service_patients.id as detail_service_patient_id',
        'price_services.id as price_service_id',
        'list_of_services.id as list_of_service_id',
        'list_of_services.service_name',
        'detail_service_patients.quantity',
        'service_categories.category_name',
        DB::raw("TRIM(detail_service_patients.price_overall )+0 as price_overall"),
        DB::raw("TRIM(price_services.selling_price)+0 as selling_price"),
        DB::raw("TRIM(price_services.capital_price * detail_service_patients.quantity)+0 as capital_price"),
        DB::raw("TRIM(price_services.doctor_fee * detail_service_patients.quantity)+0 as doctor_fee"),
        DB::raw("TRIM(price_services.petshop_fee * detail_service_patients.quantity)+0 as petshop_fee"),
        DB::raw("TRIM(list_of_payment_services.discount)+0 as discount"),
        DB::raw("TRIM(list_of_payment_services.amount_discount)+0 as amount_discount"),
        DB::raw("TRIM((price_services.doctor_fee * detail_service_patients.quantity) - list_of_payment_services.amount_discount)+0 as fee_doctor_after_discount"),
        'users.fullname as created_by',
        DB::raw("DATE_FORMAT(list_of_payment_services.updated_at, '%d %b %Y') as created_at")
      )
      ->where('list_of_payment_services.check_up_result_id', '=', $data->check_up_result_id);

    if ($request->date) {

      $list_of_payment_services = $list_of_payment_services->where(DB::raw('DATE(list_of_payment_services.updated_at)'), '=', $request->date);
    }

    $list_of_payment_services = $list_of_payment_services->orderBy('list_of_payment_services.id', 'desc')
      ->get();

    $data['list_of_payment_services'] = $list_of_payment_services;

    $item = DB::table('list_of_payment_medicine_groups as lopm')
      ->join('price_medicine_groups as pmg', 'lopm.medicine_group_id', '=', 'pmg.id')
      ->join('medicine_groups', 'pmg.medicine_group_id', '=', 'medicine_groups.id')
      ->join('branches', 'medicine_groups.branch_id', '=', 'branches.id')
      ->join('users', 'lopm.user_id', '=', 'users.id')
      ->select(
        'lopm.id as id',
        'pmg.id as price_medicine_group_id',
        DB::raw("TRIM(pmg.selling_price)+0 as selling_price"),
        DB::raw("TRIM(pmg.selling_price * lopm.quantity)+0 as price_overall"),
        'lopm.medicine_group_id as medicine_group_id',
        'lopm.quantity as quantity',
        DB::raw("TRIM(pmg.selling_price)+0 as selling_price"),
        DB::raw("TRIM(pmg.capital_price)+0 as capital_price"),
        DB::raw("TRIM(pmg.doctor_fee)+0 as doctor_fee"),
        DB::raw("TRIM(pmg.petshop_fee)+0 as petshop_fee"),
        DB::raw("TRIM(lopm.discount)+0 as discount"),
        DB::raw("TRIM(lopm.amount_discount)+0 as amount_discount"),
        DB::raw("TRIM(pmg.doctor_fee - lopm.amount_discount)+0 as fee_doctor_after_discount"),
        'medicine_groups.group_name',
        'branches.id as branch_id',
        'branches.branch_name',
        'users.fullname as created_by',
        DB::raw("DATE_FORMAT(lopm.created_at, '%d %b %Y') as created_at")
      )
      ->where('lopm.list_of_payment_id', '=', $data->id);
    if ($request->date) {

      $item = $item->where(DB::raw('DATE(lopm.updated_at)'), '=', $request->date);
    }
    $item = $item->get();

    $groupIds = $item->pluck('id')->toArray();
    $all_detail_items = DB::table('list_of_payment_items as lopi')
      ->join('price_items', 'lopi.price_item_id', '=', 'price_items.id')
      ->join('list_of_items', 'price_items.list_of_items_id', '=', 'list_of_items.id')
      ->join('category_item', 'list_of_items.category_item_id', '=', 'category_item.id')
      ->join('unit_item', 'list_of_items.unit_item_id', '=', 'unit_item.id')
      ->join('users', 'lopi.user_id', '=', 'users.id')
      ->select(
        'lopi.id as detail_item_patients_id',
        'lopi.list_of_payment_medicine_group_id',
        'list_of_items.id as list_of_item_id',
        'price_items.id as price_item_id',
        'list_of_items.item_name',
        'lopi.quantity',
        DB::raw("TRIM(lopi.price_overall)+0 as price_overall"),
        'unit_item.unit_name',
        'category_item.category_name',
        DB::raw("TRIM(price_items.selling_price)+0 as selling_price"),
        DB::raw("TRIM(price_items.capital_price)+0 as capital_price"),
        DB::raw("TRIM(price_items.doctor_fee)+0 as doctor_fee"),
        DB::raw("TRIM(price_items.petshop_fee)+0 as petshop_fee"),
        'users.fullname as created_by',
        DB::raw("DATE_FORMAT(lopi.created_at, '%d %b %Y') as created_at")
      )
      ->whereIn('lopi.list_of_payment_medicine_group_id', $groupIds)
      ->orderBy('lopi.id', 'asc')
      ->get()
      ->groupBy('list_of_payment_medicine_group_id');

    foreach ($item as $value) {
      $value->list_of_medicine = $all_detail_items->get($value->id, collect());
    }

    $data['item'] = $item;

    $inpatient = DB::table('in_patients')
      ->join('users', 'in_patients.user_id', '=', 'users.id')
      ->select(
        'in_patients.description',
        DB::raw("DATE_FORMAT(in_patients.created_at, '%d %b %Y') as created_at"),
        'users.fullname as created_by'
      )
      ->where('in_patients.check_up_result_id', '=', $data->check_up_result_id)
      ->get();

    $data['inpatient'] = $inpatient;

    return response()->json($data, 200);
  }

  public function download_excel(Request $request)
  {
    // if ($request->user()->role == 'resepsionis') {
    //     return response()->json([
    //         'message' => 'The user role was invalid.',
    //         'errors' => ['Akses User tidak diizinkan!'],
    //     ], 403);
    // }

    if ($request->user()->role == 'admin') {
      $branch = $request->branch_id;
    } else {
      $branch = $request->user()->branch_id;
    }

    $date = \Carbon\Carbon::parse($request->date)->format('d-m-Y');

    $branches = Branch::find($branch);

    return Excel::download(
      new LaporanKeuanganHarian(
        $request->orderby,
        $request->column,
        $request->date,
        $branch,
        'Laporan Keuangan Harian'
      ),
      'Laporan Keuangan Harian ' . $branches->branch_name . ' ' . $date . '.xlsx'
    );
  }
}
