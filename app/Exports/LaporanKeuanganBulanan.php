<?php

namespace App\Exports;

use DB;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;

class LaporanKeuanganBulanan implements FromView, WithTitle
{

    protected $orderby;
    protected $column;
    protected $month;
    protected $year;
    protected $branch_id;
    protected $title_name;

    public function __construct($orderby, $column, $month, $year, $branch_id, $title_name)
    {
        $this->orderby = $orderby;
        $this->column = $column;
        $this->month = $month;
        $this->year = $year;
        $this->branch_id = $branch_id;
        $this->title_name = $title_name;
    }

    public function view(): View
    {

        $list_date = DB::table('list_of_payments as lop')
            ->join('users', 'lop.user_id', 'users.id')
            ->join('branches', 'users.branch_id', 'branches.id')
            ->select(DB::raw("DATE(lop.updated_at) as date"));

        if ($this->branch_id) {
            $list_date = $list_date->where('branches.id', '=', $this->branch_id);
        }

        if ($this->month && $this->year) {
            $list_date = $list_date->where(DB::raw('MONTH(lop.updated_at)'), '=', $this->month)
                ->where(DB::raw('YEAR(lop.updated_at)'), '=', $this->year);
        }

        $list_date = $list_date->groupby(DB::raw("DATE(lop.updated_at)"))
            ->get();

        $array = array();

        foreach ($list_date as $result_data) {

            $item = DB::table('list_of_payments as lop')
                ->join('check_up_results as cur', 'lop.check_up_result_id', '=', 'cur.id')
                ->join('list_of_payment_medicine_groups as lopm', 'lopm.list_of_payment_id', '=', 'lop.id')
                ->join('price_medicine_groups as pmg', 'lopm.medicine_group_id', '=', 'pmg.id')
                ->join('medicine_groups', 'pmg.medicine_group_id', '=', 'medicine_groups.id')
                ->join('registrations as reg', 'cur.patient_registration_id', '=', 'reg.id')
                ->join('patients as p', 'reg.patient_id', '=', 'p.id')
                ->join('users', 'lop.user_id', '=', 'users.id')
                ->join('branches', 'users.branch_id', '=', 'branches.id')

                ->select(
                    'medicine_groups.group_name as action',
                    DB::raw("TRIM(SUM(pmg.capital_price))+0 as capital_price"),
                    DB::raw("TRIM(SUM(pmg.selling_price))+0 as selling_price"),
                    DB::raw("TRIM(SUM(pmg.petshop_fee))+0 as petshop_fee"),
                    DB::raw("TRIM(SUM(pmg.doctor_fee))+0 as doctor_fee"),
                    'p.pet_name as pet_name',
                    'p.owner_name as owner_name',
                    'branches.id as branchId',
                    DB::raw("DATE_FORMAT(lopm.updated_at, '%d/%m/%Y') as created_at")
                )
                ->where(DB::raw("DATE(lopm.updated_at)"), '=', $result_data->date)
                ->groupBy('lopm.detail_medicine_group_check_up_result_id')
                ->orderBy('cur.id', 'asc');

            $service = DB::table('list_of_payments')
                ->join('check_up_results', 'list_of_payments.check_up_result_id', '=', 'check_up_results.id')
                ->join('list_of_payment_services', 'check_up_results.id', '=', 'list_of_payment_services.check_up_result_id')
                ->join('detail_service_patients', 'list_of_payment_services.detail_service_patient_id', '=', 'detail_service_patients.id')
                ->join('price_services', 'detail_service_patients.price_service_id', '=', 'price_services.id')
                ->join('list_of_services', 'price_services.list_of_services_id', '=', 'list_of_services.id')
                ->join('registrations', 'check_up_results.patient_registration_id', '=', 'registrations.id')
                ->join('patients', 'registrations.patient_id', '=', 'patients.id')
                ->join('users', 'check_up_results.user_id', '=', 'users.id')
                ->join('branches', 'users.branch_id', '=', 'branches.id')

                ->select(
                    'list_of_services.service_name as action',
                    DB::raw("TRIM(price_services.capital_price * detail_service_patients.quantity)+0 as capital_price"),
                    DB::raw("TRIM(detail_service_patients.price_overall)+0 as selling_price"),
                    DB::raw("TRIM(price_services.petshop_fee * detail_service_patients.quantity)+0 as petshop_fee"),
                    DB::raw("TRIM(price_services.doctor_fee * detail_service_patients.quantity)+0 as doctor_fee"),
                    'patients.pet_name as pet_name',
                    'patients.owner_name as owner_name',
                    'branches.id as branchId',
                    DB::raw("DATE_FORMAT(list_of_payment_services.updated_at, '%d/%m/%Y') as created_at")
                )
                ->where(DB::raw("DATE(list_of_payment_services.updated_at)"), '=', $result_data->date)
                ->orderBy('check_up_results.id', 'asc')
                ->union($item);

            $data = DB::query()->fromSub($service, 'p_pn')
                ->select('action',
                    'capital_price',
                    'selling_price',
                    'petshop_fee',
                    'doctor_fee',
                    'pet_name',
                    'owner_name',
                    'branchId',
                    'created_at');

            if ($this->branch_id) {
                $data = $data->where('branchId', '=', $this->branch_id);
            }

            if ($this->orderby) {

                $data = $data->orderBy($this->column, $this->orderby);
            } else {
                $data = $data->orderBy('list_of_payment_id', 'desc');
            }

            $data = $data->orderBy('check_up_result_id', 'asc')
                ->get();

            $array[] = $data;
        }

        return view('laporan-keuangan', [
            'data' => $array,
        ]);
    }

    public function title(): string
    {
        return $this->title_name;
    }
}
