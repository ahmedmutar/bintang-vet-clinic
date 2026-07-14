<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Models\User;
use DB;
use Illuminate\Http\Request;
use Nasution\Terbilang;
use PDF;
use Response;
use Validator;

class PenggajianController extends Controller
{
    public function index(Request $request)
    {

        if ($request->keyword) {

            $res = $this->Search($request);

            $data = DB::table('payrolls as py')
                ->join('users', 'py.user_employee_id', '=', 'users.id')
                ->join('branches', 'users.branch_id', '=', 'branches.id')
                ->select(
                    'py.id as id',
                    'py.user_employee_id as user_employee_id',
                    'users.fullname as fullname',
                    DB::raw("DATE_FORMAT(py.date_payed, '%d %b %Y %H:%i:%s') as date_payed"),
                    'branches.branch_name as branch_name',
                    DB::raw("TRIM(py.basic_sallary)+0 as basic_sallary"),
                    DB::raw("TRIM(py.accomodation)+0 as accomodation"),
                    DB::raw("TRIM(py.percentage_turnover)+0 as percentage_turnover"),
                    DB::raw("TRIM(py.amount_turnover)+0 as amount_turnover"),
                    DB::raw("TRIM(py.total_turnover)+0 as total_turnover"),
                    DB::raw("TRIM(py.amount_inpatient)+0 as amount_inpatient"),
                    DB::raw("TRIM(py.count_inpatient)+0 as count_inpatient"),
                    DB::raw("TRIM(py.total_inpatient)+0 as total_inpatient"),
                    DB::raw("TRIM(py.percentage_surgery)+0 as percentage_surgery"),
                    DB::raw("TRIM(py.amount_surgery)+0 as amount_surgery"),
                    DB::raw("TRIM(py.total_surgery)+0 as total_surgery"),
                    DB::raw("TRIM(py.total_overall)+0 as total_overall"),

                    DB::raw("TRIM(py.amount_grooming)+0 as amount_grooming"),
                    DB::raw("TRIM(py.count_grooming)+0 as count_grooming"),
                    DB::raw("TRIM(py.total_grooming)+0 as total_grooming"),
                    DB::raw("TRIM(py.minus_turnover)+0 as minus_turnover"),
                    DB::raw("TRIM(py.eat)+0 as eat"),
                    DB::raw("TRIM(py.amount_eat)+0 as amount_eat"),
                    DB::raw("TRIM(py.count_eat)+0 as count_eat"),
                    DB::raw("TRIM(py.fine)+0 as fine"),
                )
                ->where('py.isDeleted', '=', 0);

            if ($res) {
                $data = $data->where($res, 'like', '%' . $request->keyword . '%');
            } else {
                $data = [];
                return response()->json($data, 200);
            }

            if ($request->user()->role == 'dokter' || $request->user()->role == 'resepsionis') {
                $data = $data->where('py.user_employee_id', '=', $request->user()->id);
            }

            if ($request->branch_id && $request->user()->role == 'admin') {
                $data = $data->where('users.branch_id', '=', $request->branch_id);
            }

            if ($request->orderby) {
                $data = $data->orderBy($request->column, $request->orderby);
            }

            $data = $data->orderBy('py.id', 'desc');

            $data = $data->get();

            return response()->json($data, 200);

        } else {

            $data = DB::table('payrolls as py')
                ->join('users', 'py.user_employee_id', '=', 'users.id')
                ->join('branches', 'users.branch_id', '=', 'branches.id')
                ->select(
                    'py.id as id',
                    'py.user_employee_id as user_employee_id',
                    'users.fullname as fullname',
                    DB::raw("DATE_FORMAT(py.date_payed, '%d/%m/%Y') as date_payed"),
                    'branches.branch_name as branch_name',
                    DB::raw("TRIM(py.basic_sallary)+0 as basic_sallary"),
                    DB::raw("TRIM(py.accomodation)+0 as accomodation"),
                    DB::raw("TRIM(py.percentage_turnover)+0 as percentage_turnover"),
                    DB::raw("TRIM(py.amount_turnover)+0 as amount_turnover"),
                    DB::raw("TRIM(py.total_turnover)+0 as total_turnover"),
                    DB::raw("TRIM(py.amount_inpatient)+0 as amount_inpatient"),
                    DB::raw("TRIM(py.count_inpatient)+0 as count_inpatient"),
                    DB::raw("TRIM(py.total_inpatient)+0 as total_inpatient"),
                    DB::raw("TRIM(py.percentage_surgery)+0 as percentage_surgery"),
                    DB::raw("TRIM(py.amount_surgery)+0 as amount_surgery"),
                    DB::raw("TRIM(py.total_surgery)+0 as total_surgery"),
                    DB::raw("TRIM(py.total_overall)+0 as total_overall"),

                    DB::raw("TRIM(py.amount_grooming)+0 as amount_grooming"),
                    DB::raw("TRIM(py.count_grooming)+0 as count_grooming"),
                    DB::raw("TRIM(py.total_grooming)+0 as total_grooming"),
                    DB::raw("TRIM(py.minus_turnover)+0 as minus_turnover"),
                    DB::raw("TRIM(py.eat)+0 as eat"),
                    DB::raw("TRIM(py.amount_eat)+0 as amount_eat"),
                    DB::raw("TRIM(py.count_eat)+0 as count_eat"),
                    DB::raw("TRIM(py.fine)+0 as fine"),
                )
                ->where('py.isDeleted', '=', 0);

            if ($request->user()->role == 'dokter' || $request->user()->role == 'resepsionis') {
                $data = $data->where('py.user_employee_id', '=', $request->user()->id);
            }

            if ($request->branch_id && $request->user()->role == 'admin') {
                $data = $data->where('users.branch_id', '=', $request->branch_id);
            }

            if ($request->month && $request->year) {
                $data = $data->where(DB::raw("MONTH(py.date_payed)"), $request->month)
                    ->where(DB::raw("YEAR(py.date_payed)"), $request->year);
            }

            if ($request->orderby) {
                $data = $data->orderBy($request->column, $request->orderby);
            }

            $data = $data->orderBy('py.id', 'desc');

            $data = $data->get();

            $amount_overall = 0;

            foreach ($data as $res) {
                $amount_overall += $res->total_overall;
            }

            return response()->json(['data' => $data,
                'amount_sallary' => $amount_overall], 200);
        }

    }

    private function Search($request)
    {
        $temp_column = '';

        $data = DB::table('payrolls as py')
            ->join('users', 'py.user_employee_id', '=', 'users.id')
            ->join('branches', 'users.branch_id', '=', 'branches.id')
            ->select(
                'users.fullname',
                'branches.branch_name'
            )
            ->where('py.isDeleted', '=', 0);

        if ($request->user()->role == 'dokter' || $request->user()->role == 'resepsionis') {
            $data = $data->where('py.user_employee_id', '=', $request->user()->id);
        }

        if ($request->branch_id && $request->user()->role == 'admin') {
            $data = $data->where('users.branch_id', '=', $request->branch_id);
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

        $data = DB::table('payrolls as py')
            ->join('users', 'py.user_employee_id', '=', 'users.id')
            ->join('branches', 'users.branch_id', '=', 'branches.id')
            ->select(
                'users.fullname',
                'branches.branch_name'
            )
            ->where('py.isDeleted', '=', 0);

        if ($request->user()->role == 'dokter' || $request->user()->role == 'resepsionis') {
            $data = $data->where('py.user_employee_id', '=', $request->user()->id);
        }

        if ($request->branch_id && $request->user()->role == 'admin') {
            $data = $data->where('users.branch_id', '=', $request->branch_id);
        }

        if ($request->keyword) {
            $data = $data->where('branches.branch_name', 'like', '%' . $request->keyword . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'branches.branch_name';
            return $temp_column;
        }
    }

    public function create(Request $request)
    {
        if ($request->user()->role == 'dokter' || $request->user()->role == 'resepsionis') {
            return response()->json([
                'message' => 'The user role was invalid.',
                'errors' => ['Akses User tidak diizinkan!'],
            ], 403);
        }

        $validate = Validator::make($request->all(), [
            'date_payed' => 'required|date_format:d/m/Y',
            'user_employee_id' => 'required|numeric',
            'basic_sallary' => 'required|numeric|min:0',
            'accomodation' => 'required|numeric|min:0',
            'percentage_turnover' => 'required|numeric|min:0',
            'amount_turnover' => 'required|numeric|min:0',
            'total_turnover' => 'required|numeric|min:0',
            'amount_inpatient' => 'required|numeric|min:0',
            'count_inpatient' => 'required|numeric|min:0',
            'total_inpatient' => 'required|numeric|min:0',
            'percentage_surgery' => 'required|numeric|min:0',
            'amount_surgery' => 'required|numeric|min:0',
            'total_surgery' => 'required|numeric|min:0',
            'total_overall' => 'required|numeric|min:0',

            'amount_grooming' => 'required|numeric|min:0',
            'count_grooming' => 'required|numeric|min:0',
            'total_grooming' => 'required|numeric|min:0',

            'minus_turnover' => 'required|numeric|min:0',
            'eat' => 'required|numeric|min:0',
            'amount_eat' => 'required|numeric|min:0',
            'count_eat' => 'required|numeric|min:0',
            'fine' => 'required|numeric|min:0',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        $res_date = rtrim($request->date_payed, "/");

        $date = explode('/', $res_date);

        $find_duplicate = db::table('payrolls')
            ->select('id')
            ->where('user_employee_id', '=', $request->user_employee_id)
            ->where(DB::raw("MONTH(date_payed)"), $date[1])
            ->where(DB::raw("YEAR(date_payed)"), $date[2])
            ->where('isDeleted', '=', 0)
            ->count();

        $date_inserted = $date[2] . '-' . $date[1] . '-' . $date[0];

        if ($find_duplicate != 0) {

            return response()->json([
                'message' => 'The data was invalid.',
                'errors' => ['Data sudah ada!'],
            ], 422);

        }

        $payroll = Payroll::create([
            'user_employee_id' => $request->user_employee_id,
            'date_payed' => $date_inserted,
            'basic_sallary' => $request->basic_sallary,
            'accomodation' => $request->accomodation,
            'percentage_turnover' => $request->percentage_turnover,
            'amount_turnover' => $request->amount_turnover,
            'total_turnover' => $request->total_turnover,
            'amount_inpatient' => $request->amount_inpatient,
            'count_inpatient' => $request->count_inpatient,
            'total_inpatient' => $request->total_inpatient,
            'percentage_surgery' => $request->percentage_surgery,
            'amount_surgery' => $request->amount_surgery,
            'total_surgery' => $request->total_surgery,
            'total_overall' => $request->total_overall,
            'user_id' => $request->user()->id,

            'amount_grooming' => $request->amount_grooming,
            'count_grooming' => $request->count_grooming,
            'total_grooming' => $request->total_grooming,

            'minus_turnover' => $request->minus_turnover,
            'eat' => $request->eat,
            'amount_eat' => $request->amount_eat,
            'count_eat' => $request->count_eat,
            'fine' => $request->fine,
        ]);

        return response()->json([
            'id' => $payroll->id,
            'message' => 'Berhasil menambah Data',
        ], 200);

    }

    public function sallary_user(Request $request)
    {
        if ($request->user()->role == 'dokter' || $request->user()->role == 'resepsionis') {
            return response()->json([
                'message' => 'The user role was invalid.',
                'errors' => ['Akses User tidak diizinkan!'],
            ], 403);
        }

        $res_service = rtrim($request->date, "/");

        $date = explode('/', $res_service);

        $user = User::find($request->id);

        $amount_turnover_item = DB::table('list_of_payments as lop')
            ->join('list_of_payment_medicine_groups as lopm', 'lop.id', '=', 'lopm.list_of_payment_id')
            ->join('price_medicine_groups as pmg', 'lopm.medicine_group_id', '=', 'pmg.id')
            ->join('users', 'lop.user_id', '=', 'users.id')
            ->join('branches', 'users.branch_id', '=', 'branches.id')
            ->select(
                DB::raw("(CASE WHEN lopm.quantity = 0 THEN TRIM(SUM(pmg.doctor_fee)) ELSE TRIM(SUM(pmg.doctor_fee * lopm.quantity)) END)+0 as amount_turnover_item"))
            ->where('branches.id', '=', $user->branch_id);

        if ($request->date) {
            $amount_turnover_item = $amount_turnover_item
                ->where(DB::raw("MONTH(lopm.updated_at)"), $date[1])
                ->where(DB::raw("YEAR(lopm.updated_at)"), $date[2]);
        }

        $amount_turnover_item = $amount_turnover_item->first();

        //=====================================================

        $amount_turnover_service = DB::table('list_of_payments')
            ->join('check_up_results', 'list_of_payments.check_up_result_id', '=', 'check_up_results.id')
            ->join('list_of_payment_services', 'check_up_results.id', '=', 'list_of_payment_services.check_up_result_id')
            ->join('detail_service_patients', 'list_of_payment_services.detail_service_patient_id', '=', 'detail_service_patients.id')
            ->join('price_services', 'detail_service_patients.price_service_id', '=', 'price_services.id')
            ->join('users', 'check_up_results.user_id', '=', 'users.id')
            ->join('branches', 'users.branch_id', '=', 'branches.id')
            ->select(
                DB::raw("TRIM(SUM(price_services.doctor_fee * detail_service_patients.quantity))+0 as amount_turnover_service"))
            ->where('branches.id', '=', $user->branch_id);

        if ($request->date) {
            $amount_turnover_service = $amount_turnover_service
                ->where(DB::raw("MONTH(list_of_payment_services.updated_at)"), $date[1])
                ->where(DB::raw("YEAR(list_of_payment_services.updated_at)"), $date[2]);
        }

        $amount_turnover_service = $amount_turnover_service->first();

        $amount_turnover = $amount_turnover_item->amount_turnover_item + $amount_turnover_service->amount_turnover_service;

        //==============================================================

        $count_inpatient = DB::table('list_of_payments as lop')
            ->join('list_of_payment_services as lops', 'lops.list_of_payment_id', 'lop.id')
            ->join('detail_service_patients as dsp', 'lops.detail_service_patient_id', 'dsp.id')
            ->join('price_services as ps', 'dsp.price_service_id', 'ps.id')
            ->join('list_of_services as los', 'ps.list_of_services_id', 'los.id')
            ->join('users as usr', 'dsp.user_id', 'usr.id')
            ->join('branches as br', 'usr.branch_id', 'br.id')

            ->select(DB::raw("TRIM(SUM(dsp.quantity))+0 as count_inpatient"))
            ->where('br.id', '=', $user->branch_id)
            ->where('los.service_name', 'like', '%rawat inap%');

        if ($request->date) {
            $count_inpatient = $count_inpatient
                ->where(DB::raw("MONTH(lops.updated_at)"), $date[1])
                ->where(DB::raw("YEAR(lops.updated_at)"), $date[2]);
        }

        $count_inpatient = $count_inpatient
            ->first();

        $total_inpatient = 0;

        if (!is_null($count_inpatient->count_inpatient)) {
            $total_inpatient = $count_inpatient->count_inpatient;
        }

        $amount_surgery = DB::table('users as usr')
            ->join('branches as brn', 'usr.branch_id', 'brn.id')
            ->join('detail_medicine_group_check_up_results as dmg', 'dmg.user_id', 'usr.id')
            ->join('price_medicine_groups as pmg', 'dmg.medicine_group_id', 'pmg.id')
            ->join('medicine_groups as mg', 'pmg.medicine_group_id', 'mg.id')

            ->select(DB::raw("TRIM(SUM(pmg.doctor_fee))+0 as amount_surgery"));

        if ($request->date) {
            $amount_surgery = $amount_surgery
                ->where(DB::raw("MONTH(dmg.updated_at)"), $date[1])
                ->where(DB::raw("YEAR(dmg.updated_at)"), $date[2]);
        }

        $amount_surgery = $amount_surgery->where('brn.id', '=', $user->branch_id)
            ->where('mg.group_name', 'like', '%operasi%')
            ->first();

        if (is_null($amount_turnover)) {
            $amount_turnover = 0;
        }

        if (is_null($amount_surgery->amount_surgery)) {
            $amount_surgery->amount_surgery = 0;
        }

        //==============================================================

        $count_grooming = DB::table('list_of_payments as lop')
            ->join('list_of_payment_services as lops', 'lops.list_of_payment_id', 'lop.id')
            ->join('detail_service_patients as dsp', 'lops.detail_service_patient_id', 'dsp.id')
            ->join('price_services as ps', 'dsp.price_service_id', 'ps.id')
            ->join('list_of_services as los', 'ps.list_of_services_id', 'los.id')
            ->join('users as usr', 'dsp.user_id', 'usr.id')
            ->join('branches as br', 'usr.branch_id', 'br.id')

            ->select(DB::raw("TRIM(SUM(dsp.quantity))+0 as count_grooming"))
            ->where('br.id', '=', $user->branch_id)
            ->where('los.service_name', 'like', '%grooming%');

        if ($request->date) {
            $count_grooming = $count_grooming
                ->where(DB::raw("MONTH(lops.updated_at)"), $date[1])
                ->where(DB::raw("YEAR(lops.updated_at)"), $date[2]);
        }

        $count_grooming = $count_grooming
            ->first();

        $total_grooming = 0;

        if (!is_null($count_grooming->count_grooming)) {
            $total_grooming = $count_grooming->count_grooming;
        }

        $count_eat_query = DB::table('attendances')
            ->where('user_id', $request->id)
            ->where('status', 'hadir');

        if ($request->date) {
            $count_eat_query = $count_eat_query
                ->whereMonth('tanggal', $date[1])
                ->whereYear('tanggal', $date[2]);
        }

        $count_eat = $count_eat_query->count();

        return response()->json([
            'amount_turnover' => $amount_turnover,
            'count_inpatient' => $total_inpatient,
            'amount_surgery' => $amount_surgery->amount_surgery,
            'count_grooming' => $total_grooming,
            'count_eat' => $count_eat,
        ], 200);
    }

    public function update(Request $request)
    {
        if ($request->user()->role == 'dokter' || $request->user()->role == 'resepsionis') {
            return response()->json([
                'message' => 'The user role was invalid.',
                'errors' => ['Akses User tidak diizinkan!'],
            ], 403);
        }

        $validate = Validator::make($request->all(), [
            'id' => 'required|numeric',
            'date_payed' => 'required|date_format:d/m/Y',
            'user_employee_id' => 'required|numeric',
            'basic_sallary' => 'required|numeric|min:0',
            'accomodation' => 'required|numeric|min:0',
            'percentage_turnover' => 'required|numeric|min:0',
            'amount_turnover' => 'required|numeric|min:0',
            'total_turnover' => 'required|numeric|min:0',
            'amount_inpatient' => 'required|numeric|min:0',
            'count_inpatient' => 'required|numeric|min:0',
            'total_inpatient' => 'required|numeric|min:0',
            'percentage_surgery' => 'required|numeric|min:0',
            'amount_surgery' => 'required|numeric|min:0',
            'total_surgery' => 'required|numeric|min:0',
            'total_overall' => 'required|numeric|min:0',

            'amount_grooming' => 'required|numeric|min:0',
            'count_grooming' => 'required|numeric|min:0',
            'total_grooming' => 'required|numeric|min:0',

            'minus_turnover' => 'required|numeric|min:0',
            'eat' => 'required|numeric|min:0',
            'amount_eat' => 'required|numeric|min:0',
            'count_eat' => 'required|numeric|min:0',
            'fine' => 'required|numeric|min:0',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        $find_duplicate = db::table('payrolls')
            ->select('id')
            ->where('id', '=', $request->id)
            ->count();

        if ($find_duplicate == 0) {

            return response()->json([
                'message' => 'The data was invalid.',
                'errors' => ['Data tidak ada!'],
            ], 422);

        }

        $res_date = rtrim($request->date_payed, "/");

        $date = explode('/', $res_date);

        $date_inserted = $date[2] . '-' . $date[1] . '-' . $date[0];

        $payroll = Payroll::find($request->id);

        $payroll->date_payed = $date_inserted;
        $payroll->user_employee_id = $request->user_employee_id;
        $payroll->basic_sallary = $request->basic_sallary;
        $payroll->accomodation = $request->accomodation;
        $payroll->percentage_turnover = $request->percentage_turnover;
        $payroll->amount_turnover = $request->amount_turnover;
        $payroll->total_turnover = $request->total_turnover;
        $payroll->amount_inpatient = $request->amount_inpatient;
        $payroll->count_inpatient = $request->count_inpatient;
        $payroll->total_inpatient = $request->total_inpatient;
        $payroll->percentage_surgery = $request->percentage_surgery;
        $payroll->amount_surgery = $request->amount_surgery;
        $payroll->total_surgery = $request->total_surgery;
        $payroll->total_overall = $request->total_overall;
        $payroll->user_update_id = $request->user()->id;
        $payroll->updated_at = \Carbon\Carbon::now();

        $payroll->amount_grooming = $request->amount_grooming;
        $payroll->count_grooming = $request->count_grooming;
        $payroll->total_grooming = $request->total_grooming;

        $payroll->minus_turnover = $request->minus_turnover;
        $payroll->eat = $request->eat;
        $payroll->amount_eat = $request->amount_eat;
        $payroll->count_eat = $request->count_eat;
        $payroll->fine = $request->fine;

        $payroll->save();

        return response()->json([
            'message' => 'Berhasil mengubah Data',
        ], 200);
    }
    public function delete(Request $request)
    {

        if ($request->user()->role == 'dokter' || $request->user()->role == 'resepsionis') {
            return response()->json([
                'message' => 'The user role was invalid.',
                'errors' => ['Akses User tidak diizinkan!'],
            ], 403);
        }

        $data = Payroll::where('id', '=', $request->id)
            ->where('isDeleted', '=', 0)
            ->count();

        if ($data == 0) {
            return response()->json([
                'message' => 'The data was invalid.',
                'errors' => ['Data tidak ada ada!'],
            ], 422);
        }

        $data = Payroll::find($request->id);

        $data->user_update_id = $request->user()->id;
        $data->isDeleted = 1;
        $data->deleted_by = $request->user()->id;
        $data->updated_at = \Carbon\Carbon::now();
        $data->deleted_at = \Carbon\Carbon::now();
        $data->save();

        return response()->json([
            'message' => 'Berhasil menghapus Data',
        ], 200);

    }

    public function generate(Request $request)
    {

        $terbilang = new Terbilang();

        $data_user = DB::table('payrolls as py')
            ->join('users', 'py.user_employee_id', '=', 'users.id')
            ->join('branches', 'users.branch_id', '=', 'branches.id')
            ->select(
                'py.id as id',
                'users.fullname as fullname',
                DB::raw("DATE_FORMAT(py.date_payed, '%d/%m/%Y') as date_payed"),
                DB::raw("DATE_FORMAT(py.date_payed, '%Y/%m/%d') as date_period"),
                DB::raw("DATE_FORMAT(py.date_payed, '%d %b %Y') as date_payed_diff_format"),
                'users.staffing_number as staffing_number',
                'users.phone_number as phone_number',
                'users.address as address',
                'users.role as role',
                'branches.branch_name as branch_name',
                'branches.address as branch_address',
                'py.basic_sallary as basic_sallary',
                'py.accomodation as accomodation',
                'py.total_turnover as total_turnover',
                'py.total_inpatient as total_inpatient',
                'py.total_surgery as total_surgery',
                'py.total_grooming as total_grooming',
                'py.eat as eat',
                'py.fine as fine',
                DB::raw("TRIM(py.total_overall)+0 as total_overall"),
            )
            ->where('py.id', '=', $request->id)
            ->get();

        $data = [
            'data_user' => $data_user,
            'terbilang' => $terbilang->convert($data_user[0]->total_overall),
            'month_period' => \Carbon\Carbon::parse($data_user[0]->date_period)->locale('id')->isoFormat('MMMM YYYY'),
        ];

        $pdf = PDF::loadview('sallary-slip', $data);

        return $pdf->download('Slip Gaji ' . $data_user[0]->fullname . ' ' . \Carbon\Carbon::parse($data_user[0]->date_period)->locale('id')->isoFormat('MMMM YYYY') . '.pdf');
    }

}
