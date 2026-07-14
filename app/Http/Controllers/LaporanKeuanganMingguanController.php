<?php

namespace App\Http\Controllers;

use App\Exports\LaporanKeuanganMingguan;
use App\Models\Branch;
use App\Models\ListofPayments;
use DB;
use Excel;
use Illuminate\Http\Request;
use Carbon\Carbon;

class LaporanKeuanganMingguanController extends Controller
{
    public function index(Request $request)
    {
        // if ($request->user()->role == 'resepsionis') {
        //     return response()->json([
        //         'message' => 'The user role was invalid.',
        //         'errors' => ['Akses User tidak diizinkan!'],
        //     ], 403);
        // }

        $fdate = $request->date_from;
        $tdate = new Carbon('2022-02-14');
        $diff = $tdate->diffInDays($fdate, false);

        // Pre Feb-2022 data used quantity=0 as a "no quantity recorded" sentinel and
        // never included pet shop sales; post Feb-2022 data always has a real quantity
        // and pet shop sales are included in the totals.
        $includeShop = $diff > 0;

        $page = $request->page;
        $items_per_page = 50;

        // ── Shared filter helpers, reused across every query below ─────────────
        $branchFilter = function ($query) use ($request) {
            if ($request->branch_id && $request->user()->role == 'admin') {
                $query->where('branches.id', '=', $request->branch_id);
            } else {
                $query->where('branches.id', '=', $request->user()->branch_id);
            }
            return $query;
        };

        $branchFilterAlias = function ($query) use ($request) {
            if ($request->branch_id && $request->user()->role == 'admin') {
                $query->where('branchId', '=', $request->branch_id);
            } else {
                $query->where('branchId', '=', $request->user()->branch_id);
            }
            return $query;
        };

        $branchFilterB = function ($query) use ($request) {
            if ($request->branch_id && $request->user()->role == 'admin') {
                $query->where('b.id', '=', $request->branch_id);
            } else {
                $query->where('b.id', '=', $request->user()->branch_id);
            }
            return $query;
        };

        $dateFilter = function ($query, string $column) use ($request) {
            if ($request->date_from && $request->date_to) {
                $query->whereBetween(DB::raw("DATE($column)"), [$request->date_from, $request->date_to]);
            }
            return $query;
        };

        // ── 1. Main paginated list (medicine items UNION services) ─────────────
        $itemSelect = $includeShop
            ? [
                DB::raw("TRIM(SUM(pmg.selling_price * lopm.quantity))+0 as price_overall"),
                DB::raw("TRIM(SUM(pmg.capital_price * lopm.quantity))+0 as capital_price"),
                DB::raw("TRIM(SUM(pmg.doctor_fee * lopm.quantity))+0 as doctor_fee"),
                DB::raw("TRIM(SUM(pmg.petshop_fee * lopm.quantity))+0 as petshop_fee"),
                DB::raw("TRIM(SUM(lopm.amount_discount))+0 as amount_discount"),
                DB::raw("TRIM(SUM(pmg.doctor_fee * lopm.quantity) - SUM(lopm.amount_discount))+0 as fee_doctor_after_discount"),
            ]
            : [
                DB::raw("(CASE WHEN lopm.quantity = 0 THEN TRIM(SUM(pmg.selling_price)) ELSE TRIM(SUM(pmg.selling_price * lopm.quantity)) END)+0 as price_overall"),
                DB::raw("(CASE WHEN lopm.quantity = 0 THEN TRIM(SUM(pmg.capital_price)) ELSE TRIM(SUM(pmg.capital_price * lopm.quantity)) END)+0 as capital_price"),
                DB::raw("(CASE WHEN lopm.quantity = 0 THEN TRIM(SUM(pmg.doctor_fee)) ELSE TRIM(SUM(pmg.doctor_fee * lopm.quantity)) END)+0 as doctor_fee"),
                DB::raw("(CASE WHEN lopm.quantity = 0 THEN TRIM(SUM(pmg.petshop_fee)) ELSE TRIM(SUM(pmg.petshop_fee * lopm.quantity)) END)+0 as petshop_fee"),
                DB::raw("TRIM(SUM(lopm.amount_discount))+0 as amount_discount"),
                DB::raw("TRIM(CASE WHEN lopm.quantity = 0 THEN TRIM(SUM(pmg.doctor_fee)) ELSE TRIM(SUM(pmg.doctor_fee * lopm.quantity)) END - SUM(lopm.amount_discount))+0 as fee_doctor_after_discount"),
            ];

        $item = DB::table('list_of_payments as lop')
            ->join('check_up_results as cur', 'lop.check_up_result_id', '=', 'cur.id')
            ->join('list_of_payment_medicine_groups as lopm', 'lopm.list_of_payment_id', '=', 'lop.id')
            ->join('price_medicine_groups as pmg', 'lopm.medicine_group_id', '=', 'pmg.id')
            ->join('registrations as reg', 'cur.patient_registration_id', '=', 'reg.id')
            ->join('patients as pa', 'reg.patient_id', '=', 'pa.id')
            ->join('users', 'lop.user_id', '=', 'users.id')
            ->join('branches', 'users.branch_id', '=', 'branches.id')
            ->select(array_merge([
                'lop.id as list_of_payment_id',
                'lop.check_up_result_id as check_up_result_id',
                'reg.id_number as registration_number',
                'pa.id_member as patient_number',
                'pa.pet_category',
                'pa.pet_name',
                'reg.complaint',
                'cur.status_outpatient_inpatient',
            ], $itemSelect, [
                'users.fullname as created_by',
                'lop.created_at as created_at',
                'branches.id as branchId',
            ]));

        $dateFilter($item, 'lopm.updated_at');
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

        $dateFilter($service, 'list_of_payment_services.updated_at');
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
            )
            ->groupBy('check_up_result_id');

        $branchFilterAlias($data);

        if ($request->orderby) {
            $data = $data->orderBy($request->column, $request->orderby);
        } else {
            $data = $data->orderBy('list_of_payment_id', 'desc');
        }

        // Pagination: count via a lightweight SQL-side COUNT instead of pulling
        // every matching row into PHP just to know how many there are. The
        // "offset beyond available data → fall back to page 1" behaviour is
        // preserved exactly as before.
        $count_data = DB::query()->fromSub($data, 'cnt')->count();

        $offset = ($page - 1) * $items_per_page;

        $count_result = $count_data - $offset;

        if ($count_result < 0) {
            $offset = 0;
        }

        $data = $data->offset($offset)->limit($items_per_page)->get();

        $total_paging = $count_data / $items_per_page;

        // ── 2. Summary aggregates — one query per group instead of one query per
        //      metric. The underlying SQL formulas are identical for both the
        //      pre/post Feb-2022 branches, so they only need to run once.
        // ─────────────────────────────────────────────────────────────────────
        $medicine_agg = DB::table('list_of_payments as lop')
            ->join('list_of_payment_medicine_groups as lopm', 'lop.id', '=', 'lopm.list_of_payment_id')
            ->join('price_medicine_groups as pmg', 'lopm.medicine_group_id', '=', 'pmg.id')
            ->join('users', 'lop.user_id', '=', 'users.id')
            ->join('branches', 'users.branch_id', '=', 'branches.id')
            ->select(
                DB::raw("(CASE WHEN lopm.quantity = 0 THEN TRIM(SUM(pmg.selling_price)) ELSE TRIM(SUM(pmg.selling_price * lopm.quantity)) END)+0 as price_overall"),
                DB::raw("(CASE WHEN lopm.quantity = 0 THEN TRIM(SUM(pmg.capital_price)) ELSE TRIM(SUM(pmg.capital_price * lopm.quantity)) END)+0 as capital_price"),
                DB::raw("(CASE WHEN lopm.quantity = 0 THEN TRIM(SUM(pmg.doctor_fee)) ELSE TRIM(SUM(pmg.doctor_fee * lopm.quantity)) END)+0 as doctor_fee"),
                DB::raw("(CASE WHEN lopm.quantity = 0 THEN TRIM(SUM(pmg.petshop_fee)) ELSE TRIM(SUM(pmg.petshop_fee * lopm.quantity)) END)+0 as petshop_fee")
            );

        $branchFilter($medicine_agg);
        $dateFilter($medicine_agg, 'lopm.updated_at');
        $medicine_agg = $medicine_agg->first();

        // amount_discount uses the same join shape as the main "item" query
        // (goes through check_up_results/registrations/patients), kept as its
        // own query to match the original filter exactly.
        $amount_discount_item = DB::table('list_of_payments as lop')
            ->join('check_up_results as cur', 'lop.check_up_result_id', '=', 'cur.id')
            ->join('list_of_payment_medicine_groups as lopm', 'lopm.list_of_payment_id', '=', 'lop.id')
            ->join('price_medicine_groups as pmg', 'lopm.medicine_group_id', '=', 'pmg.id')
            ->join('registrations as reg', 'cur.patient_registration_id', '=', 'reg.id')
            ->join('patients as pa', 'reg.patient_id', '=', 'pa.id')
            ->join('users', 'lop.user_id', '=', 'users.id')
            ->join('branches', 'users.branch_id', '=', 'branches.id')
            ->select(
                DB::raw("TRIM(SUM(lopm.amount_discount))+0 as amount_discount")
            );

        $branchFilter($amount_discount_item);
        $dateFilter($amount_discount_item, 'lopm.updated_at');
        $amount_discount_item = $amount_discount_item->first();

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
            );

        $branchFilter($service_agg);
        $dateFilter($service_agg, 'list_of_payment_services.updated_at');
        $service_agg = $service_agg->first();

        $price_overall = $medicine_agg->price_overall + $service_agg->price_overall;
        $capital_price = $medicine_agg->capital_price + $service_agg->capital_price;
        $doctor_fee = $medicine_agg->doctor_fee + $service_agg->doctor_fee;
        $petshop_fee = $medicine_agg->petshop_fee + $service_agg->petshop_fee;
        $amount_discount = $amount_discount_item->amount_discount + $service_agg->amount_discount;

        // ── 3. Pet shop summaries — only relevant post Feb-2022. Note: capital
        //      price from pet shop sales is intentionally NOT added to
        //      $capital_price below, matching the pre-existing (unchanged)
        //      behaviour of this report.
        // ─────────────────────────────────────────────────────────────────────
        $shop_clinic_agg = (object) ['price_overall' => 0, 'profit' => 0];
        $shop_agg = (object) ['price_overall' => 0, 'profit' => 0];

        if ($includeShop) {
            $shop_clinic_agg = DB::table('payment_petshop_with_clinics as ppwc')
                ->join('price_item_pet_shops as pip', 'ppwc.price_item_pet_shop_id', 'pip.id')
                ->join('users', 'ppwc.user_id', '=', 'users.id')
                ->join('branches', 'users.branch_id', '=', 'branches.id')
                ->select(
                    DB::raw("TRIM(SUM(pip.selling_price * ppwc.total_item))+0 as price_overall"),
                    DB::raw("TRIM(SUM(pip.profit * ppwc.total_item))+0 as profit")
                );

            $branchFilter($shop_clinic_agg);
            $dateFilter($shop_clinic_agg, 'ppwc.created_at');
            $shop_clinic_agg = $shop_clinic_agg->first();

            $shop_agg = DB::table('payment_petshops as pp')
                ->join('price_item_pet_shops as pip', 'pp.price_item_pet_shop_id', 'pip.id')
                ->join('users', 'pp.user_id', '=', 'users.id')
                ->join('branches', 'users.branch_id', '=', 'branches.id')
                ->select(
                    DB::raw("TRIM(SUM(pip.selling_price * pp.total_item))+0 as price_overall"),
                    DB::raw("TRIM(SUM(pip.profit * pp.total_item))+0 as profit")
                );

            $branchFilter($shop_agg);
            $dateFilter($shop_agg, 'pp.created_at');
            $shop_agg = $shop_agg->first();

            $price_overall += $shop_clinic_agg->price_overall + $shop_agg->price_overall;
        }

        // ── 4. Expenses ──────────────────────────────────────────────────────
        $expenses = DB::table('expenses as e')
            ->join('users as u', 'e.user_id_spender', '=', 'u.id')
            ->join('branches as b', 'u.branch_id', '=', 'b.id')
            ->select(DB::raw("TRIM(SUM(IFNULL(e.amount_overall,0)))+0 as amount_overall"));

        $branchFilterB($expenses);
        $dateFilter($expenses, 'e.date_spend');

        $expenses = $expenses->first();

        $total_expenses = 0;

        if (!is_null($expenses->amount_overall)) {
            $total_expenses = $expenses->amount_overall;
        }

        $net_profit = $includeShop
            ? $doctor_fee - $total_expenses + $shop_clinic_agg->profit + $shop_agg->profit
            : $doctor_fee - $total_expenses;

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

        if ($request->date_from && $request->date_to) {
            $list_of_payment_services = $list_of_payment_services
                ->whereBetween(DB::raw('DATE(list_of_payment_services.updated_at)'), [$request->date_from, $request->date_to]);
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

        if ($request->date_from && $request->date_to) {
            $item = $item
                ->whereBetween(DB::raw('DATE(lopm.updated_at)'), [$request->date_from, $request->date_to]);
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

        $branch = "";

        if ($request->user()->role == 'admin') {
            $branch = $request->branch_id;
        } else {
            $branch = $request->user()->branch_id;
        }

        $date_from = '';
        $date_to = '';
        $filename = '';

        $branches = Branch::find($branch);

        if ($request->date_from && $request->date_to) {
            $date_from = \Carbon\Carbon::parse($request->date_from)->format('d-m-Y');
            $date_to = \Carbon\Carbon::parse($request->date_to)->format('d-m-Y');
            $filename = 'Laporan Keuangan Mingguan ' . $branches->branch_name . ' ' . $date_from . ' - ' . $date_to . '.xlsx';
        } else {
            $filename = 'Laporan Keuangan Mingguan ' . $branches->branch_name . '.xlsx';
        }

        return Excel::download(
            new LaporanKeuanganMingguan(
                $request->orderby,
                $request->column,
                $request->date_from,
                $request->date_to,
                $branch,
                'Laporan Keuangan Mingguan'
            ),
            $filename
        );
    }
}
