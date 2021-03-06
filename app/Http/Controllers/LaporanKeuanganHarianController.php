<?php

namespace App\Http\Controllers;

use App\Exports\LaporanKeuanganHarian;
use App\Models\ListofPayments;
use DB;
use Illuminate\Http\Request;

class LaporanKeuanganHarianController extends Controller
{
    public function index(Request $request)
    {

        if ($request->user()->role == 'resepsionis' && $request->user()->role == 'dokter') {
            return response()->json([
                'message' => 'The user role was invalid.',
                'errors' => ['Akses User tidak diizinkan!'],
            ], 403);
        }

        $data = DB::table('list_of_payments')
            ->join('check_up_results', 'list_of_payments.check_up_result_id', '=', 'check_up_results.id')

            ->join('list_of_payment_items', 'check_up_results.id', '=', 'list_of_payment_items.check_up_result_id')
            ->join('detail_item_patients', 'list_of_payment_items.detail_item_patient_id', '=', 'detail_item_patients.id')
            ->join('price_items', 'detail_item_patients.price_item_id', '=', 'price_items.id')

            ->join('list_of_payment_services', 'check_up_results.id', '=', 'list_of_payment_services.check_up_result_id')
            ->join('detail_service_patients', 'list_of_payment_services.detail_service_patient_id', '=', 'detail_service_patients.id')
            ->join('price_services', 'detail_service_patients.price_service_id', '=', 'price_services.id')

            ->join('users', 'check_up_results.user_id', '=', 'users.id')
            ->join('branches', 'users.branch_id', '=', 'branches.id')
            ->join('registrations', 'check_up_results.patient_registration_id', '=', 'registrations.id')
            ->join('patients', 'registrations.patient_id', '=', 'patients.id')
            ->select('list_of_payments.id as list_of_payment_id', 'check_up_results.id as check_up_result_id',
                'check_up_results.status_outpatient_inpatient as status_outpatient_inpatient', 'registrations.id_number as registration_number',
                'patients.id_member as patient_number', 'patients.pet_category', 'patients.pet_name', 'registrations.complaint',

                DB::raw("TRIM(SUM(detail_item_patients.price_overall) + SUM(detail_service_patients.price_overall))+0 as price_overall"),
                DB::raw("TRIM(SUM(price_items.capital_price * detail_item_patients.quantity) + SUM(price_services.capital_price * detail_service_patients.quantity))+0 as capital_price"),
                DB::raw("TRIM(SUM(price_items.doctor_fee * detail_item_patients.quantity) + SUM(price_services.doctor_fee * detail_service_patients.quantity))+0 as doctor_fee"),
                DB::raw("TRIM(SUM(price_items.petshop_fee * detail_item_patients.quantity) + SUM(price_services.petshop_fee * detail_service_patients.quantity))+0 as petshop_fee"),

                'users.fullname as created_by', DB::raw("DATE_FORMAT(list_of_payments.created_at, '%d %b %Y') as created_at"))
            ->groupBy('list_of_payments.id', 'check_up_results.id', 'check_up_results.status_outpatient_inpatient', 'registrations.id_number', 'patients.id_member', 'patients.pet_category', 'patients.pet_name',
                'registrations.complaint', 'users.fullname', 'list_of_payments.created_at');

        if ($request->branch_id && $request->user()->role == 'admin') {
            $data = $data->where('branches.id', '=', $request->branch_id);
        }

        if ($request->date) {
            $data = $data->where('list_of_payments.created_at', '=', $request->date);
        }

        if ($request->orderby) {

            $data = $data->orderBy($request->column, $request->orderby);
        } else {
            $data = $data->orderBy('list_of_payments.id', 'asc');
        }

        $data = $data->get();

        $price_overall = DB::table('list_of_payments')
            ->join('check_up_results', 'list_of_payments.check_up_result_id', '=', 'check_up_results.id')

            ->join('list_of_payment_items', 'check_up_results.id', '=', 'list_of_payment_items.check_up_result_id')
            ->join('detail_item_patients', 'list_of_payment_items.detail_item_patient_id', '=', 'detail_item_patients.id')
            ->join('price_items', 'detail_item_patients.price_item_id', '=', 'price_items.id')

            ->join('list_of_payment_services', 'check_up_results.id', '=', 'list_of_payment_services.check_up_result_id')
            ->join('detail_service_patients', 'list_of_payment_services.detail_service_patient_id', '=', 'detail_service_patients.id')
            ->join('price_services', 'detail_service_patients.price_service_id', '=', 'price_services.id')
            ->select(
                DB::raw("TRIM(SUM(detail_item_patients.price_overall) + SUM(detail_service_patients.price_overall))+0 as price_overall"));

        if ($request->date) {
            $price_overall = $price_overall->where('list_of_payments.created_at', '=', $request->date);
        }
        $price_overall = $price_overall->first();

        $capital_price = DB::table('list_of_payments')
            ->join('check_up_results', 'list_of_payments.check_up_result_id', '=', 'check_up_results.id')

            ->join('list_of_payment_items', 'check_up_results.id', '=', 'list_of_payment_items.check_up_result_id')
            ->join('detail_item_patients', 'list_of_payment_items.detail_item_patient_id', '=', 'detail_item_patients.id')
            ->join('price_items', 'detail_item_patients.price_item_id', '=', 'price_items.id')

            ->join('list_of_payment_services', 'check_up_results.id', '=', 'list_of_payment_services.check_up_result_id')
            ->join('detail_service_patients', 'list_of_payment_services.detail_service_patient_id', '=', 'detail_service_patients.id')
            ->join('price_services', 'detail_service_patients.price_service_id', '=', 'price_services.id')
            ->select(
                DB::raw("TRIM(SUM(price_items.capital_price * detail_item_patients.quantity) + SUM(price_services.capital_price * detail_service_patients.quantity))+0 as capital_price"));

        if ($request->date) {
            $capital_price = $capital_price->where('list_of_payments.created_at', '=', $request->date);
        }
        $capital_price = $capital_price->first();

        $doctor_fee = DB::table('list_of_payments')
            ->join('check_up_results', 'list_of_payments.check_up_result_id', '=', 'check_up_results.id')

            ->join('list_of_payment_items', 'check_up_results.id', '=', 'list_of_payment_items.check_up_result_id')
            ->join('detail_item_patients', 'list_of_payment_items.detail_item_patient_id', '=', 'detail_item_patients.id')
            ->join('price_items', 'detail_item_patients.price_item_id', '=', 'price_items.id')

            ->join('list_of_payment_services', 'check_up_results.id', '=', 'list_of_payment_services.check_up_result_id')
            ->join('detail_service_patients', 'list_of_payment_services.detail_service_patient_id', '=', 'detail_service_patients.id')
            ->join('price_services', 'detail_service_patients.price_service_id', '=', 'price_services.id')
            ->select(
                DB::raw("TRIM(SUM(price_items.doctor_fee * detail_item_patients.quantity) + SUM(price_services.doctor_fee * detail_service_patients.quantity))+0 as doctor_fee"));

        if ($request->date) {
            $doctor_fee = $doctor_fee->where('list_of_payments.created_at', '=', $request->date);
        }
        $doctor_fee = $doctor_fee->first();

        $petshop_fee = DB::table('list_of_payments')
            ->join('check_up_results', 'list_of_payments.check_up_result_id', '=', 'check_up_results.id')

            ->join('list_of_payment_items', 'check_up_results.id', '=', 'list_of_payment_items.check_up_result_id')
            ->join('detail_item_patients', 'list_of_payment_items.detail_item_patient_id', '=', 'detail_item_patients.id')
            ->join('price_items', 'detail_item_patients.price_item_id', '=', 'price_items.id')

            ->join('list_of_payment_services', 'check_up_results.id', '=', 'list_of_payment_services.check_up_result_id')
            ->join('detail_service_patients', 'list_of_payment_services.detail_service_patient_id', '=', 'detail_service_patients.id')
            ->join('price_services', 'detail_service_patients.price_service_id', '=', 'price_services.id')
            ->select(
                DB::raw("TRIM(SUM(price_items.petshop_fee * detail_item_patients.quantity) + SUM(price_services.petshop_fee * detail_service_patients.quantity))+0 as petshop_fee"));

        if ($request->date) {
            $petshop_fee = $petshop_fee->where('list_of_payments.created_at', '=', $request->date);
        }
        $petshop_fee = $petshop_fee->first();

        return response()->json([
            'data' => $data,
            'price_overall' => $price_overall->price_overall,
            'capital_price' => $capital_price->capital_price,
            'doctor_fee' => $doctor_fee->doctor_fee,
            'petshop_fee' => $petshop_fee->petshop_fee,
        ], 200);
    }

    public function detail(Request $request)
    {
        if ($request->user()->role == 'resepsionis' && $request->user()->role == 'dokter') {
            return response()->json([
                'message' => 'The user role was invalid.',
                'errors' => ['Akses User tidak diizinkan!'],
            ], 403);
        }

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
            ->select('registrations.id_number as registration_number', 'patients.id as patient_id', 'patients.id_member as patient_number', 'patients.pet_category',
                'patients.pet_name', 'patients.pet_gender', 'patients.pet_year_age', 'patients.pet_month_age', 'patients.owner_name', 'patients.owner_address',
                'patients.owner_phone_number', 'registrations.complaint', 'registrations.registrant')
            ->where('registrations.id', '=', $check_up_result->patient_registration_id)
            ->first();

        $data->registration = $registration;

        $list_of_payment_services = DB::table('list_of_payment_services')
            ->join('detail_service_patients', 'list_of_payment_services.detail_service_patient_id', '=', 'detail_service_patients.id')
            ->join('price_services', 'detail_service_patients.price_service_id', '=', 'price_services.id')
            ->join('list_of_services', 'price_services.list_of_services_id', '=', 'list_of_services.id')
            ->join('service_categories', 'list_of_services.service_category_id', '=', 'service_categories.id')
            ->join('users', 'detail_service_patients.user_id', '=', 'users.id')
            ->select('detail_service_patients.id as detail_service_patient_id', 'price_services.id as price_service_id',
                'list_of_services.id as list_of_service_id', 'list_of_services.service_name',
                'detail_service_patients.quantity',
                'service_categories.category_name',
                DB::raw("TRIM(detail_service_patients.price_overall )+0 as price_overall"),
                DB::raw("TRIM(price_services.selling_price)+0 as selling_price"),
                DB::raw("TRIM(price_services.capital_price * detail_service_patients.quantity)+0 as capital_price"),
                DB::raw("TRIM(price_services.doctor_fee * detail_service_patients.quantity)+0 as doctor_fee"),
                DB::raw("TRIM(price_services.petshop_fee * detail_service_patients.quantity)+0 as petshop_fee"),
                'users.fullname as created_by', DB::raw("DATE_FORMAT(detail_service_patients.created_at, '%d %b %Y') as created_at"))
            ->where('list_of_payment_services.check_up_result_id', '=', $data->check_up_result_id)
            ->orderBy('list_of_payment_services.id', 'desc')
            ->get();

        $data['list_of_payment_services'] = $list_of_payment_services;

        $item = DB::table('list_of_payment_items')
            ->join('detail_item_patients', 'list_of_payment_items.detail_item_patient_id', '=', 'detail_item_patients.id')
            ->join('price_medicine_groups', 'detail_item_patients.medicine_group_id', '=', 'price_medicine_groups.id')
            ->join('medicine_groups', 'price_medicine_groups.medicine_group_id', '=', 'medicine_groups.id')
            ->join('branches', 'medicine_groups.branch_id', '=', 'branches.id')
            ->select('price_medicine_groups.id as price_medicine_group_id', DB::raw("TRIM(price_medicine_groups.selling_price)+0 as selling_price"), 'detail_item_patients.medicine_group_id as medicine_group_id',
                'medicine_groups.group_name', 'branches.id as branch_id', 'branches.branch_name')
            ->where('list_of_payment_items.check_up_result_id', '=', $data->check_up_result_id)
            ->groupBy('price_medicine_groups.id', 'price_medicine_groups.selling_price', 'detail_item_patients.medicine_group_id', 'medicine_groups.group_name', 'branches.id', 'branches.branch_name')
            ->get();

        foreach ($item as $value) {

            $detail_item = DB::table('list_of_payment_items')
                ->join('detail_item_patients', 'list_of_payment_items.detail_item_patient_id', '=', 'detail_item_patients.id')
                ->join('price_items', 'detail_item_patients.price_item_id', '=', 'price_items.id')
                ->join('list_of_items', 'price_items.list_of_items_id', '=', 'list_of_items.id')
                ->join('category_item', 'list_of_items.category_item_id', '=', 'category_item.id')
                ->join('unit_item', 'list_of_items.unit_item_id', '=', 'unit_item.id')
                ->join('users', 'detail_item_patients.user_id', '=', 'users.id')
                ->select('detail_item_patients.id as detail_item_patients_id', 'list_of_items.id as list_of_item_id', 'price_items.id as price_item_id', 'list_of_items.item_name', 'detail_item_patients.quantity',
                    'unit_item.unit_name',
                    'category_item.category_name',
                    DB::raw("TRIM(detail_item_patients.price_overall)+0 as price_overall"),
                    DB::raw("TRIM(price_items.selling_price)+0 as selling_price"),
                    DB::raw("TRIM(price_items.capital_price * detail_item_patients.quantity)+0 as capital_price"),
                    DB::raw("TRIM(price_items.doctor_fee * detail_item_patients.quantity)+0 as doctor_fee"),
                    DB::raw("TRIM(price_items.petshop_fee * detail_item_patients.quantity)+0 as petshop_fee"),
                    'users.fullname as created_by', DB::raw("DATE_FORMAT(detail_item_patients.created_at, '%d %b %Y') as created_at"))
                ->where('list_of_payment_items.check_up_result_id', '=', $data->check_up_result_id)
                ->where('detail_item_patients.medicine_group_id', '=', $value->medicine_group_id)
                ->orderBy('detail_item_patients.id', 'desc')
                ->get();

            $value->list_of_medicine = $detail_item;
        }

        $data['item'] = $item;

        $inpatient = DB::table('in_patients')
            ->join('users', 'in_patients.user_id', '=', 'users.id')
            ->select('in_patients.description', DB::raw("DATE_FORMAT(in_patients.created_at, '%d %b %Y') as created_at"),
                'users.fullname as created_by')
            ->where('in_patients.check_up_result_id', '=', $data->check_up_result_id)
            ->get();

        $data['inpatient'] = $inpatient;

        return response()->json($data, 200);

    }

    public function download_excel(Request $request)
    {
        if ($request->user()->role == 'resepsionis' && $request->user()->role == 'dokter') {
            return response()->json([
                'message' => 'The user role was invalid.',
                'errors' => ['Akses User tidak diizinkan!'],
            ], 403);
        }

        return (new LaporanKeuanganHarian($request->orderby, $request->column, $request->date, $request->branch_id))->download('Laporan Keuangan Harian.xlsx');
    }
}
