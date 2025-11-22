<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Payroll;
use Illuminate\Http\Request;
use Carbon\Carbon;
use DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Border;

class RekapController extends Controller
{
  public function listperiode()
  {
    $data = [
      ['id' => 1, 'periode' => 'Bulanan'],
      ['id' => 2, 'periode' => 'Tahunan'],
    ];

    return response()->json($data, 200);
  }

  public function index(Request $request)
  {
    if ($request->user()->role == 'dokter' || $request->user()->role == 'resepsionis') {
      return response()->json([
        'message' => 'The user role was invalid.',
        'errors' => ['Akses User tidak diizinkan!'],
      ], 403);
    }

    $lastPeriods = collect();
    $listDates = [];

    if ($request->periode == 2) {

      $currentYear = Carbon::now()->year;

      // Generate an array of the last 5 years
      for ($i = 0; $i < 5; $i++) {

        $lastPeriods->push([
          'year' => $currentYear - $i  // Full year (e.g., 2025)
        ]);

        $listDates[] = [
          'dates' => $currentYear - $i,  // Month name in Indonesian (e.g., Januari, Februari)
        ];
      }
    } else {

      $month = Carbon::now()->addMonth(-13);

      for ($i = 0; $i < 13; $i++) {

        $month = $month->addMonth();

        if ($month->day !== 1) {
          $month->day(1);  // Set it to the 1st day of the next month
        }

        $lastPeriods->push([
          'month' => $month->format('n'),  // Month number (e.g., 1 for January)
          'year' => $month->format('Y')    // Full year (e.g., 2025)
        ]);
      }

      Carbon::setLocale('id');

      $startDate = Carbon::now()->addMonth(-13);

      for ($i = 0; $i < 13; $i++) {
        // Get the month and year for the current iteration (starting from now)
        $monthYear = $startDate->addMonth();

        if ($monthYear->day !== 1) {
          $monthYear->day(1);  // Set it to the 1st day of the next month
        }

        $listDates[] = [
          'dates' => $monthYear->translatedFormat('F Y'),  // Month name in Indonesian (e.g., Januari, Februari)
        ];
      }
    }

    $datas = collect();

    $i = 0;

    foreach ($lastPeriods as $val) {
      //total omset
      $price_overall_item = DB::table('list_of_payments as lop')
        ->join('list_of_payment_medicine_groups as lopm', 'lop.id', '=', 'lopm.list_of_payment_id')
        ->join('price_medicine_groups as pmg', 'lopm.medicine_group_id', '=', 'pmg.id')
        ->join('users', 'lop.user_id', '=', 'users.id')
        ->join('branches', 'users.branch_id', '=', 'branches.id')
        ->select(
          DB::raw("(CASE WHEN lopm.quantity = 0 THEN TRIM(SUM(pmg.selling_price)) ELSE TRIM(SUM(pmg.selling_price * lopm.quantity)) END)+0 as price_overall")
        );

      if ($request->branch_id) {
        $price_overall_item = $price_overall_item->where('branches.id', '=', $request->branch_id);
      }

      if ($request->periode == 2) {
        $price_overall_item = $price_overall_item->where(DB::raw("YEAR(lopm.updated_at)"), $val['year']);
      } else {
        $price_overall_item = $price_overall_item->where(DB::raw("MONTH(lopm.updated_at)"), $val['month'])
          ->where(DB::raw("YEAR(lopm.updated_at)"), $val['year']);
      }
      $price_overall_item = $price_overall_item->first();

      $price_overall_service = DB::table('list_of_payments')
        ->join('check_up_results', 'list_of_payments.check_up_result_id', '=', 'check_up_results.id')
        ->join('list_of_payment_services', 'check_up_results.id', '=', 'list_of_payment_services.check_up_result_id')
        ->join('detail_service_patients', 'list_of_payment_services.detail_service_patient_id', '=', 'detail_service_patients.id')
        ->join('price_services', 'detail_service_patients.price_service_id', '=', 'price_services.id')
        ->join('users', 'check_up_results.user_id', '=', 'users.id')
        ->join('branches', 'users.branch_id', '=', 'branches.id')
        ->select(
          DB::raw("TRIM(SUM(detail_service_patients.price_overall))+0 as price_overall")
        );

      if ($request->branch_id) {
        $price_overall_service = $price_overall_service->where('branches.id', '=', $request->branch_id);
      }

      if ($request->periode == 2) {
        $price_overall_service = $price_overall_service->where(DB::raw("YEAR(list_of_payment_services.updated_at)"), $val['year']);
      } else {
        $price_overall_service = $price_overall_service->where(DB::raw("MONTH(list_of_payment_services.updated_at)"), $val['month'])
          ->where(DB::raw("YEAR(list_of_payment_services.updated_at)"), $val['year']);
      }
      $price_overall_service = $price_overall_service->first();

      $price_overall_shop_clinic = DB::table('payment_petshop_with_clinics as ppwc')
        ->join('price_item_pet_shops as pip', 'ppwc.price_item_pet_shop_id', 'pip.id')
        ->join('users', 'ppwc.user_id', '=', 'users.id')
        ->join('branches', 'users.branch_id', '=', 'branches.id')
        ->select(DB::raw("TRIM(SUM(pip.selling_price * ppwc.total_item))+0 as price_overall"));

      if ($request->branch_id) {
        $price_overall_shop_clinic = $price_overall_shop_clinic->where('branches.id', '=', $request->branch_id);
      }

      if ($request->periode == 2) {

        $price_overall_shop_clinic = $price_overall_shop_clinic->where(DB::raw('YEAR(ppwc.created_at)'), '=', $val['year']);
      } else {
        $price_overall_shop_clinic = $price_overall_shop_clinic->where(DB::raw('MONTH(ppwc.created_at)'), '=', $val['month'])
          ->where(DB::raw('YEAR(ppwc.created_at)'), '=', $val['year']);
      }
      $price_overall_shop_clinic = $price_overall_shop_clinic->first();

      //=============================
      $price_overall_shop = DB::table('payment_petshops as pp')
        ->join('price_item_pet_shops as pip', 'pp.price_item_pet_shop_id', 'pip.id')
        ->join('users', 'pp.user_id', '=', 'users.id')
        ->join('branches', 'users.branch_id', '=', 'branches.id')
        ->select(DB::raw("TRIM(SUM(pip.selling_price * pp.total_item))+0 as price_overall"));

      if ($request->branch_id) {
        $price_overall_shop = $price_overall_shop->where('branches.id', '=', $request->branch_id);
      }

      if ($request->periode == 2) {
        $price_overall_shop = $price_overall_shop->where(DB::raw('YEAR(pp.created_at)'), '=', $val['year']);
      } else {
        $price_overall_shop = $price_overall_shop->where(DB::raw('MONTH(pp.created_at)'), '=', $val['month'])
          ->where(DB::raw('YEAR(pp.created_at)'), '=', $val['year']);
      }
      $price_overall_shop = $price_overall_shop->first();

      $price_overall = $price_overall_service->price_overall + $price_overall_item->price_overall
        + $price_overall_shop_clinic->price_overall + $price_overall_shop->price_overall;


      //discount
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

      if ($request->branch_id) {
        $amount_discount_item = $amount_discount_item->where('branches.id', '=', $request->branch_id);
      }

      if ($request->periode == 2) {
        $amount_discount_item = $amount_discount_item->where(DB::raw("YEAR(lopm.updated_at)"), $val['year']);
      } else {
        $amount_discount_item = $amount_discount_item->where(DB::raw("MONTH(lopm.updated_at)"), $val['month'])
          ->where(DB::raw("YEAR(lopm.updated_at)"), $val['year']);
      }
      $amount_discount_item = $amount_discount_item->first();

      $amount_discount_service = DB::table('list_of_payments')
        ->join('check_up_results', 'list_of_payments.check_up_result_id', '=', 'check_up_results.id')
        ->join('list_of_payment_services', 'check_up_results.id', '=', 'list_of_payment_services.check_up_result_id')
        ->join('detail_service_patients', 'list_of_payment_services.detail_service_patient_id', '=', 'detail_service_patients.id')
        ->join('price_services', 'detail_service_patients.price_service_id', '=', 'price_services.id')
        ->join('users', 'check_up_results.user_id', '=', 'users.id')
        ->join('branches', 'users.branch_id', '=', 'branches.id')
        ->select(
          DB::raw("TRIM(SUM(list_of_payment_services.amount_discount))+0 as amount_discount")
        );

      if ($request->branch_id) {
        $amount_discount_service = $amount_discount_service->where('branches.id', '=', $request->branch_id);
      }

      if ($request->periode == 2) {
        $amount_discount_service = $amount_discount_service->where(DB::raw("YEAR(list_of_payment_services.updated_at)"), $val['year']);
      } else {
        $amount_discount_service = $amount_discount_service->where(DB::raw("MONTH(list_of_payment_services.updated_at)"), $val['month'])
          ->where(DB::raw("YEAR(list_of_payment_services.updated_at)"), $val['year']);
      }

      $amount_discount_service = $amount_discount_service->first();

      $amount_discount = $amount_discount_item->amount_discount + $amount_discount_service->amount_discount;

      // //expenses
      $expenses = DB::table('expenses as e')
        ->join('users as u', 'e.user_id_spender', '=', 'u.id')
        ->join('branches as b', 'u.branch_id', '=', 'b.id')
        ->select(DB::raw("TRIM(SUM(IFNULL(e.amount_overall,0)))+0 as amount_overall"));

      if ($request->branch_id) {
        $expenses = $expenses->where('b.id', '=', $request->branch_id);
      }

      if ($request->periode == 2) {
        $expenses = $expenses->where(DB::raw("YEAR(e.date_spend)"), $val['year']);
      } else {
        $expenses = $expenses->where(DB::raw("MONTH(e.date_spend)"), $val['month'])
          ->where(DB::raw("YEAR(e.date_spend)"), $val['year']);
      }

      $expenses = $expenses->first();

      $total_expenses = 0;

      if (!is_null($expenses->amount_overall)) {

        $total_expenses = $expenses->amount_overall;
      }


      //sallary
      $payrolls = DB::table('payrolls as py')
        ->join('users', 'py.user_employee_id', '=', 'users.id')
        ->join('branches', 'users.branch_id', '=', 'branches.id')
        ->where('py.isDeleted', '=', 0);

      if ($request->periode == 2) {

        $payrolls = $payrolls->whereYear('py.date_payed', $val['year']);
      } else {

        $payrolls = $payrolls->whereYear('py.date_payed', $val['year'])->whereMonth('py.date_payed', $val['month']);
      }

      // if ($request->monthFrom && $request->monthTo && $request->yearFrom && $request->yearTo) {
      //   $payrolls = $payrolls
      //     ->whereBetween(DB::raw('MONTH(py.created_at)'), [$startMonth, $endMonth]);
      // } else {

      //   $payrolls = $payrolls
      //     ->whereYear('py.date_payed', $val['year'])
      //     ->whereMonth('py.date_payed', $val['month']);
      // }

      if ($request->branch_id) {
        $payrolls = $payrolls->where('branches.id', '=', $request->branch_id);
      }

      $firstPayroll = $payrolls->sum('total_overall');

      $firstPayroll = (float) $firstPayroll;

      $netto = $price_overall - $amount_discount - $total_expenses - $firstPayroll;

      $datas->push([
        'dates' => $listDates[$i]['dates'],
        'total_omset' => $price_overall,
        'discount' => $amount_discount,
        'expenses' => $total_expenses,
        'sallary' => $firstPayroll,
        'netto' => $netto
      ]);

      $i++;
    }

    return response()->json($datas, 200);
  }

  public function chart(Request $request)
  {
    if ($request->user()->role == 'dokter' || $request->user()->role == 'resepsionis') {
      return response()->json([
        'message' => 'The user role was invalid.',
        'errors' => ['Akses User tidak diizinkan!'],
      ], 403);
    }

    $lastPeriods = collect();
    $listDates = [];

    if ($request->periode == 2) {

      $currentYear = Carbon::now()->addYear(-4)->year;

      // Generate an array of the last 5 years
      for ($i = 0; $i < 5; $i++) {

        $lastPeriods->push([
          'year' => $currentYear + $i  // Full year (e.g., 2025)
        ]);

        $listDates[] = [
          'dates' => $currentYear + $i,  // Month name in Indonesian (e.g., Januari, Februari)
        ];
      }
    } else {

      $month = Carbon::now()->addMonth(-13);

      for ($i = 0; $i < 13; $i++) {

        $month = $month->addMonth();

        if ($month->day !== 1) {
          $month->day(1);  // Set it to the 1st day of the next month
        }

        $lastPeriods->push([
          'month' => $month->format('n'),  // Month number (e.g., 1 for January)
          'year' => $month->format('Y')    // Full year (e.g., 2025)
        ]);
      }

      Carbon::setLocale('id');

      $startDate = Carbon::now()->addMonth(-13);

      for ($i = 0; $i < 13; $i++) {
        // Get the month and year for the current iteration (starting from now)
        $monthYear = $startDate->addMonth();

        if ($monthYear->day !== 1) {
          $monthYear->day(1);  // Set it to the 1st day of the next month
        }

        $listDates[] = [
          'dates' => $monthYear->translatedFormat('M Y'),  // Month name in Indonesian (e.g., Januari, Februari)
        ];
      }
    }

    $datas = collect();
    $i = 0;

    //return $listDates;

    //return $lastPeriods;

    foreach ($lastPeriods as $val) {
      //total omset
      $price_overall_item = DB::table('list_of_payments as lop')
        ->join('list_of_payment_medicine_groups as lopm', 'lop.id', '=', 'lopm.list_of_payment_id')
        ->join('price_medicine_groups as pmg', 'lopm.medicine_group_id', '=', 'pmg.id')
        ->join('users', 'lop.user_id', '=', 'users.id')
        ->join('branches', 'users.branch_id', '=', 'branches.id')
        ->select(
          DB::raw("(CASE WHEN lopm.quantity = 0 THEN TRIM(SUM(pmg.selling_price)) ELSE TRIM(SUM(pmg.selling_price * lopm.quantity)) END)+0 as price_overall")
        );

      if ($request->branch_id) {
        $price_overall_item = $price_overall_item->where('branches.id', '=', $request->branch_id);
      }

      if ($request->periode == 2) {
        $price_overall_item = $price_overall_item->where(DB::raw("YEAR(lopm.updated_at)"), $val['year']);
      } else {
        $price_overall_item = $price_overall_item->where(DB::raw("MONTH(lopm.updated_at)"), $val['month'])
          ->where(DB::raw("YEAR(lopm.updated_at)"), $val['year']);
      }
      $price_overall_item = $price_overall_item->first();

      $price_overall_service = DB::table('list_of_payments')
        ->join('check_up_results', 'list_of_payments.check_up_result_id', '=', 'check_up_results.id')
        ->join('list_of_payment_services', 'check_up_results.id', '=', 'list_of_payment_services.check_up_result_id')
        ->join('detail_service_patients', 'list_of_payment_services.detail_service_patient_id', '=', 'detail_service_patients.id')
        ->join('price_services', 'detail_service_patients.price_service_id', '=', 'price_services.id')
        ->join('users', 'check_up_results.user_id', '=', 'users.id')
        ->join('branches', 'users.branch_id', '=', 'branches.id')
        ->select(
          DB::raw("TRIM(SUM(detail_service_patients.price_overall))+0 as price_overall")
        );

      if ($request->branch_id) {
        $price_overall_service = $price_overall_service->where('branches.id', '=', $request->branch_id);
      }

      if ($request->periode == 2) {
        $price_overall_service = $price_overall_service->where(DB::raw("YEAR(list_of_payment_services.updated_at)"), $val['year']);
      } else {
        $price_overall_service = $price_overall_service->where(DB::raw("MONTH(list_of_payment_services.updated_at)"), $val['month'])
          ->where(DB::raw("YEAR(list_of_payment_services.updated_at)"), $val['year']);
      }
      $price_overall_service = $price_overall_service->first();

      $price_overall_shop_clinic = DB::table('payment_petshop_with_clinics as ppwc')
        ->join('price_item_pet_shops as pip', 'ppwc.price_item_pet_shop_id', 'pip.id')
        ->join('users', 'ppwc.user_id', '=', 'users.id')
        ->join('branches', 'users.branch_id', '=', 'branches.id')
        ->select(DB::raw("TRIM(SUM(pip.selling_price * ppwc.total_item))+0 as price_overall"));

      if ($request->branch_id) {
        $price_overall_shop_clinic = $price_overall_shop_clinic->where('branches.id', '=', $request->branch_id);
      }

      if ($request->periode == 2) {

        $price_overall_shop_clinic = $price_overall_shop_clinic->where(DB::raw('YEAR(ppwc.created_at)'), '=', $val['year']);
      } else {
        $price_overall_shop_clinic = $price_overall_shop_clinic->where(DB::raw('MONTH(ppwc.created_at)'), '=', $val['month'])
          ->where(DB::raw('YEAR(ppwc.created_at)'), '=', $val['year']);
      }
      $price_overall_shop_clinic = $price_overall_shop_clinic->first();

      //=============================
      $price_overall_shop = DB::table('payment_petshops as pp')
        ->join('price_item_pet_shops as pip', 'pp.price_item_pet_shop_id', 'pip.id')
        ->join('users', 'pp.user_id', '=', 'users.id')
        ->join('branches', 'users.branch_id', '=', 'branches.id')
        ->select(DB::raw("TRIM(SUM(pip.selling_price * pp.total_item))+0 as price_overall"));

      if ($request->branch_id) {
        $price_overall_shop = $price_overall_shop->where('branches.id', '=', $request->branch_id);
      }

      if ($request->periode == 2) {
        $price_overall_shop = $price_overall_shop->where(DB::raw('YEAR(pp.created_at)'), '=', $val['year']);
      } else {
        $price_overall_shop = $price_overall_shop->where(DB::raw('MONTH(pp.created_at)'), '=', $val['month'])
          ->where(DB::raw('YEAR(pp.created_at)'), '=', $val['year']);
      }
      $price_overall_shop = $price_overall_shop->first();

      $price_overall = $price_overall_service->price_overall + $price_overall_item->price_overall
        + $price_overall_shop_clinic->price_overall + $price_overall_shop->price_overall;


      //discount
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

      if ($request->branch_id) {
        $amount_discount_item = $amount_discount_item->where('branches.id', '=', $request->branch_id);
      }

      if ($request->periode == 2) {
        $amount_discount_item = $amount_discount_item->where(DB::raw("YEAR(lopm.updated_at)"), $val['year']);
      } else {
        $amount_discount_item = $amount_discount_item->where(DB::raw("MONTH(lopm.updated_at)"), $val['month'])
          ->where(DB::raw("YEAR(lopm.updated_at)"), $val['year']);
      }
      $amount_discount_item = $amount_discount_item->first();

      $amount_discount_service = DB::table('list_of_payments')
        ->join('check_up_results', 'list_of_payments.check_up_result_id', '=', 'check_up_results.id')
        ->join('list_of_payment_services', 'check_up_results.id', '=', 'list_of_payment_services.check_up_result_id')
        ->join('detail_service_patients', 'list_of_payment_services.detail_service_patient_id', '=', 'detail_service_patients.id')
        ->join('price_services', 'detail_service_patients.price_service_id', '=', 'price_services.id')
        ->join('users', 'check_up_results.user_id', '=', 'users.id')
        ->join('branches', 'users.branch_id', '=', 'branches.id')
        ->select(
          DB::raw("TRIM(SUM(list_of_payment_services.amount_discount))+0 as amount_discount")
        );

      if ($request->branch_id) {
        $amount_discount_service = $amount_discount_service->where('branches.id', '=', $request->branch_id);
      }

      if ($request->periode == 2) {
        $amount_discount_service = $amount_discount_service->where(DB::raw("YEAR(list_of_payment_services.updated_at)"), $val['year']);
      } else {
        $amount_discount_service = $amount_discount_service->where(DB::raw("MONTH(list_of_payment_services.updated_at)"), $val['month'])
          ->where(DB::raw("YEAR(list_of_payment_services.updated_at)"), $val['year']);
      }

      $amount_discount_service = $amount_discount_service->first();

      $amount_discount = $amount_discount_item->amount_discount + $amount_discount_service->amount_discount;

      // //expenses
      $expenses = DB::table('expenses as e')
        ->join('users as u', 'e.user_id_spender', '=', 'u.id')
        ->join('branches as b', 'u.branch_id', '=', 'b.id')
        ->select(DB::raw("TRIM(SUM(IFNULL(e.amount_overall,0)))+0 as amount_overall"));

      if ($request->branch_id) {
        $expenses = $expenses->where('b.id', '=', $request->branch_id);
      }

      if ($request->periode == 2) {
        $expenses = $expenses->where(DB::raw("YEAR(e.date_spend)"), $val['year']);
      } else {
        $expenses = $expenses->where(DB::raw("MONTH(e.date_spend)"), $val['month'])
          ->where(DB::raw("YEAR(e.date_spend)"), $val['year']);
      }

      $expenses = $expenses->first();

      $total_expenses = 0;

      if (!is_null($expenses->amount_overall)) {

        $total_expenses = $expenses->amount_overall;
      }


      //sallary
      $payrolls = DB::table('payrolls as py')
        ->join('users', 'py.user_employee_id', '=', 'users.id')
        ->join('branches', 'users.branch_id', '=', 'branches.id')
        ->where('py.isDeleted', '=', 0);

      if ($request->periode == 2) {

        $payrolls = $payrolls->whereYear('py.date_payed', $val['year']);
      } else {

        $payrolls = $payrolls->whereYear('py.date_payed', $val['year'])->whereMonth('py.date_payed', $val['month']);
      }

      // if ($request->monthFrom && $request->monthTo && $request->yearFrom && $request->yearTo) {
      //   $payrolls = $payrolls
      //     ->whereBetween(DB::raw('MONTH(py.created_at)'), [$startMonth, $endMonth]);
      // } else {

      //   $payrolls = $payrolls
      //     ->whereYear('py.date_payed', $val['year'])
      //     ->whereMonth('py.date_payed', $val['month']);
      // }

      if ($request->branch_id) {
        $payrolls = $payrolls->where('branches.id', '=', $request->branch_id);
      }

      $firstPayroll = $payrolls->sum('total_overall');

      $firstPayroll = (float) $firstPayroll;

      $netto = $price_overall - $amount_discount - $total_expenses - $firstPayroll;

      $datas->push([
        'periode' => $listDates[$i]['dates'],
        'netto' => $netto
      ]);

      $i++;
    }

    return response()->json($datas, 200);
  }

  public function export(Request $request)
  {
    if ($request->user()->role == 'dokter' || $request->user()->role == 'resepsionis') {
      return response()->json([
        'message' => 'The user role was invalid.',
        'errors' => ['Akses User tidak diizinkan!'],
      ], 403);
    }

    $price_overall_item = DB::table('list_of_payments as lop')
      ->join('list_of_payment_medicine_groups as lopm', 'lop.id', '=', 'lopm.list_of_payment_id')
      ->join('price_medicine_groups as pmg', 'lopm.medicine_group_id', '=', 'pmg.id')
      ->join('users', 'lop.user_id', '=', 'users.id')
      ->join('branches', 'users.branch_id', '=', 'branches.id')
      ->select(
        DB::raw("(CASE WHEN lopm.quantity = 0 THEN TRIM(SUM(pmg.selling_price)) ELSE TRIM(SUM(pmg.selling_price * lopm.quantity)) END)+0 as price_overall")
      );

    $price_overall_item = $price_overall_item->where('branches.id', '=', $request->branch_id);

    $price_overall_item = $price_overall_item->where(DB::raw("MONTH(lopm.updated_at)"), $request->month)
      ->where(DB::raw("YEAR(lopm.updated_at)"), $request->year);

    $price_overall_item = $price_overall_item->first();

    $price_overall_service = DB::table('list_of_payments')
      ->join('check_up_results', 'list_of_payments.check_up_result_id', '=', 'check_up_results.id')
      ->join('list_of_payment_services', 'check_up_results.id', '=', 'list_of_payment_services.check_up_result_id')
      ->join('detail_service_patients', 'list_of_payment_services.detail_service_patient_id', '=', 'detail_service_patients.id')
      ->join('price_services', 'detail_service_patients.price_service_id', '=', 'price_services.id')
      ->join('users', 'check_up_results.user_id', '=', 'users.id')
      ->join('branches', 'users.branch_id', '=', 'branches.id')
      ->select(
        DB::raw("TRIM(SUM(detail_service_patients.price_overall))+0 as price_overall")
      );

    $price_overall_service = $price_overall_service->where('branches.id', '=', $request->branch_id);

    $price_overall_service = $price_overall_service->where(DB::raw("MONTH(list_of_payment_services.updated_at)"), $request->month)
      ->where(DB::raw("YEAR(list_of_payment_services.updated_at)"), $request->year);

    $price_overall_service = $price_overall_service->first();

    $price_overall_shop_clinic = DB::table('payment_petshop_with_clinics as ppwc')
      ->join('price_item_pet_shops as pip', 'ppwc.price_item_pet_shop_id', 'pip.id')
      ->join('users', 'ppwc.user_id', '=', 'users.id')
      ->join('branches', 'users.branch_id', '=', 'branches.id')
      ->select(DB::raw("TRIM(SUM(pip.selling_price * ppwc.total_item))+0 as price_overall"));

    $price_overall_shop_clinic = $price_overall_shop_clinic->where('branches.id', '=', $request->branch_id);

    $price_overall_shop_clinic = $price_overall_shop_clinic->where(DB::raw('MONTH(ppwc.created_at)'), '=', $request->month)
      ->where(DB::raw('YEAR(ppwc.created_at)'), '=', $request->year);

    $price_overall_shop_clinic = $price_overall_shop_clinic->first();

    //=============================
    $price_overall_shop = DB::table('payment_petshops as pp')
      ->join('price_item_pet_shops as pip', 'pp.price_item_pet_shop_id', 'pip.id')
      ->join('users', 'pp.user_id', '=', 'users.id')
      ->join('branches', 'users.branch_id', '=', 'branches.id')
      ->select(DB::raw("TRIM(SUM(pip.selling_price * pp.total_item))+0 as price_overall"));

    $price_overall_shop = $price_overall_shop->where('branches.id', '=', $request->branch_id);

    $price_overall_shop = $price_overall_shop->where(DB::raw('MONTH(pp.created_at)'), '=', $request->month)
      ->where(DB::raw('YEAR(pp.created_at)'), '=', $request->year);

    $price_overall_shop = $price_overall_shop->first();

    $price_overall = $price_overall_service->price_overall + $price_overall_item->price_overall
      + $price_overall_shop_clinic->price_overall + $price_overall_shop->price_overall;

    //gaji
    $dataSallary = DB::table('payrolls as py')
      ->join('users', 'py.user_employee_id', '=', 'users.id')
      ->join('branches', 'users.branch_id', '=', 'branches.id')
      ->select(
        'users.fullname as fullname',
        DB::raw("TRIM(py.total_overall)+0 as total_overall"),
      )
      ->where('py.isDeleted', '=', 0)
      ->whereYear('py.date_payed', $request->year)->whereMonth('py.date_payed', $request->month)
      ->where('branches.id', '=', $request->branch_id)
      ->orderBy('users.id', 'asc')->get();

    //return $dataSallary;

    //discount
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

    $amount_discount_item = $amount_discount_item->where('branches.id', '=', $request->branch_id);

    $amount_discount_item = $amount_discount_item->where(DB::raw("MONTH(lopm.updated_at)"), $request->month)
      ->where(DB::raw("YEAR(lopm.updated_at)"), $request->year);

    $amount_discount_item = $amount_discount_item->first();

    $amount_discount_service = DB::table('list_of_payments')
      ->join('check_up_results', 'list_of_payments.check_up_result_id', '=', 'check_up_results.id')
      ->join('list_of_payment_services', 'check_up_results.id', '=', 'list_of_payment_services.check_up_result_id')
      ->join('detail_service_patients', 'list_of_payment_services.detail_service_patient_id', '=', 'detail_service_patients.id')
      ->join('price_services', 'detail_service_patients.price_service_id', '=', 'price_services.id')
      ->join('users', 'check_up_results.user_id', '=', 'users.id')
      ->join('branches', 'users.branch_id', '=', 'branches.id')
      ->select(
        DB::raw("TRIM(SUM(list_of_payment_services.amount_discount))+0 as amount_discount")
      );


    $amount_discount_service = $amount_discount_service->where('branches.id', '=', $request->branch_id);


    $amount_discount_service = $amount_discount_service->where(DB::raw("MONTH(list_of_payment_services.updated_at)"), $request->month)
      ->where(DB::raw("YEAR(list_of_payment_services.updated_at)"), $request->year);


    $amount_discount_service = $amount_discount_service->first();

    $amount_discount = $amount_discount_item->amount_discount + $amount_discount_service->amount_discount;

    // //expenses
    $expenses = DB::table('expenses as e')
      ->join('users as u', 'e.user_id_spender', '=', 'u.id')
      ->join('branches as b', 'u.branch_id', '=', 'b.id')
      ->select(DB::raw("TRIM(SUM(IFNULL(e.amount_overall,0)))+0 as amount_overall"));

    $expenses = $expenses->where('b.id', '=', $request->branch_id);

    $expenses = $expenses->where(DB::raw("MONTH(e.date_spend)"), $request->month)
      ->where(DB::raw("YEAR(e.date_spend)"), $request->year);

    $expenses = $expenses->first();

    $total_expenses = 0;

    if (!is_null($expenses->amount_overall)) {

      $total_expenses = $expenses->amount_overall;
    }

    $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Rekap.xlsx');

    $sheet = $spreadsheet->getSheet(0);

    $sheet->setCellValue("B3", $price_overall);
    $sheet->setCellValue("B4", $amount_discount);

    $sheet->setCellValue("B6", $price_overall - $amount_discount);
    $sheet->setCellValue("B8", $total_expenses);

    $row = 11;
    $temp_sallary = 0;
    foreach ($dataSallary as $item) {

      $sheet->setCellValue("A{$row}", $item->fullname);
      $sheet->setCellValue("B{$row}", $item->total_overall);
      $temp_sallary += $item->total_overall;
      $row++;
    }

    $row++;

    $sheet->getStyle("A{$row}")->getFont()->setBold(true);
    $sheet->setCellValue("A{$row}", "TOTAL GAJI");
    $sheet->setCellValue("B{$row}", $temp_sallary);

    $row += 2;

    $sheet->getStyle("A{$row}")->getFont()->setBold(true);
    $sheet->setCellValue("A{$row}", "PROFIT SESUAI SISTEM");
    $sheet->setCellValue("B{$row}", $temp_sallary);

    $row++;

    $netto = $price_overall - $amount_discount - $total_expenses - $temp_sallary;

    $sheet->getStyle("A{$row}")->getFont()->setBold(true);
    $sheet->setCellValue("A{$row}", "REAL PROFIT");
    $sheet->setCellValue("B{$row}", $netto);

    $sheet->getStyle("A1:B{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

    $sallaryUser = DB::table('payrolls as py')
      ->join('users as u', 'py.user_employee_id', '=', 'u.id')
      ->join('branches', 'u.branch_id', '=', 'branches.id')
      ->select(
        'u.fullname as fullname',
        DB::raw("TRIM(py.total_overall)+0 as total_overall"),
        DB::raw("TRIM(py.basic_sallary)+0 as basic_sallary"),
        DB::raw("TRIM(py.accomodation)+0 as accomodation"),
        DB::raw("TRIM(py.total_turnover)+0 as total_turnover"), //bonus omset
        DB::raw("TRIM(py.total_inpatient)+0 as total_inpatient"),
        DB::raw("TRIM(py.total_surgery)+0 as total_surgery"),
        DB::raw("TRIM(py.total_grooming)+0 as total_grooming"),
      )
      ->where('py.isDeleted', '=', 0)
      ->where('branches.id', '=', $request->branch_id)
      ->where(DB::raw("YEAR(py.date_payed)"), $request->year)
      ->where(DB::raw("MONTH(py.date_payed)"), $request->month)
      ->orderBy('u.id', 'asc')
      ->get();


    $col = 0;

    $letter = "";

    foreach ($sallaryUser as $value) {

      $row = 5;

      $letter = chr(70 + $col);

      $sheet->getColumnDimension("{$letter}")->setWidth(13);

      $sheet->setCellValue("{$letter}{$row}", $value->fullname);
      $sheet->getStyle("{$letter}{$row}")->getFont()->setBold(true);
      $row++;

      $sheet->setCellValue("{$letter}{$row}", $value->basic_sallary);
      //$sheet->getStyle("{$letter}{$row}")->getNumberFormat()->setFormatCode('#.##0');
      $row++;

      $sheet->setCellValue("{$letter}{$row}", $value->accomodation);
      // $sheet->getStyle("{$letter}{$row}")->getNumberFormat()->setFormatCode('#.##0');
      $row++;

      $sheet->setCellValue("{$letter}{$row}", $value->total_turnover);
      // $sheet->getStyle("{$letter}{$row}")->getNumberFormat()->setFormatCode('#.##0');
      $row++;

      $sheet->setCellValue("{$letter}{$row}", $value->total_inpatient);
      // $sheet->getStyle("{$letter}{$row}")->getNumberFormat()->setFormatCode('#.##0');
      $row++;

      $sheet->setCellValue("{$letter}{$row}", $value->total_surgery);
      // $sheet->getStyle("{$letter}{$row}")->getNumberFormat()->setFormatCode('#.##0');
      $row++;

      $sheet->setCellValue("{$letter}{$row}", $value->total_grooming);
      // $sheet->getStyle("{$letter}{$row}")->getNumberFormat()->setFormatCode('#.##0');
      $row += 2;

      $sheet->setCellValue("{$letter}{$row}", $value->total_overall);
      // $sheet->getStyle("{$letter}{$row}")->getNumberFormat()->setFormatCode('#.##0');
      $sheet->getStyle("{$letter}{$row}")->getFont()->setBold(true);
      $col++;
    }

    $sheet->getStyle("F6:{$letter}13")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

    Carbon::setLocale('id');

    $monthNumber = $request->month;
    $monthName = Carbon::createFromFormat('m', $monthNumber)->locale('id')->isoFormat('MMMM');

    $branches = Branch::find($request->branch_id);

    $filename = 'Rekapitulasi Stella Vet Cabang ' . $branches->branch_name . ' ' . $monthName . ' ' . $request->year;

    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    $newFilePath = public_path() . '/result_download/' . $filename; // Set the desired path
    $writer->save($newFilePath);

    return response()->stream(function () use ($writer) {
      $writer->save('php://output');
    }, 200, [
      'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      'Content-Disposition' => 'attachment; filename="' . $filename . '"',
    ]);
  }
}
