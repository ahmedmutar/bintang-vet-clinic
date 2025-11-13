<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RekapKeseluruhanController extends Controller
{
  public function index(Request $request)
  {
    $lastPeriods = collect();
    $listDates = [];

    if ($request->date_from && $request->date_to) {

      $date_from = $request->date_from;
      $date_to = $request->date_to;

      $listDates = new Collection();
      $currentDate = Carbon::parse($date_from);

      while ($currentDate->lte(Carbon::parse($date_to))) {
        $listDates->push([
          'days' => $currentDate->translatedFormat('l, j M Y')
        ]);
        $currentDate->addDay();
      }


      $lastPeriods = new Collection();
      $currentDate = Carbon::parse($date_from);

      while ($currentDate->lte(Carbon::parse($date_to))) {
        $lastPeriods->push([
          'days' => $currentDate->format('Y-m-d')
        ]);
        $currentDate->addDay();
      }
    } else {
      $days = Carbon::now()->addDay(-7);

      for ($i = 0; $i < 7; $i++) {

        $days = $days->addDay();

        $lastPeriods->push([
          'days' => $days->format('Y-m-d'),  // Month number (e.g., 1 for January)
        ]);
      }

      Carbon::setLocale('id');

      $startDate = Carbon::now()->addDay(-7);

      for ($i = 0; $i < 7; $i++) {
        // Get the month and year for the current iteration (starting from now)
        $day = $startDate->addDay();

        $listDates[] = [
          'days' => $day->translatedFormat('l, j M Y'),  // Month name in Indonesian (e.g., Januari, Februari)
        ];
      }
    }

    $datas = collect();

    $i = 0;

    if ($request->branch_id) {

      $branches = DB::connection($request->connection)->table('branches')
        ->select('id', 'branch_name', DB::raw("'$request->connection' as connection"))
        ->where('isDeleted', '=', 0)
        ->where('id', '=', $request->branch_id)
        ->get()
        ->map(function ($item) {
          $item->branch_slug = Str::slug($item->branch_name, '_');
          return $item;
        });

      foreach ($lastPeriods as $val) {

        $totalOverall = 0;

        foreach ($branches as $valBrc) {

          $price_overall_item = DB::connection($valBrc->connection)->table('list_of_payments as lop')
            ->join('list_of_payment_medicine_groups as lopm', 'lop.id', '=', 'lopm.list_of_payment_id')
            ->join('price_medicine_groups as pmg', 'lopm.medicine_group_id', '=', 'pmg.id')
            ->join('users', 'lop.user_id', '=', 'users.id')
            ->join('branches', 'users.branch_id', '=', 'branches.id')
            ->select(
              DB::raw("(CASE WHEN lopm.quantity = 0 THEN TRIM(SUM(pmg.selling_price)) ELSE TRIM(SUM(pmg.selling_price * lopm.quantity)) END)+0 as price_overall")
            );


          $price_overall_item = $price_overall_item->where('branches.id', '=', $valBrc->id);


          $price_overall_item = $price_overall_item->whereDate('lopm.updated_at', $val['days'])->first();


          $price_overall_service = DB::connection($valBrc->connection)->table('list_of_payments')
            ->join('check_up_results', 'list_of_payments.check_up_result_id', '=', 'check_up_results.id')
            ->join('list_of_payment_services', 'check_up_results.id', '=', 'list_of_payment_services.check_up_result_id')
            ->join('detail_service_patients', 'list_of_payment_services.detail_service_patient_id', '=', 'detail_service_patients.id')
            ->join('price_services', 'detail_service_patients.price_service_id', '=', 'price_services.id')
            ->join('users', 'check_up_results.user_id', '=', 'users.id')
            ->join('branches', 'users.branch_id', '=', 'branches.id')
            ->select(
              DB::raw("TRIM(SUM(detail_service_patients.price_overall))+0 as price_overall")
            );


          $price_overall_service = $price_overall_service->where('branches.id', '=', $valBrc->id);


          $price_overall_service = $price_overall_service->whereDate('list_of_payment_services.updated_at', $val['days'])->first();
          //

          $price_overall_shop_clinic = DB::connection($valBrc->connection)->table('payment_petshop_with_clinics as ppwc')
            ->join('price_item_pet_shops as pip', 'ppwc.price_item_pet_shop_id', 'pip.id')
            ->join('users', 'ppwc.user_id', '=', 'users.id')
            ->join('branches', 'users.branch_id', '=', 'branches.id')
            ->select(DB::raw("TRIM(SUM(pip.selling_price * ppwc.total_item))+0 as price_overall"));

          $price_overall_shop_clinic = $price_overall_shop_clinic->where('branches.id', '=', $valBrc->id);


          $price_overall_shop_clinic = $price_overall_shop_clinic->whereDate('ppwc.created_at', $val['days'])->first();
          //=============================

          $price_overall_shop = DB::connection($valBrc->connection)->table('payment_petshops as pp')
            ->join('price_item_pet_shops as pip', 'pp.price_item_pet_shop_id', 'pip.id')
            ->join('users', 'pp.user_id', '=', 'users.id')
            ->join('branches', 'users.branch_id', '=', 'branches.id')
            ->select(DB::raw("TRIM(SUM(pip.selling_price * pp.total_item))+0 as price_overall"));

          $price_overall_shop = $price_overall_shop->where('branches.id', '=', $valBrc->id);


          $price_overall_shop = $price_overall_shop->whereDate('pp.created_at', $val['days'])->first();

          $price_overall = $price_overall_service->price_overall + $price_overall_item->price_overall
            + $price_overall_shop_clinic->price_overall + $price_overall_shop->price_overall;


          //discount
          $amount_discount_item = DB::connection($valBrc->connection)->table('list_of_payments as lop')
            ->join('check_up_results as cur', 'lop.check_up_result_id', '=', 'cur.id')
            ->join('list_of_payment_medicine_groups as lopm', 'lopm.list_of_payment_id', '=', 'lop.id')
            ->join('price_medicine_groups as pmg', 'lopm.medicine_group_id', '=', 'pmg.id')
            ->join('registrations as reg', 'cur.patient_registration_id', '=', 'reg.id')
            ->join('patients as pa', 'reg.patient_id', '=', 'pa.id')
            ->join('users', 'lop.user_id', '=', 'users.id')
            ->join('branches', 'users.branch_id', '=', 'branches.id')
            ->select(
              DB::raw("TRIM(SUM(lopm.amount_discount))+0 as amount_discount")
            )
            ->where('branches.id', '=', $valBrc->id)
            ->whereDate('lopm.updated_at', $val['days'])->first();
          // if ($request->periode == 2) {
          //   $amount_discount_item = $amount_discount_item->where(DB::raw("YEAR(lopm.updated_at)"), $val['year']);
          // } else {
          //   $amount_discount_item = $amount_discount_item->where(DB::raw("MONTH(lopm.updated_at)"), $val['month'])
          //     ->where(DB::raw("YEAR(lopm.updated_at)"), $val['year']);
          // }

          $amount_discount_service = DB::connection($valBrc->connection)->table('list_of_payments')
            ->join('check_up_results', 'list_of_payments.check_up_result_id', '=', 'check_up_results.id')
            ->join('list_of_payment_services', 'check_up_results.id', '=', 'list_of_payment_services.check_up_result_id')
            ->join('detail_service_patients', 'list_of_payment_services.detail_service_patient_id', '=', 'detail_service_patients.id')
            ->join('price_services', 'detail_service_patients.price_service_id', '=', 'price_services.id')
            ->join('users', 'check_up_results.user_id', '=', 'users.id')
            ->join('branches', 'users.branch_id', '=', 'branches.id')
            ->select(
              DB::raw("TRIM(SUM(list_of_payment_services.amount_discount))+0 as amount_discount")
            )
            ->where('branches.id', '=', $valBrc->id)
            ->whereDate('list_of_payment_services.updated_at', $val['days'])->first();

          $amount_discount = $amount_discount_item->amount_discount + $amount_discount_service->amount_discount;

          $total_clean = $price_overall - $amount_discount;

          $totalOverall += $total_clean;
        }


        $datas->push([
          'dates' => $listDates[$i]['days'],
          'total_omset' => $totalOverall,
        ]);

        $i++;
      }
    } else {

      $admin = DB::connection('mysql')->table('branches')
        ->select('id', 'branch_name', DB::raw("'mysql' as connection"))
        ->where('id', '!=', 4)
        ->where('isDeleted', '=', 0)->get();

      $tj = DB::connection('mysql_second')->table('branches')
        ->select('id', 'branch_name',  DB::raw("'mysql_second' as connection"))
        ->where('id', '!=', 1)
        ->where('isDeleted', '=', 0)->get();

      $hello = DB::connection('mysql_third')->table('branches')
        ->select('id', 'branch_name',  DB::raw("'mysql_third' as connection"))
        ->where('isDeleted', '=', 0)->get();

      $helloKahfi = DB::connection('mysql_forth')->table('branches')
        ->select('id', 'branch_name', DB::raw("'mysql_forth' as connection"))
        ->where('isDeleted', '=', 0)->get();

      $stella = DB::connection('mysql_fifth')->table('branches')
        ->select('id', 'branch_name',  DB::raw("'mysql_fifth' as connection"))
        ->where('isDeleted', '=', 0)->get();

      $branches = $admin->merge($tj)->merge($hello)->merge($helloKahfi)->merge($stella)
        ->map(function ($item) {
          // tambahkan kolom baru tanpa mengubah branch_name asli
          $item->branch_slug = Str::slug($item->branch_name, '_');
          return $item;
        })
        ->values();

      $i = 0;
      foreach ($lastPeriods as $val) {

        $datas->push(
          [
            'dates' => $listDates[$i]['days'],
          ]
        );

        $updatedCollection = $datas->map(function ($item, $key) use ($branches, $lastPeriods) {

          $date_specified = $lastPeriods[$key]['days'];
          $newItem = $item;
          $total_omset = 0;
          $total_clean = 0;

          foreach ($branches as $valBrc) {

            $price_overall_item = DB::connection($valBrc->connection)->table('list_of_payments as lop')
              ->join('list_of_payment_medicine_groups as lopm', 'lop.id', '=', 'lopm.list_of_payment_id')
              ->join('price_medicine_groups as pmg', 'lopm.medicine_group_id', '=', 'pmg.id')
              ->join('users', 'lop.user_id', '=', 'users.id')
              ->join('branches', 'users.branch_id', '=', 'branches.id')
              ->select(
                DB::raw("(CASE WHEN lopm.quantity = 0 THEN TRIM(SUM(pmg.selling_price)) ELSE TRIM(SUM(pmg.selling_price * lopm.quantity)) END)+0 as price_overall")
              )
              ->where('branches.id', '=', $valBrc->id)
              ->whereDate('lopm.updated_at', $date_specified)->first();

            $price_overall_service = DB::connection($valBrc->connection)->table('list_of_payments')
              ->join('check_up_results', 'list_of_payments.check_up_result_id', '=', 'check_up_results.id')
              ->join('list_of_payment_services', 'check_up_results.id', '=', 'list_of_payment_services.check_up_result_id')
              ->join('detail_service_patients', 'list_of_payment_services.detail_service_patient_id', '=', 'detail_service_patients.id')
              ->join('price_services', 'detail_service_patients.price_service_id', '=', 'price_services.id')
              ->join('users', 'check_up_results.user_id', '=', 'users.id')
              ->join('branches', 'users.branch_id', '=', 'branches.id')
              ->select(
                DB::raw("TRIM(SUM(detail_service_patients.price_overall))+0 as price_overall")
              )
              ->where('branches.id', '=', $valBrc->id)
              ->whereDate('list_of_payment_services.updated_at', $date_specified)->first();

            $price_overall_shop_clinic = DB::connection($valBrc->connection)->table('payment_petshop_with_clinics as ppwc')
              ->join('price_item_pet_shops as pip', 'ppwc.price_item_pet_shop_id', 'pip.id')
              ->join('users', 'ppwc.user_id', '=', 'users.id')
              ->join('branches', 'users.branch_id', '=', 'branches.id')
              ->select(DB::raw("TRIM(SUM(pip.selling_price * ppwc.total_item))+0 as price_overall"))
              ->where('branches.id', '=', $valBrc->id)
              ->whereDate('ppwc.created_at', $date_specified)->first();
            //=============================

            $price_overall_shop = DB::connection($valBrc->connection)->table('payment_petshops as pp')
              ->join('price_item_pet_shops as pip', 'pp.price_item_pet_shop_id', 'pip.id')
              ->join('users', 'pp.user_id', '=', 'users.id')
              ->join('branches', 'users.branch_id', '=', 'branches.id')
              ->select(DB::raw("TRIM(SUM(pip.selling_price * pp.total_item))+0 as price_overall"))
              ->where('branches.id', '=', $valBrc->id)
              ->whereDate('pp.created_at', $date_specified)->first();

            $total = ($price_overall_service->price_overall ?? 0) +
              ($price_overall_item->price_overall ?? 0) +
              ($price_overall_shop_clinic->price_overall ?? 0) +
              ($price_overall_shop->price_overall ?? 0);

            //discount
            $amount_discount_item = DB::connection($valBrc->connection)->table('list_of_payments as lop')
              ->join('check_up_results as cur', 'lop.check_up_result_id', '=', 'cur.id')
              ->join('list_of_payment_medicine_groups as lopm', 'lopm.list_of_payment_id', '=', 'lop.id')
              ->join('price_medicine_groups as pmg', 'lopm.medicine_group_id', '=', 'pmg.id')
              ->join('registrations as reg', 'cur.patient_registration_id', '=', 'reg.id')
              ->join('patients as pa', 'reg.patient_id', '=', 'pa.id')
              ->join('users', 'lop.user_id', '=', 'users.id')
              ->join('branches', 'users.branch_id', '=', 'branches.id')
              ->select(
                DB::raw("TRIM(SUM(lopm.amount_discount))+0 as amount_discount")
              )
              ->where('branches.id', '=', $valBrc->id)
              ->whereDate('lopm.updated_at', $date_specified)->first();


            $amount_discount_service = DB::connection($valBrc->connection)->table('list_of_payments')
              ->join('check_up_results', 'list_of_payments.check_up_result_id', '=', 'check_up_results.id')
              ->join('list_of_payment_services', 'check_up_results.id', '=', 'list_of_payment_services.check_up_result_id')
              ->join('detail_service_patients', 'list_of_payment_services.detail_service_patient_id', '=', 'detail_service_patients.id')
              ->join('price_services', 'detail_service_patients.price_service_id', '=', 'price_services.id')
              ->join('users', 'check_up_results.user_id', '=', 'users.id')
              ->join('branches', 'users.branch_id', '=', 'branches.id')
              ->select(
                DB::raw("TRIM(SUM(list_of_payment_services.amount_discount))+0 as amount_discount")
              )
              ->where('branches.id', '=', $valBrc->id)
              ->whereDate('list_of_payment_services.updated_at', $date_specified)->first();

            $amount_discount = $amount_discount_item->amount_discount + $amount_discount_service->amount_discount;

            $total_clean = $total - $amount_discount;

            $total_omset += $total_clean;

            $newItem[Str::slug($valBrc->branch_name, '_')] = $total_clean;
          }

          $newItem['total_omset'] = $total_omset;
          //info($item);
          return $newItem;
          // Return the modified item
        });

        $i++;
      }
      // return 'test';
      //return $updatedCollection;
      return response()->json([
        'datas' => $updatedCollection->toArray(),
        'branches' => $branches
      ], 200);
    }
  }

  public function chart(Request $request)
  {
    $lastPeriods = collect();
    $listDates = [];

    if ($request->date_from && $request->date_to) {

      $date_from = $request->date_from;
      $date_to = $request->date_to;

      $listDates = new Collection();
      $currentDate = Carbon::parse($date_from);

      while ($currentDate->lte(Carbon::parse($date_to))) {
        $listDates->push([
          'days' => $currentDate->translatedFormat('l, j M Y')
        ]);
        $currentDate->addDay();
      }


      $lastPeriods = new Collection();
      $currentDate = Carbon::parse($date_from);

      while ($currentDate->lte(Carbon::parse($date_to))) {
        $lastPeriods->push([
          'days' => $currentDate->format('Y-m-d')
        ]);
        $currentDate->addDay();
      }
    } else {
      $days = Carbon::now()->addDay(-7);

      for ($i = 0; $i < 7; $i++) {

        $days = $days->addDay();

        $lastPeriods->push([
          'days' => $days->format('Y-m-d'),  // Month number (e.g., 1 for January)
        ]);
      }

      Carbon::setLocale('id');

      $startDate = Carbon::now()->addDay(-7);

      for ($i = 0; $i < 7; $i++) {
        // Get the month and year for the current iteration (starting from now)
        $day = $startDate->addDay();

        $listDates[] = [
          'days' => $day->translatedFormat('l, j M Y'),  // Month name in Indonesian (e.g., Januari, Februari)
        ];
      }
    }

    $datas = collect();

    $i = 0;

    if ($request->branch_id) {

      $branches = DB::connection($request->connection)->table('branches')
        ->select('id', 'branch_name', DB::raw("'$request->connection' as connection"))
        ->where('isDeleted', '=', 0)
        ->where('id', '=', $request->branch_id)
        ->get();
    } else {

      $admin = DB::connection('mysql')->table('branches')
        ->select('id', 'branch_name', DB::raw("'mysql' as connection"))
        ->where('id', '!=', 4)
        ->where('isDeleted', '=', 0)->get();

      $tj = DB::connection('mysql_second')->table('branches')
        ->select('id', 'branch_name',  DB::raw("'mysql_second' as connection"))
        ->where('id', '!=', 1)
        ->where('isDeleted', '=', 0)->get();

      $hello = DB::connection('mysql_third')->table('branches')
        ->select('id', 'branch_name',  DB::raw("'mysql_third' as connection"))
        ->where('isDeleted', '=', 0)->get();

      $helloKahfi = DB::connection('mysql_forth')->table('branches')
        ->select('id', 'branch_name', DB::raw("'mysql_forth' as connection"))
        ->where('isDeleted', '=', 0)->get();

      $stella = DB::connection('mysql_fifth')->table('branches')
        ->select('id', 'branch_name',  DB::raw("'mysql_fifth' as connection"))
        ->where('isDeleted', '=', 0)->get();

      $branches = $admin->merge($tj)->merge($hello)->merge($helloKahfi)->merge($stella)->values();
    }

    //    return $branches;
    foreach ($lastPeriods as $val) {

      $totalOverall = 0;

      foreach ($branches as $valBrc) {

        $price_overall_item = DB::connection($valBrc->connection)->table('list_of_payments as lop')
          ->join('list_of_payment_medicine_groups as lopm', 'lop.id', '=', 'lopm.list_of_payment_id')
          ->join('price_medicine_groups as pmg', 'lopm.medicine_group_id', '=', 'pmg.id')
          ->join('users', 'lop.user_id', '=', 'users.id')
          ->join('branches', 'users.branch_id', '=', 'branches.id')
          ->select(
            DB::raw("(CASE WHEN lopm.quantity = 0 THEN TRIM(SUM(pmg.selling_price)) ELSE TRIM(SUM(pmg.selling_price * lopm.quantity)) END)+0 as price_overall")
          );

        $price_overall_item = $price_overall_item->where('branches.id', '=', $valBrc->id);


        $price_overall_item = $price_overall_item->whereDate('lopm.updated_at', $val['days'])->first();


        $price_overall_service = DB::connection($valBrc->connection)->table('list_of_payments')
          ->join('check_up_results', 'list_of_payments.check_up_result_id', '=', 'check_up_results.id')
          ->join('list_of_payment_services', 'check_up_results.id', '=', 'list_of_payment_services.check_up_result_id')
          ->join('detail_service_patients', 'list_of_payment_services.detail_service_patient_id', '=', 'detail_service_patients.id')
          ->join('price_services', 'detail_service_patients.price_service_id', '=', 'price_services.id')
          ->join('users', 'check_up_results.user_id', '=', 'users.id')
          ->join('branches', 'users.branch_id', '=', 'branches.id')
          ->select(
            DB::raw("TRIM(SUM(detail_service_patients.price_overall))+0 as price_overall")
          );

        $price_overall_service = $price_overall_service->where('branches.id', '=', $valBrc->id);


        $price_overall_service = $price_overall_service->whereDate('list_of_payment_services.updated_at', $val['days'])->first();
        //

        $price_overall_shop_clinic = DB::connection($valBrc->connection)->table('payment_petshop_with_clinics as ppwc')
          ->join('price_item_pet_shops as pip', 'ppwc.price_item_pet_shop_id', 'pip.id')
          ->join('users', 'ppwc.user_id', '=', 'users.id')
          ->join('branches', 'users.branch_id', '=', 'branches.id')
          ->select(DB::raw("TRIM(SUM(pip.selling_price * ppwc.total_item))+0 as price_overall"));

        $price_overall_shop_clinic = $price_overall_shop_clinic->where('branches.id', '=', $valBrc->id);


        $price_overall_shop_clinic = $price_overall_shop_clinic->whereDate('ppwc.created_at', $val['days'])->first();
        //=============================

        $price_overall_shop = DB::connection($valBrc->connection)->table('payment_petshops as pp')
          ->join('price_item_pet_shops as pip', 'pp.price_item_pet_shop_id', 'pip.id')
          ->join('users', 'pp.user_id', '=', 'users.id')
          ->join('branches', 'users.branch_id', '=', 'branches.id')
          ->select(DB::raw("TRIM(SUM(pip.selling_price * pp.total_item))+0 as price_overall"));


        $price_overall_shop = $price_overall_shop->where('branches.id', '=', $valBrc->id);


        $price_overall_shop = $price_overall_shop->whereDate('pp.created_at', $val['days'])->first();

        $price_overall = $price_overall_service->price_overall + $price_overall_item->price_overall
          + $price_overall_shop_clinic->price_overall + $price_overall_shop->price_overall;

        //discount
        $amount_discount_item = DB::connection($valBrc->connection)->table('list_of_payments as lop')
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


        $amount_discount_item = $amount_discount_item->where('branches.id', '=', $valBrc->id);


        $amount_discount_item = $amount_discount_item->whereDate('lopm.updated_at', $val['days'])->first();
        // if ($request->periode == 2) {
        //   $amount_discount_item = $amount_discount_item->where(DB::raw("YEAR(lopm.updated_at)"), $val['year']);
        // } else {
        //   $amount_discount_item = $amount_discount_item->where(DB::raw("MONTH(lopm.updated_at)"), $val['month'])
        //     ->where(DB::raw("YEAR(lopm.updated_at)"), $val['year']);
        // }

        $amount_discount_service = DB::connection($valBrc->connection)->table('list_of_payments')
          ->join('check_up_results', 'list_of_payments.check_up_result_id', '=', 'check_up_results.id')
          ->join('list_of_payment_services', 'check_up_results.id', '=', 'list_of_payment_services.check_up_result_id')
          ->join('detail_service_patients', 'list_of_payment_services.detail_service_patient_id', '=', 'detail_service_patients.id')
          ->join('price_services', 'detail_service_patients.price_service_id', '=', 'price_services.id')
          ->join('users', 'check_up_results.user_id', '=', 'users.id')
          ->join('branches', 'users.branch_id', '=', 'branches.id')
          ->select(
            DB::raw("TRIM(SUM(list_of_payment_services.amount_discount))+0 as amount_discount")
          );


        $amount_discount_service = $amount_discount_service->where('branches.id', '=', $valBrc->id);


        $amount_discount_service = $amount_discount_service->whereDate('list_of_payment_services.updated_at', $val['days'])->first();
        // if ($request->periode == 2) {
        //   $amount_discount_service = $amount_discount_service->where(DB::raw("YEAR(list_of_payment_services.updated_at)"), $val['year']);
        // } else {
        //   $amount_discount_service = $amount_discount_service->where(DB::raw("MONTH(list_of_payment_services.updated_at)"), $val['month'])
        //     ->where(DB::raw("YEAR(list_of_payment_services.updated_at)"), $val['year']);
        // }

        $amount_discount = $amount_discount_item->amount_discount + $amount_discount_service->amount_discount;

        $totalOverall += $price_overall - $amount_discount;
      }


      $datas->push([
        'dates' => $listDates[$i]['days'],
        'total_omset' => $totalOverall,
      ]);

      $i++;
    }

    return response()->json($datas, 200);
  }
}
