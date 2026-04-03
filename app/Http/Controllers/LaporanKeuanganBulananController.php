<?php

namespace App\Http\Controllers;

use App\Exports\LaporanKeuanganBulanan;
use App\Models\Branch;
use App\Models\ListofPayments;
use DB;
use Excel;
use Illuminate\Http\Request;

class LaporanKeuanganBulananController extends Controller
{
  public function index(Request $request)
  {
    $cMonth       = 2;
    $cYear        = 2022;
    $resMonth     = $request->month - $cMonth;
    $resYear      = $request->year  - $cYear;
    $includeShop  = ($resMonth > 0 && $resYear >= 0) || ($resMonth < 0 && $resYear > 0);

    $page           = (int) $request->page;
    $items_per_page = 50;

    // ─────────────────────────────────────────────────────────────────────────────
    // Helper closure: apply branch filter once, reuse everywhere
    // ─────────────────────────────────────────────────────────────────────────────
    $branchFilter = function ($query) use ($request) {
      if ($request->branch_id && $request->user()->role === 'admin') {
        $query->where('branches.id', '=', $request->branch_id);
      } else {
        $query->where('branches.id', '=', $request->user()->branch_id);
      }
      return $query;
    };

    // Used on outer subquery where branches.id is out of scope (aliased as branchId)
    $branchFilterAlias = function ($query) use ($request) {
      if ($request->branch_id && $request->user()->role === 'admin') {
        $query->where('branchId', '=', $request->branch_id);
      } else {
        $query->where('branchId', '=', $request->user()->branch_id);
      }
      return $query;
    };

    // Same but uses alias .b. (expenses table)
    $branchFilterB = function ($query) use ($request) {
      if ($request->branch_id && $request->user()->role === 'admin') {
        $query->where('b.id', '=', $request->branch_id);
      } else {
        $query->where('b.id', '=', $request->user()->branch_id);
      }
      return $query;
    };

    // Helper closure: apply month/year filter
    $periodFilter = function ($query, string $column) use ($request) {
      if ($request->month && $request->year) {
        $query->where(DB::raw("MONTH($column)"), $request->month)
          ->where(DB::raw("YEAR($column)"),  $request->year);
      }
      return $query;
    };

    // ─────────────────────────────────────────────────────────────────────────────
    // 1.  MAIN PAGINATED DATA  (medicine items UNION services, then outer GROUP BY)
    // ─────────────────────────────────────────────────────────────────────────────

    // --- medicine items sub-query ---
    $itemSelect = $includeShop
      ? [   // post-Feb-2022: simple multiply (quantity is always > 0)
        DB::raw("TRIM(SUM(pmg.selling_price * lopm.quantity))+0  as price_overall"),
        DB::raw("TRIM(SUM(pmg.capital_price * lopm.quantity))+0  as capital_price"),
        DB::raw("TRIM(SUM(pmg.doctor_fee    * lopm.quantity))+0  as doctor_fee"),
        DB::raw("TRIM(SUM(pmg.petshop_fee   * lopm.quantity))+0  as petshop_fee"),
        DB::raw("TRIM(SUM(lopm.amount_discount))+0               as amount_discount"),
        DB::raw("TRIM(SUM(pmg.doctor_fee * lopm.quantity) - SUM(lopm.amount_discount))+0 as fee_doctor_after_discount"),
      ]
      : [   // legacy: guard against quantity = 0
        DB::raw("(CASE WHEN lopm.quantity=0 THEN TRIM(SUM(pmg.selling_price)) ELSE TRIM(SUM(pmg.selling_price * lopm.quantity)) END)+0 as price_overall"),
        DB::raw("(CASE WHEN lopm.quantity=0 THEN TRIM(SUM(pmg.capital_price)) ELSE TRIM(SUM(pmg.capital_price * lopm.quantity)) END)+0 as capital_price"),
        DB::raw("(CASE WHEN lopm.quantity=0 THEN TRIM(SUM(pmg.doctor_fee))    ELSE TRIM(SUM(pmg.doctor_fee    * lopm.quantity)) END)+0 as doctor_fee"),
        DB::raw("(CASE WHEN lopm.quantity=0 THEN TRIM(SUM(pmg.petshop_fee))   ELSE TRIM(SUM(pmg.petshop_fee   * lopm.quantity)) END)+0 as petshop_fee"),
        DB::raw("TRIM(SUM(lopm.amount_discount))+0 as amount_discount"),
        DB::raw("TRIM(CASE WHEN lopm.quantity=0 THEN SUM(pmg.doctor_fee) ELSE SUM(pmg.doctor_fee * lopm.quantity) END - SUM(lopm.amount_discount))+0 as fee_doctor_after_discount"),
      ];

    $item = DB::table('list_of_payments as lop')
      ->join('check_up_results as cur',              'lop.check_up_result_id',   '=', 'cur.id')
      ->join('list_of_payment_medicine_groups as lopm', 'lopm.list_of_payment_id', '=', 'lop.id')
      ->join('price_medicine_groups as pmg',         'lopm.medicine_group_id',   '=', 'pmg.id')
      ->join('registrations as reg',                 'cur.patient_registration_id', '=', 'reg.id')
      ->join('patients as pa',                       'reg.patient_id',            '=', 'pa.id')
      ->join('users',                                'lop.user_id',               '=', 'users.id')
      ->join('branches',                             'users.branch_id',           '=', 'branches.id')
      ->select(array_merge([
        'lop.id as list_of_payment_id',
        'lop.check_up_result_id as check_up_result_id',
        'reg.id_number  as registration_number',
        'pa.id_member   as patient_number',
        'pa.pet_category',
        'pa.pet_name',
        'reg.complaint',
        'cur.status_outpatient_inpatient',
      ], $itemSelect, [
        'users.fullname   as created_by',
        'lop.created_at   as created_at',
        'branches.id      as branchId',
      ]));

    $periodFilter($item, 'lopm.updated_at');
    $item->groupBy('lop.check_up_result_id');

    // --- services sub-query ---
    $service = DB::table('list_of_payments')
      ->join('check_up_results',         'list_of_payments.check_up_result_id',                  '=', 'check_up_results.id')
      ->join('list_of_payment_services', 'check_up_results.id',                                  '=', 'list_of_payment_services.check_up_result_id')
      ->join('detail_service_patients',  'list_of_payment_services.detail_service_patient_id',   '=', 'detail_service_patients.id')
      ->join('price_services',           'detail_service_patients.price_service_id',              '=', 'price_services.id')
      ->join('registrations',            'check_up_results.patient_registration_id',              '=', 'registrations.id')
      ->join('patients',                 'registrations.patient_id',                              '=', 'patients.id')
      ->join('users',                    'check_up_results.user_id',                              '=', 'users.id')
      ->join('branches',                 'users.branch_id',                                       '=', 'branches.id')
      ->select([
        'list_of_payments.id                      as list_of_payment_id',
        'list_of_payments.check_up_result_id      as check_up_result_id',
        'registrations.id_number                  as registration_number',
        'patients.id_member                       as patient_number',
        'patients.pet_category',
        'patients.pet_name',
        'registrations.complaint',
        'check_up_results.status_outpatient_inpatient',
        DB::raw("TRIM(SUM(detail_service_patients.price_overall))+0                                    as price_overall"),
        DB::raw("TRIM(SUM(price_services.capital_price * detail_service_patients.quantity))+0          as capital_price"),
        DB::raw("TRIM(SUM(price_services.doctor_fee    * detail_service_patients.quantity))+0          as doctor_fee"),
        DB::raw("TRIM(SUM(price_services.petshop_fee   * detail_service_patients.quantity))+0          as petshop_fee"),
        DB::raw("TRIM(SUM(list_of_payment_services.amount_discount))+0                                 as amount_discount"),
        DB::raw("TRIM(SUM(price_services.doctor_fee * detail_service_patients.quantity) - SUM(list_of_payment_services.amount_discount))+0 as fee_doctor_after_discount"),
        'users.fullname                            as created_by',
        'list_of_payments.updated_at              as created_at',
        'branches.id                              as branchId',
      ]);

    $periodFilter($service, 'list_of_payment_services.updated_at');
    $service->groupBy('list_of_payments.check_up_result_id')->union($item);

    // --- outer query: merge item + service rows per check_up_result_id ---
    $data = DB::query()->fromSub($service, 'p_pn')
      ->select([
        'list_of_payment_id',
        'check_up_result_id',
        'registration_number',
        'patient_number',
        'pet_category',
        'pet_name',
        'complaint',
        'status_outpatient_inpatient',
        DB::raw("TRIM(SUM(price_overall))+0            as price_overall"),
        DB::raw("TRIM(SUM(capital_price))+0            as capital_price"),
        DB::raw("TRIM(SUM(doctor_fee))+0               as doctor_fee"),
        DB::raw("TRIM(SUM(petshop_fee))+0              as petshop_fee"),
        DB::raw("TRIM(SUM(amount_discount))+0          as amount_discount"),
        DB::raw("TRIM(SUM(fee_doctor_after_discount))+0 as fee_doctor_after_discount"),
        'created_by',
        DB::raw("DATE_FORMAT(created_at, '%d %b %Y')  as created_at"),
      ])
      ->groupBy('check_up_result_id');

    $branchFilterAlias($data);

    if ($request->orderby) {
      $data->orderBy($request->column, $request->orderby);
    } else {
      $data->orderBy('list_of_payment_id', 'desc');
    }

    // ── Pagination (single get() call to count, then slice) ──────────────────────
    $count_data = $data->get()->count();                    // one query
    $offset     = max(0, ($page - 1) * $items_per_page);   // never negative

    $rows        = $data->offset($offset)->limit($items_per_page)->get();   // one query
    $total_paging = ceil($count_data / $items_per_page);

    // ─────────────────────────────────────────────────────────────────────────────
    // 2.  SUMMARY AGGREGATES  — collapsed into ONE query each for items / services
    //     instead of separate queries per metric
    // ─────────────────────────────────────────────────────────────────────────────

    // --- medicine items summary (all metrics at once) ---
    $itemSummarySelect = $includeShop
      ? [
        DB::raw("TRIM(SUM(pmg.selling_price * lopm.quantity))+0 as price_overall"),
        DB::raw("TRIM(SUM(pmg.capital_price * lopm.quantity))+0 as capital_price"),
        DB::raw("TRIM(SUM(pmg.doctor_fee    * lopm.quantity))+0 as doctor_fee"),
        DB::raw("TRIM(SUM(pmg.petshop_fee   * lopm.quantity))+0 as petshop_fee"),
        DB::raw("TRIM(SUM(lopm.amount_discount))+0              as amount_discount"),
      ]
      : [
        DB::raw("(CASE WHEN lopm.quantity=0 THEN TRIM(SUM(pmg.selling_price)) ELSE TRIM(SUM(pmg.selling_price * lopm.quantity)) END)+0 as price_overall"),
        DB::raw("(CASE WHEN lopm.quantity=0 THEN TRIM(SUM(pmg.capital_price)) ELSE TRIM(SUM(pmg.capital_price * lopm.quantity)) END)+0 as capital_price"),
        DB::raw("(CASE WHEN lopm.quantity=0 THEN TRIM(SUM(pmg.doctor_fee))    ELSE TRIM(SUM(pmg.doctor_fee    * lopm.quantity)) END)+0 as doctor_fee"),
        DB::raw("(CASE WHEN lopm.quantity=0 THEN TRIM(SUM(pmg.petshop_fee))   ELSE TRIM(SUM(pmg.petshop_fee   * lopm.quantity)) END)+0 as petshop_fee"),
        DB::raw("TRIM(SUM(lopm.amount_discount))+0 as amount_discount"),
      ];

    $item_summary = DB::table('list_of_payments as lop')
      ->join('check_up_results as cur',               'lop.check_up_result_id',    '=', 'cur.id')
      ->join('list_of_payment_medicine_groups as lopm', 'lopm.list_of_payment_id',  '=', 'lop.id')
      ->join('price_medicine_groups as pmg',           'lopm.medicine_group_id',   '=', 'pmg.id')
      ->join('registrations as reg',                   'cur.patient_registration_id', '=', 'reg.id')
      ->join('patients as pa',                         'reg.patient_id',            '=', 'pa.id')
      ->join('users',                                  'lop.user_id',               '=', 'users.id')
      ->join('branches',                               'users.branch_id',           '=', 'branches.id')
      ->select($itemSummarySelect);

    $branchFilter($item_summary);
    $periodFilter($item_summary, 'lopm.updated_at');
    $item_summary = $item_summary->first();   // ← 1 query instead of 5

    // --- services summary (all metrics at once) ---
    $service_summary = DB::table('list_of_payments')
      ->join('check_up_results',         'list_of_payments.check_up_result_id',                '=', 'check_up_results.id')
      ->join('list_of_payment_services', 'check_up_results.id',                                '=', 'list_of_payment_services.check_up_result_id')
      ->join('detail_service_patients',  'list_of_payment_services.detail_service_patient_id', '=', 'detail_service_patients.id')
      ->join('price_services',           'detail_service_patients.price_service_id',            '=', 'price_services.id')
      ->join('users',                    'check_up_results.user_id',                            '=', 'users.id')
      ->join('branches',                 'users.branch_id',                                     '=', 'branches.id')
      ->select([
        DB::raw("TRIM(SUM(detail_service_patients.price_overall))+0                           as price_overall"),
        DB::raw("TRIM(SUM(price_services.capital_price * detail_service_patients.quantity))+0 as capital_price"),
        DB::raw("TRIM(SUM(price_services.doctor_fee    * detail_service_patients.quantity))+0 as doctor_fee"),
        DB::raw("TRIM(SUM(price_services.petshop_fee   * detail_service_patients.quantity))+0 as petshop_fee"),
        DB::raw("TRIM(SUM(list_of_payment_services.amount_discount))+0                        as amount_discount"),
      ]);

    $branchFilter($service_summary);
    $periodFilter($service_summary, 'list_of_payment_services.updated_at');
    $service_summary = $service_summary->first();   // ← 1 query instead of 5

    // ─────────────────────────────────────────────────────────────────────────────
    // 3.  SHOP SUMMARIES  (only when period > Feb 2022)
    // ─────────────────────────────────────────────────────────────────────────────
    $shop_clinic_summary = (object)['price_overall' => 0, 'capital_price' => 0, 'profit' => 0];
    $shop_summary        = (object)['price_overall' => 0, 'capital_price' => 0, 'profit' => 0];

    if ($includeShop) {
      // petshop-with-clinic — one query for price, capital AND profit
      $shop_clinic_summary = DB::table('payment_petshop_with_clinics as ppwc')
        ->join('price_item_pet_shops as pip', 'ppwc.price_item_pet_shop_id', 'pip.id')
        ->join('users',    'ppwc.user_id',    '=', 'users.id')
        ->join('branches', 'users.branch_id', '=', 'branches.id')
        ->select([
          DB::raw("TRIM(SUM(pip.selling_price * ppwc.total_item))+0 as price_overall"),
          DB::raw("TRIM(SUM(pip.capital_price * ppwc.total_item))+0 as capital_price"),
          DB::raw("TRIM(SUM(pip.profit        * ppwc.total_item))+0 as profit"),
        ]);
      $branchFilter($shop_clinic_summary);
      $periodFilter($shop_clinic_summary, 'ppwc.created_at');
      $shop_clinic_summary = $shop_clinic_summary->first();   // 1 query instead of 3

      // standalone petshop — same consolidation
      $shop_summary = DB::table('payment_petshops as pp')
        ->join('price_item_pet_shops as pip', 'pp.price_item_pet_shop_id', 'pip.id')
        ->join('users',    'pp.user_id',      '=', 'users.id')
        ->join('branches', 'users.branch_id', '=', 'branches.id')
        ->select([
          DB::raw("TRIM(SUM(pip.selling_price * pp.total_item))+0 as price_overall"),
          DB::raw("TRIM(SUM(pip.capital_price * pp.total_item))+0 as capital_price"),
          DB::raw("TRIM(SUM(pip.profit        * pp.total_item))+0 as profit"),
        ]);
      $branchFilter($shop_summary);
      $periodFilter($shop_summary, 'pp.created_at');
      $shop_summary = $shop_summary->first();   // 1 query instead of 3
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // 4.  EXPENSES  (unchanged, already a single query)
    // ─────────────────────────────────────────────────────────────────────────────
    $expenses_row = DB::table('expenses as e')
      ->join('users as u',    'e.user_id_spender', '=', 'u.id')
      ->join('branches as b', 'u.branch_id',       '=', 'b.id')
      ->select(DB::raw("TRIM(SUM(IFNULL(e.amount_overall,0)))+0 as amount_overall"));

    $branchFilterB($expenses_row);
    $periodFilter($expenses_row, 'e.date_spend');
    $expenses_row  = $expenses_row->first();
    $total_expenses = (float) ($expenses_row->amount_overall ?? 0);

    // ─────────────────────────────────────────────────────────────────────────────
    // 5.  COMBINE TOTALS
    // ─────────────────────────────────────────────────────────────────────────────
    $price_overall   = $item_summary->price_overall   + $service_summary->price_overall
      + $shop_clinic_summary->price_overall + $shop_summary->price_overall;

    $capital_price   = $item_summary->capital_price   + $service_summary->capital_price
      + $shop_clinic_summary->capital_price + $shop_summary->capital_price;

    $doctor_fee      = $item_summary->doctor_fee      + $service_summary->doctor_fee;
    $petshop_fee     = $item_summary->petshop_fee     + $service_summary->petshop_fee;
    $amount_discount = $item_summary->amount_discount + $service_summary->amount_discount;

    $net_profit = $doctor_fee - $total_expenses
      + $shop_clinic_summary->profit
      + $shop_summary->profit;

    // ─────────────────────────────────────────────────────────────────────────────
    // 6.  RESPONSE
    // ─────────────────────────────────────────────────────────────────────────────
    return response()->json([
      'data'            => $rows,
      'price_overall'   => $price_overall,
      'capital_price'   => $capital_price,
      'doctor_fee'      => $doctor_fee,
      'petshop_fee'     => $petshop_fee,
      'amount_discount' => $amount_discount,
      'expenses'        => $total_expenses,
      'net_profit'      => $net_profit,
      'total_paging'    => $total_paging,
    ], 200);
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
        'patients.pet_year_age',
        'patients.pet_month_age',
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
        DB::raw("DATE_FORMAT(detail_service_patients.created_at, '%d %b %Y') as created_at")
      )
      ->where('list_of_payment_services.check_up_result_id', '=', $data->check_up_result_id);

    if ($request->month && $request->year) {
      $list_of_payment_services = $list_of_payment_services->where(DB::raw("MONTH(list_of_payment_services.updated_at)"), $request->month)
        ->where(DB::raw("YEAR(list_of_payment_services.updated_at)"), $request->year);
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

    if ($request->month && $request->year) {
      $item = $item->where(DB::raw("MONTH(lopm.updated_at)"), $request->month)
        ->where(DB::raw("YEAR(lopm.updated_at)"), $request->year);
    }
    $item = $item->get();

    foreach ($item as $value) {

      $detail_item = DB::table('list_of_payment_items as lopi')
        ->join('price_items', 'lopi.price_item_id', '=', 'price_items.id')
        ->join('list_of_items', 'price_items.list_of_items_id', '=', 'list_of_items.id')
        ->join('category_item', 'list_of_items.category_item_id', '=', 'category_item.id')
        ->join('unit_item', 'list_of_items.unit_item_id', '=', 'unit_item.id')
        ->join('users', 'lopi.user_id', '=', 'users.id')
        ->select(
          'lopi.id as detail_item_patients_id',
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
        ->where('lopi.list_of_payment_medicine_group_id', '=', $value->id)
        ->orderBy('lopi.id', 'asc')
        ->get();

      $value->list_of_medicine = $detail_item;
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

    $name = '';
    $branches = Branch::find($branch);

    if ($request->month && $request->year) {
      $monthName = date('F', mktime(0, 0, 0, $request->month, 10));

      $time = $monthName . ' ' . $request->year;

      $name = 'Laporan Keuangan Bulanan ' . $branches->branch_name . ' ' . $time . '.xlsx';
    } else {
      $name = 'Laporan Keuangan Bulanan ' . $branches->branch_name . '.xlsx';
    }

    return Excel::download(
      new LaporanKeuanganBulanan(
        $request->orderby,
        $request->column,
        $request->month,
        $request->year,
        $branch,
        'Laporan Keuangan Bulanan'
      ),
      $name
    );

    // return (new LaporanKeuanganBulanan())->download('Laporan Keuangan Bulanan.xlsx');
  }
}
