<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Cache;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Border;

class RekapController extends Controller
{
  public function listperiode()
  {
    $data = [
      ['id' => 1, 'periode' => 'Bulanan'],
      ['id' => 2, 'periode' => 'Tahunan'],
      ['id' => 3, 'periode' => 'Sejak Awal Klinik Buka'],
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

    $is_yearly   = $request->periode == 2;
    $is_all_time = $request->periode == 3;
    $lastPeriods = collect();
    $listDates   = [];

    Carbon::setLocale('id');

    if ($is_all_time) {
      // $lastPeriods and $listDates populated after map query
    } elseif ($is_yearly) {
      if ($request->year) {
        $year = (int) $request->year;
        $lastPeriods->push(['year' => $year]);
        $listDates[] = ['dates' => $year];
      } else {
        $currentYear = Carbon::now()->year;
        for ($i = 0; $i < 5; $i++) {
          $lastPeriods->push(['year' => $currentYear - $i]);
          $listDates[] = ['dates' => $currentYear - $i];
        }
      }
    } else {
      if ($request->month_from && $request->year_from && $request->month_to && $request->year_to) {
        $cur = Carbon::createFromDate((int) $request->year_from, (int) $request->month_from, 1)->startOfMonth();
        $end = Carbon::createFromDate((int) $request->year_to,   (int) $request->month_to,   1)->startOfMonth();
        while ($cur->lte($end)) {
          $lastPeriods->push(['month' => $cur->month, 'year' => $cur->year]);
          $listDates[] = ['dates' => $cur->translatedFormat('F Y')];
          $cur->addMonths(1);
        }
      } else {
        $month = Carbon::now()->subMonths(13);
        for ($i = 0; $i < 13; $i++) {
          $month = $month->addMonths(1);
          if ($month->day !== 1) { $month->day(1); }
          $lastPeriods->push(['month' => $month->format('n'), 'year' => $month->format('Y')]);
          $listDates[] = ['dates' => $month->translatedFormat('F Y')];
        }
      }
    }

    [
      'poi_map' => $poi_map,
      'pos_map' => $pos_map,
      'posc_map' => $posc_map,
      'poshop_map' => $poshop_map,
      'adi_map' => $adi_map,
      'ads_map' => $ads_map,
      'exp_map' => $exp_map,
      'py_map' => $py_map,
    ] = $this->buildFinancialMaps($request, $is_yearly, $is_all_time);

    if ($is_all_time) {
      $allKeys = collect(array_keys($poi_map))
        ->merge(array_keys($pos_map))->merge(array_keys($posc_map))
        ->merge(array_keys($poshop_map))->merge(array_keys($exp_map))
        ->merge(array_keys($py_map))
        ->unique()
        ->filter(fn($k) => substr_count($k, '-') === 1)
        ->sort(function ($a, $b) {
          [$ay, $am] = explode('-', $a);
          [$by, $bm] = explode('-', $b);
          return $ay !== $by ? (int)$ay - (int)$by : (int)$am - (int)$bm;
        })
        ->values();

      foreach ($allKeys as $k) {
        [$yr, $mo] = explode('-', $k);
        $yr = (int) $yr; $mo = (int) $mo;
        if ($yr < 2000 || $mo < 1 || $mo > 12) continue;
        $lastPeriods->push(['month' => $mo, 'year' => $yr]);
        $listDates[] = ['dates' => Carbon::createFromDate($yr, $mo, 1)->translatedFormat('F Y')];
      }
    }

    $datas = collect();

    foreach ($lastPeriods as $i => $val) {
      $key = $is_yearly ? $val['year'] : $val['year'] . '-' . $val['month'];

      $price_overall = ($poi_map[$key] ?? 0) + ($pos_map[$key] ?? 0)
        + ($posc_map[$key] ?? 0) + ($poshop_map[$key] ?? 0);

      $amount_discount = ($adi_map[$key] ?? 0) + ($ads_map[$key] ?? 0);

      $total_expenses = $exp_map[$key] ?? 0;
      $firstPayroll = (float) ($py_map[$key] ?? 0);

      $netto = $price_overall - $amount_discount - $total_expenses - $firstPayroll;

      $datas->push([
        'dates'       => $listDates[$i]['dates'],
        'total_omset' => $price_overall,
        'discount'    => $amount_discount,
        'expenses'    => $total_expenses,
        'sallary'     => $firstPayroll,
        'netto'       => $netto,
      ]);
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

    $is_yearly   = $request->periode == 2;
    $is_all_time = $request->periode == 3;
    $lastPeriods = collect();
    $listDates   = [];

    Carbon::setLocale('id');

    if ($is_all_time) {
      // $lastPeriods and $listDates populated after map query
    } elseif ($is_yearly) {
      if ($request->year) {
        $year = (int) $request->year;
        $lastPeriods->push(['year' => $year]);
        $listDates[] = ['dates' => $year];
      } else {
        $currentYear = Carbon::now()->subYears(4)->year;
        for ($i = 0; $i < 5; $i++) {
          $lastPeriods->push(['year' => $currentYear + $i]);
          $listDates[] = ['dates' => $currentYear + $i];
        }
      }
    } else {
      if ($request->month_from && $request->year_from && $request->month_to && $request->year_to) {
        $cur = Carbon::createFromDate((int) $request->year_from, (int) $request->month_from, 1)->startOfMonth();
        $end = Carbon::createFromDate((int) $request->year_to,   (int) $request->month_to,   1)->startOfMonth();
        while ($cur->lte($end)) {
          $lastPeriods->push(['month' => $cur->month, 'year' => $cur->year]);
          $listDates[] = ['dates' => $cur->translatedFormat('M Y')];
          $cur->addMonths(1);
        }
      } else {
        $month = Carbon::now()->subMonths(13);
        for ($i = 0; $i < 13; $i++) {
          $month = $month->addMonths(1);
          if ($month->day !== 1) { $month->day(1); }
          $lastPeriods->push(['month' => $month->format('n'), 'year' => $month->format('Y')]);
          $listDates[] = ['dates' => $month->translatedFormat('M Y')];
        }
      }
    }

    [
      'poi_map' => $poi_map,
      'pos_map' => $pos_map,
      'posc_map' => $posc_map,
      'poshop_map' => $poshop_map,
      'adi_map' => $adi_map,
      'ads_map' => $ads_map,
      'exp_map' => $exp_map,
      'py_map' => $py_map,
    ] = $this->buildFinancialMaps($request, $is_yearly, $is_all_time);

    if ($is_all_time) {
      $allKeys = collect(array_keys($poi_map))
        ->merge(array_keys($pos_map))->merge(array_keys($posc_map))
        ->merge(array_keys($poshop_map))->merge(array_keys($exp_map))
        ->merge(array_keys($py_map))
        ->unique()
        ->filter(fn($k) => substr_count($k, '-') === 1)
        ->sort(function ($a, $b) {
          [$ay, $am] = explode('-', $a);
          [$by, $bm] = explode('-', $b);
          return $ay !== $by ? (int)$ay - (int)$by : (int)$am - (int)$bm;
        })
        ->values();

      foreach ($allKeys as $k) {
        [$yr, $mo] = explode('-', $k);
        $yr = (int) $yr; $mo = (int) $mo;
        if ($yr < 2000 || $mo < 1 || $mo > 12) continue;
        $lastPeriods->push(['month' => $mo, 'year' => $yr]);
        $listDates[] = ['dates' => Carbon::createFromDate($yr, $mo, 1)->translatedFormat('M Y')];
      }
    }

    $datas = collect();

    foreach ($lastPeriods as $i => $val) {
      $key = $is_yearly ? $val['year'] : $val['year'] . '-' . $val['month'];

      $price_overall = ($poi_map[$key] ?? 0) + ($pos_map[$key] ?? 0)
        + ($posc_map[$key] ?? 0) + ($poshop_map[$key] ?? 0);

      $amount_discount = ($adi_map[$key] ?? 0) + ($ads_map[$key] ?? 0);

      $total_expenses = $exp_map[$key] ?? 0;
      $firstPayroll = (float) ($py_map[$key] ?? 0);

      $netto = $price_overall - $amount_discount - $total_expenses - $firstPayroll;

      $datas->push([
        'periode'     => $listDates[$i]['dates'],
        'total_omset' => $price_overall,
        'discount'    => $amount_discount,
        'expenses'    => $total_expenses,
        'sallary'     => $firstPayroll,
        'netto'       => $netto,
      ]);
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

    Carbon::setLocale('id');

    $periode    = (int) ($request->periode ?? 1);
    $branchId   = $request->branch_id;
    $branch     = Branch::find($branchId);
    $branchName = $branch->branch_name ?? 'Cabang';

    // ── Bangun daftar bulan yang akan di-export ───────────────────────────
    $months        = [];
    $filenameRange = '';

    $filename = 'Rekapitulasi Astra Vet Cabang ' . $branches->branch_name . ' ' . $monthName . ' ' . $request->year;

    if ($periode === 2) {
      // Tahunan — semua 12 bulan dalam tahun yang dipilih
      $year = (int) ($request->year ?? Carbon::now()->year);
      for ($m = 1; $m <= 12; $m++) {
        $d = Carbon::createFromDate($year, $m, 1);
        $months[] = [
          'dateFrom' => $d->copy()->startOfMonth()->format('Y-m-d'),
          'dateTo'   => $d->copy()->endOfMonth()->format('Y-m-d'),
          'label'    => $d->isoFormat('MMM YYYY'),
        ];
      }
      $filenameRange = (string) $year;

    } elseif ($periode === 3) {
      // Sejak awal klinik buka
      $firstRow = DB::table('list_of_payments as lop')
        ->join('users', 'lop.user_id', '=', 'users.id')
        ->join('branches', 'users.branch_id', '=', 'branches.id')
        ->where('branches.id', $branchId)
        ->selectRaw('MIN(DATE(lop.created_at)) as min_date')
        ->first();
      $cur = Carbon::parse($firstRow->min_date ?? now()->subYear())->startOfMonth();
      $end = Carbon::now()->startOfMonth();
      while ($cur->lte($end)) {
        $months[] = [
          'dateFrom' => $cur->copy()->startOfMonth()->format('Y-m-d'),
          'dateTo'   => $cur->copy()->endOfMonth()->format('Y-m-d'),
          'label'    => $cur->isoFormat('MMM YYYY'),
        ];
        $cur->addMonth();
      }
      $filenameRange = ($months[0]['label'] ?? '') . ' - ' . (end($months)['label'] ?? '');

    } else {
      // Bulanan — iterasi setiap bulan dalam range yang dipilih
      $monthFrom = (int) ($request->month_from ?? $request->month ?? Carbon::now()->month);
      $yearFrom  = (int) ($request->year_from  ?? $request->year  ?? Carbon::now()->year);
      $monthTo   = (int) ($request->month_to   ?? $monthFrom);
      $yearTo    = (int) ($request->year_to    ?? $yearFrom);
      $cur       = Carbon::createFromDate($yearFrom, $monthFrom, 1)->startOfMonth();
      $end       = Carbon::createFromDate($yearTo,   $monthTo,   1)->startOfMonth();
      while ($cur->lte($end)) {
        $months[] = [
          'dateFrom' => $cur->copy()->startOfMonth()->format('Y-m-d'),
          'dateTo'   => $cur->copy()->endOfMonth()->format('Y-m-d'),
          'label'    => $cur->isoFormat('MMM YYYY'),
        ];
        $cur->addMonth();
      }
      $first         = Carbon::createFromDate($yearFrom, $monthFrom, 1);
      $last          = Carbon::createFromDate($yearTo,   $monthTo,   1);
      $filenameRange = $first->isoFormat('MMM YYYY');
      if ($monthFrom !== $monthTo || $yearFrom !== $yearTo) {
        $filenameRange .= ' - ' . $last->isoFormat('MMM YYYY');
      }
    }

    // ── Load template — simpan clone asli sebelum dimodifikasi ───────────
    $templatePath  = public_path() . '/template/report/Template_Rekap.xlsx';
    $spreadsheet   = IOFactory::load($templatePath);
    $templateSheet = clone $spreadsheet->getSheet(0); // referensi bersih untuk clone berikutnya

    foreach ($months as $idx => $monthData) {
      $dateFrom = $monthData['dateFrom'];
      $dateTo   = $monthData['dateTo'];

      // ── Query data bulan ini ─────────────────────────────────────────
      $items = DB::table('list_of_payments as lop')
        ->join('list_of_payment_medicine_groups as lopm', 'lop.id', '=', 'lopm.list_of_payment_id')
        ->join('price_medicine_groups as pmg', 'lopm.medicine_group_id', '=', 'pmg.id')
        ->join('users', 'lop.user_id', '=', 'users.id')
        ->join('branches', 'users.branch_id', '=', 'branches.id')
        ->selectRaw('(CASE WHEN lopm.quantity = 0 THEN TRIM(SUM(pmg.selling_price)) ELSE TRIM(SUM(pmg.selling_price * lopm.quantity)) END)+0 as price_overall, TRIM(SUM(lopm.amount_discount))+0 as amount_discount')
        ->where('branches.id', $branchId)
        ->whereBetween(DB::raw('DATE(lopm.updated_at)'), [$dateFrom, $dateTo])
        ->first();

      $services = DB::table('list_of_payments as lop')
        ->join('check_up_results as cur', 'lop.check_up_result_id', '=', 'cur.id')
        ->join('list_of_payment_services as lops', 'cur.id', '=', 'lops.check_up_result_id')
        ->join('detail_service_patients as dsp', 'lops.detail_service_patient_id', '=', 'dsp.id')
        ->join('users', 'cur.user_id', '=', 'users.id')
        ->join('branches', 'users.branch_id', '=', 'branches.id')
        ->selectRaw('TRIM(SUM(dsp.price_overall))+0 as price_overall, TRIM(SUM(lops.amount_discount))+0 as amount_discount')
        ->where('branches.id', $branchId)
        ->whereBetween(DB::raw('DATE(lops.updated_at)'), [$dateFrom, $dateTo])
        ->first();

      $shops = DB::selectOne("
        SELECT COALESCE(SUM(t.amount), 0)+0 AS price_overall FROM (
          SELECT pip.selling_price * ppwc.total_item AS amount
          FROM payment_petshop_with_clinics ppwc
          INNER JOIN price_item_pet_shops pip ON ppwc.price_item_pet_shop_id = pip.id
          INNER JOIN users ON ppwc.user_id = users.id
          INNER JOIN branches ON users.branch_id = branches.id
          WHERE branches.id = ? AND DATE(ppwc.created_at) BETWEEN ? AND ?
          UNION ALL
          SELECT pip.selling_price * pp.total_item
          FROM payment_petshops pp
          INNER JOIN price_item_pet_shops pip ON pp.price_item_pet_shop_id = pip.id
          INNER JOIN users ON pp.user_id = users.id
          INNER JOIN branches ON users.branch_id = branches.id
          WHERE branches.id = ? AND DATE(pp.created_at) BETWEEN ? AND ?
        ) t
      ", [$branchId, $dateFrom, $dateTo, $branchId, $dateFrom, $dateTo]);

      $price_overall   = ($items->price_overall ?? 0) + ($services->price_overall ?? 0) + ($shops->price_overall ?? 0);
      $amount_discount = ($items->amount_discount ?? 0) + ($services->amount_discount ?? 0);

      $sallaryUser = DB::table('payrolls as py')
        ->join('users as u', 'py.user_employee_id', '=', 'u.id')
        ->join('branches', 'u.branch_id', '=', 'branches.id')
        ->selectRaw('u.id as user_id, u.fullname,
          TRIM(SUM(py.total_overall))+0   as total_overall,
          TRIM(SUM(py.basic_sallary))+0   as basic_sallary,
          TRIM(SUM(py.accomodation))+0    as accomodation,
          TRIM(SUM(py.eat))+0             as eat,
          TRIM(SUM(py.total_turnover))+0  as total_turnover,
          TRIM(SUM(py.total_inpatient))+0 as total_inpatient,
          TRIM(SUM(py.total_surgery))+0   as total_surgery,
          TRIM(SUM(py.total_grooming))+0  as total_grooming,
          TRIM(SUM(py.fine))+0            as fine')
        ->where('py.isDeleted', 0)
        ->where('branches.id', $branchId)
        ->whereBetween(DB::raw('DATE(py.date_payed)'), [$dateFrom, $dateTo])
        ->groupBy('u.id', 'u.fullname')
        ->orderBy('u.id')
        ->get();

      $expenses = DB::table('expenses as e')
        ->join('users as u', 'e.user_id_spender', '=', 'u.id')
        ->join('branches as b', 'u.branch_id', '=', 'b.id')
        ->selectRaw('TRIM(SUM(IFNULL(e.amount_overall,0)))+0 as amount_overall')
        ->where('b.id', $branchId)
        ->whereBetween(DB::raw('DATE(e.date_spend)'), [$dateFrom, $dateTo])
        ->first();

      $total_expenses = $expenses->amount_overall ?? 0;

      // ── Ambil / buat sheet untuk bulan ini ───────────────────────────
      if ($idx === 0) {
        $sheet = $spreadsheet->getSheet(0);
      } else {
        $sheet = clone $templateSheet; // selalu clone dari template asli
        $spreadsheet->addSheet($sheet);
      }
      $sheet->setTitle($monthData['label']);

      // ── Isi data ke sheet ─────────────────────────────────────────────
      $sheet->setCellValue('B3', $price_overall);
      $sheet->setCellValue('B4', $amount_discount);
      $sheet->setCellValue('B6', $price_overall - $amount_discount);
      $sheet->setCellValue('B9', $total_expenses);

      $row          = 12;
      $temp_sallary = 0;
      foreach ($sallaryUser as $item) {
        $sheet->setCellValue("A{$row}", $item->fullname);
        $sheet->setCellValue("B{$row}", $item->total_overall);
        $temp_sallary += $item->total_overall;
        $row++;
      }

      $row++;
      $sheet->getStyle("A{$row}")->getFont()->setBold(true);
      $sheet->setCellValue("A{$row}", 'TOTAL GAJI');
      $sheet->setCellValue("B{$row}", $temp_sallary);

      $row += 2;
      $sheet->getStyle("A{$row}")->getFont()->setBold(true);
      $sheet->setCellValue("A{$row}", 'PROFIT SESUAI SISTEM');
      $sheet->setCellValue("B{$row}", $price_overall - $amount_discount - $total_expenses);

      $row++;
      $netto = $price_overall - $amount_discount - $total_expenses - $temp_sallary;
      $sheet->getStyle("A{$row}")->getFont()->setBold(true);
      $sheet->setCellValue("A{$row}", 'REAL PROFIT');
      $sheet->setCellValue("B{$row}", $netto);

      $sheet->getStyle("A1:B{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

      // ── Rincian gaji per karyawan (kolom F+) ─────────────────────────
      $col    = 0;
      $letter = '';
      foreach ($sallaryUser as $value) {
        $letter = chr(70 + $col);
        $sheet->getColumnDimension($letter)->setWidth(13);

        $r = 5;
        $sheet->setCellValue("{$letter}{$r}", $value->fullname);
        $sheet->getStyle("{$letter}{$r}")->getFont()->setBold(true);

        $sheet->setCellValue("{$letter}" . ++$r, $value->basic_sallary);
        $sheet->setCellValue("{$letter}" . ++$r, $value->accomodation);
        $sheet->setCellValue("{$letter}" . ++$r, $value->eat);
        $sheet->setCellValue("{$letter}" . ++$r, $value->total_turnover);
        $sheet->setCellValue("{$letter}" . ++$r, $value->total_inpatient);
        $sheet->setCellValue("{$letter}" . ++$r, $value->total_surgery);
        $sheet->setCellValue("{$letter}" . ++$r, $value->total_grooming);
        $sheet->setCellValue("{$letter}" . ++$r, $value->fine);

        $r++;
        $sheet->setCellValue("{$letter}{$r}", $value->total_overall);
        $sheet->getStyle("{$letter}{$r}")->getFont()->setBold(true);

        $col++;
      }

      if (!empty($letter)) {
        $sheet->getStyle("F6:{$letter}14")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
      }
    }

    $spreadsheet->setActiveSheetIndex(0);

    $filename = "Rekapitulasi Bintang Vet Cabang {$branchName} {$filenameRange}";
    $writer   = IOFactory::createWriter($spreadsheet, 'Xlsx');

    return response()->stream(function () use ($writer) {
      $writer->save('php://output');
    }, 200, [
      'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      'Content-Disposition' => 'attachment; filename="' . $filename . '.xlsx"',
    ]);
  }

  public function patientSummary(Request $request)
  {
    if ($request->user()->role == 'dokter' || $request->user()->role == 'resepsionis') {
      return response()->json([
        'message' => 'The user role was invalid.',
        'errors' => ['Akses User tidak diizinkan!'],
      ], 403);
    }

    $is_yearly   = $request->periode == 2;
    $is_all_time = $request->periode == 3;
    $lastPeriods = collect();
    $listDates   = [];

    Carbon::setLocale('id');

    if (!$is_all_time) {
      if ($is_yearly) {
        if ($request->year) {
          $lastPeriods->push(['year' => (int) $request->year]);
          $listDates[] = ['dates' => (int) $request->year];
        } else {
          $currentYear = Carbon::now()->year;
          for ($i = 0; $i < 5; $i++) {
            $lastPeriods->push(['year' => $currentYear - $i]);
            $listDates[] = ['dates' => $currentYear - $i];
          }
        }
      } else {
        if ($request->month_from && $request->year_from && $request->month_to && $request->year_to) {
          $cur = Carbon::createFromDate((int) $request->year_from, (int) $request->month_from, 1)->startOfMonth();
          $end = Carbon::createFromDate((int) $request->year_to,   (int) $request->month_to,   1)->startOfMonth();
          while ($cur->lte($end)) {
            $lastPeriods->push(['month' => $cur->month, 'year' => $cur->year]);
            $listDates[] = ['dates' => $cur->translatedFormat('F Y')];
            $cur->addMonths(1);
          }
        } else {
          $month = Carbon::now()->subMonths(13);
          for ($i = 0; $i < 13; $i++) {
            $month = $month->addMonths(1);
            if ($month->day !== 1) { $month->day(1); }
            $lastPeriods->push(['month' => $month->format('n'), 'year' => $month->format('Y')]);
            $listDates[] = ['dates' => $month->translatedFormat('F Y')];
          }
        }
      }
    }

    // Build date range for query
    $dateFrom = $dateTo = null;
    if (!$is_all_time) {
      if ($is_yearly) {
        $year     = $request->year ? (int) $request->year : now()->year;
        $dateFrom = Carbon::createFromDate($year, 1, 1)->startOfYear();
        $dateTo   = Carbon::createFromDate($year, 12, 31)->endOfYear();
      } else {
        if ($request->month_from && $request->year_from && $request->month_to && $request->year_to) {
          $dateFrom = Carbon::createFromDate((int) $request->year_from, (int) $request->month_from, 1)->startOfMonth();
          $dateTo   = Carbon::createFromDate((int) $request->year_to,   (int) $request->month_to,   1)->endOfMonth();
        } else {
          $dateFrom = now()->subMonths(13)->startOfMonth();
          $dateTo   = now()->endOfMonth();
        }
      }
    }

    $groupSel = $is_yearly
      ? 'YEAR(registrations.created_at) as year_val'
      : 'YEAR(registrations.created_at) as year_val, MONTH(registrations.created_at) as month_val';
    $groupBy = $is_yearly
      ? 'YEAR(registrations.created_at), complaints.id'
      : 'YEAR(registrations.created_at), MONTH(registrations.created_at), complaints.id';

    $q = DB::table('registrations')
      ->join('patients', 'registrations.patient_id', '=', 'patients.id')
      ->join('users', 'registrations.doctor_user_id', '=', 'users.id')
      ->join('branches', 'users.branch_id', '=', 'branches.id')
      ->leftJoin('complaints', 'registrations.complaint_id', '=', 'complaints.id')
      ->selectRaw("COUNT(registrations.id) as total, COALESCE(complaints.name, 'Lainnya') as complaint_name, complaints.id as complaint_id, {$groupSel}")
      ->where('registrations.isDeleted', '=', 0)
      ->groupByRaw($groupBy)
      ->orderByRaw($groupBy);

    if (!$is_all_time) {
      $q->where('registrations.created_at', '>=', $dateFrom)
        ->where('registrations.created_at', '<=', $dateTo);
    }
    if ($request->branch_id) {
      $q->where('branches.id', '=', $request->branch_id);
    } elseif ($request->user()->role !== 'admin') {
      $q->where('branches.id', '=', $request->user()->branch_id);
    }

    $rows = $q->get();

    // Build period => complaint => total map, and collect complaint order
    $makeKey = $is_yearly
      ? fn($r) => (string) $r->year_val
      : fn($r) => $r->year_val . '-' . $r->month_val;

    $counts_map     = [];
    $complaint_order = [];
    foreach ($rows as $row) {
      $key = $makeKey($row);
      $counts_map[$key][$row->complaint_name] = (int) $row->total;
      $complaint_order[$row->complaint_id ?? 'null'] = $row->complaint_name;
    }

    // Sort complaints by id, 'Lainnya' (null id) last
    uksort($complaint_order, function ($a, $b) {
      if ($a === 'null') return 1;
      if ($b === 'null') return -1;
      return (int) $a - (int) $b;
    });
    $complaints = array_values($complaint_order);

    // For all_time: derive periods from the map
    if ($is_all_time) {
      $allKeys = collect(array_keys($counts_map))
        ->filter(fn($k) => substr_count($k, '-') === 1)
        ->sort(function ($a, $b) {
          [$ay, $am] = explode('-', $a);
          [$by, $bm] = explode('-', $b);
          return $ay !== $by ? (int)$ay - (int)$by : (int)$am - (int)$bm;
        })
        ->values();

      foreach ($allKeys as $k) {
        [$yr, $mo] = explode('-', $k);
        $yr = (int) $yr; $mo = (int) $mo;
        if ($yr < 2000 || $mo < 1 || $mo > 12) continue;
        $lastPeriods->push(['month' => $mo, 'year' => $yr]);
        $listDates[] = ['dates' => Carbon::createFromDate($yr, $mo, 1)->translatedFormat('F Y')];
      }
    }

    // Build result rows (one per period, columns per complaint)
    $datas = collect();
    foreach ($lastPeriods as $i => $val) {
      $key           = $is_yearly ? (string) $val['year'] : $val['year'] . '-' . $val['month'];
      $period_counts = $counts_map[$key] ?? [];

      $row_data = ['dates' => $listDates[$i]['dates']];
      $total    = 0;
      foreach ($complaints as $complaint) {
        $count              = $period_counts[$complaint] ?? 0;
        $row_data[$complaint] = $count;
        $total              += $count;
      }
      $row_data['total'] = $total;
      $datas->push($row_data);
    }

    return response()->json([
      'complaints' => $complaints,
      'data'       => $datas,
    ], 200);
  }

  private function buildFinancialMaps(Request $request, bool $is_yearly, bool $is_all_time = false): array
  {
    $branchId = $request->branch_id ?: 'all';
    $byYear   = $is_yearly;

    if ($is_all_time) {
      $tick = 'sejak_awal_m';
    } elseif ($is_yearly && $request->year) {
      $tick = 'y' . $request->year;
    } elseif (!$is_yearly && $request->month_from && $request->year_from && $request->month_to && $request->year_to) {
      $tick = 'm' . $request->year_from . str_pad($request->month_from, 2, '0', STR_PAD_LEFT)
            . '-' . $request->year_to   . str_pad($request->month_to,   2, '0', STR_PAD_LEFT);
    } else {
      $tick = $is_yearly ? now()->format('Y') : now()->format('Ym');
    }

    $cacheKey = "rekap_maps_{$branchId}_{$tick}";
    $cacheTtl = $is_all_time ? 1800 : 600;

    return Cache::remember($cacheKey, $cacheTtl, function () use ($request, $is_yearly, $is_all_time, $byYear) {
      $poi_map = $adi_map = $pos_map = $ads_map = $posc_map = $poshop_map = $exp_map = $py_map = [];

      $makeKey = $byYear
        ? fn($r) => (string) $r->year_val
        : fn($r) => $r->year_val . '-' . $r->month_val;

      // Date range — skipped entirely for all_time
      $dateFrom = $dateTo = null;
      if (!$is_all_time) {
        if ($is_yearly) {
          $year     = $request->year ? (int) $request->year : now()->year;
          $dateFrom = Carbon::createFromDate($year, 1, 1)->startOfYear();
          $dateTo   = Carbon::createFromDate($year, 12, 31)->endOfYear();
        } else {
          if ($request->month_from && $request->year_from && $request->month_to && $request->year_to) {
            $dateFrom = Carbon::createFromDate((int) $request->year_from, (int) $request->month_from, 1)->startOfMonth();
            $dateTo   = Carbon::createFromDate((int) $request->year_to,   (int) $request->month_to,   1)->endOfMonth();
          } else {
            $dateFrom = now()->subMonths(13)->startOfMonth();
            $dateTo   = now()->endOfMonth();
          }
        }
      }

      // ── 1. Items ──────────────────────────────────────────────────────────
      $lopmSel   = $byYear ? 'YEAR(lopm.updated_at) as year_val' : 'YEAR(lopm.updated_at) as year_val, MONTH(lopm.updated_at) as month_val';
      $lopmGroup = $byYear ? 'YEAR(lopm.updated_at)' : 'YEAR(lopm.updated_at), MONTH(lopm.updated_at)';

      $q = DB::table('list_of_payments as lop')
        ->join('list_of_payment_medicine_groups as lopm', 'lop.id', '=', 'lopm.list_of_payment_id')
        ->join('price_medicine_groups as pmg', 'lopm.medicine_group_id', '=', 'pmg.id')
        ->join('users', 'lop.user_id', '=', 'users.id')
        ->join('branches', 'users.branch_id', '=', 'branches.id')
        ->selectRaw("(CASE WHEN lopm.quantity = 0 THEN SUM(pmg.selling_price) ELSE SUM(pmg.selling_price * lopm.quantity) END)+0 as price_overall, SUM(lopm.amount_discount)+0 as amount_discount, {$lopmSel}")
        ->groupByRaw($lopmGroup);
      if (!$is_all_time) { $q->where('lopm.updated_at', '>=', $dateFrom)->where('lopm.updated_at', '<=', $dateTo); }
      if ($request->branch_id) { $q->where('branches.id', '=', $request->branch_id); }
      foreach ($q->get() as $row) {
        $key = $makeKey($row);
        $poi_map[$key] = $row->price_overall;
        $adi_map[$key] = $row->amount_discount;
      }

      // ── 2. Services ───────────────────────────────────────────────────────
      $lopsSel   = $byYear ? 'YEAR(lops.updated_at) as year_val' : 'YEAR(lops.updated_at) as year_val, MONTH(lops.updated_at) as month_val';
      $lopsGroup = $byYear ? 'YEAR(lops.updated_at)' : 'YEAR(lops.updated_at), MONTH(lops.updated_at)';

      $q = DB::table('list_of_payments as lop')
        ->join('check_up_results as cur', 'lop.check_up_result_id', '=', 'cur.id')
        ->join('list_of_payment_services as lops', 'cur.id', '=', 'lops.check_up_result_id')
        ->join('detail_service_patients as dsp', 'lops.detail_service_patient_id', '=', 'dsp.id')
        ->join('users', 'cur.user_id', '=', 'users.id')
        ->join('branches', 'users.branch_id', '=', 'branches.id')
        ->selectRaw("SUM(dsp.price_overall)+0 as price_overall, SUM(lops.amount_discount)+0 as amount_discount, {$lopsSel}")
        ->groupByRaw($lopsGroup);
      if (!$is_all_time) { $q->where('lops.updated_at', '>=', $dateFrom)->where('lops.updated_at', '<=', $dateTo); }
      if ($request->branch_id) { $q->where('branches.id', '=', $request->branch_id); }
      foreach ($q->get() as $row) {
        $key = $makeKey($row);
        $pos_map[$key] = $row->price_overall;
        $ads_map[$key] = $row->amount_discount;
      }

      // ── 3. Shop clinic ────────────────────────────────────────────────────
      $ppwcSel   = $byYear ? 'YEAR(ppwc.created_at) as year_val' : 'YEAR(ppwc.created_at) as year_val, MONTH(ppwc.created_at) as month_val';
      $ppwcGroup = $byYear ? 'YEAR(ppwc.created_at)' : 'YEAR(ppwc.created_at), MONTH(ppwc.created_at)';

      $q = DB::table('payment_petshop_with_clinics as ppwc')
        ->join('price_item_pet_shops as pip', 'ppwc.price_item_pet_shop_id', '=', 'pip.id')
        ->join('users', 'ppwc.user_id', '=', 'users.id')
        ->join('branches', 'users.branch_id', '=', 'branches.id')
        ->selectRaw("TRIM(SUM(pip.selling_price * ppwc.total_item))+0 as price_overall, {$ppwcSel}")
        ->groupByRaw($ppwcGroup);
      if (!$is_all_time) { $q->where('ppwc.created_at', '>=', $dateFrom)->where('ppwc.created_at', '<=', $dateTo); }
      if ($request->branch_id) { $q->where('branches.id', '=', $request->branch_id); }
      foreach ($q->get() as $row) {
        $posc_map[$makeKey($row)] = $row->price_overall;
      }

      // ── 4. Petshop standalone ─────────────────────────────────────────────
      $ppSel   = $byYear ? 'YEAR(pp.created_at) as year_val' : 'YEAR(pp.created_at) as year_val, MONTH(pp.created_at) as month_val';
      $ppGroup = $byYear ? 'YEAR(pp.created_at)' : 'YEAR(pp.created_at), MONTH(pp.created_at)';

      $q = DB::table('payment_petshops as pp')
        ->join('price_item_pet_shops as pip', 'pp.price_item_pet_shop_id', '=', 'pip.id')
        ->join('users', 'pp.user_id', '=', 'users.id')
        ->join('branches', 'users.branch_id', '=', 'branches.id')
        ->selectRaw("TRIM(SUM(pip.selling_price * pp.total_item))+0 as price_overall, {$ppSel}")
        ->groupByRaw($ppGroup);
      if (!$is_all_time) { $q->where('pp.created_at', '>=', $dateFrom)->where('pp.created_at', '<=', $dateTo); }
      if ($request->branch_id) { $q->where('branches.id', '=', $request->branch_id); }
      foreach ($q->get() as $row) {
        $poshop_map[$makeKey($row)] = $row->price_overall;
      }

      // ── 5. Expenses ───────────────────────────────────────────────────────
      $expSel   = $byYear ? 'YEAR(e.date_spend) as year_val' : 'YEAR(e.date_spend) as year_val, MONTH(e.date_spend) as month_val';
      $expGroup = $byYear ? 'YEAR(e.date_spend)' : 'YEAR(e.date_spend), MONTH(e.date_spend)';

      $q = DB::table('expenses as e')
        ->leftJoin('users as u', 'e.user_id_spender', '=', 'u.id')
        ->selectRaw("TRIM(SUM(IFNULL(e.amount_overall,0)))+0 as amount_overall, {$expSel}")
        ->whereRaw('COALESCE(e.isDeleted, 0) = 0')
        ->groupByRaw($expGroup);
      if (!$is_all_time) { $q->where('e.date_spend', '>=', $dateFrom)->where('e.date_spend', '<=', $dateTo); }
      if ($request->branch_id) { $q->where('u.branch_id', '=', $request->branch_id); }
      foreach ($q->get() as $row) {
        $exp_map[$makeKey($row)] = $row->amount_overall;
      }

      // ── 6. Payrolls ───────────────────────────────────────────────────────
      $pySel   = $byYear ? 'YEAR(py.date_payed) as year_val' : 'YEAR(py.date_payed) as year_val, MONTH(py.date_payed) as month_val';
      $pyGroup = $byYear ? 'YEAR(py.date_payed)' : 'YEAR(py.date_payed), MONTH(py.date_payed)';

      $q = DB::table('payrolls as py')
        ->join('users', 'py.user_employee_id', '=', 'users.id')
        ->join('branches', 'users.branch_id', '=', 'branches.id')
        ->where('py.isDeleted', '=', 0)
        ->selectRaw("SUM(py.total_overall) as total_overall, {$pySel}")
        ->groupByRaw($pyGroup);
      if (!$is_all_time) { $q->where('py.date_payed', '>=', $dateFrom)->where('py.date_payed', '<=', $dateTo); }
      if ($request->branch_id) { $q->where('branches.id', '=', $request->branch_id); }
      foreach ($q->get() as $row) {
        $py_map[$makeKey($row)] = $row->total_overall;
      }

      return compact('poi_map', 'pos_map', 'posc_map', 'poshop_map', 'adi_map', 'ads_map', 'exp_map', 'py_map');
    });
  }
}
