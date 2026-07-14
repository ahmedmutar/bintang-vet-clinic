<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
  public function BarChartPatient(Request $request)
  {
    $data = DB::table('registrations')
      ->join('patients', 'registrations.patient_id', '=', 'patients.id')
      ->join('users', 'registrations.doctor_user_id', '=', 'users.id')
      ->join('branches', 'users.branch_id', '=', 'branches.id')
      ->leftJoin('complaints', 'registrations.complaint_id', '=', 'complaints.id')
      ->select(
        DB::raw('COUNT(registrations.id) as total_patient'),
        'branches.branch_name',
        DB::raw("COALESCE(complaints.name, 'Lainnya') as complaint_name"),
        'complaints.id as complaint_id'
      )
      ->where('registrations.isDeleted', '=', 0);

    if ($request->user()->role !== 'admin') {
      $data = $data->where('branches.id', '=', $request->user()->branch_id);
    }

    $periode = $request->periode ?? 'bulanan';

    if ($periode === 'harian' && $request->date) {
      $data = $data->where(DB::raw("DATE(registrations.created_at)"), $request->date);
    } elseif ($periode === 'mingguan' && $request->date_from && $request->date_to) {
      $data = $data->whereRaw("DATE(registrations.created_at) BETWEEN ? AND ?", [$request->date_from, $request->date_to]);
    } elseif ($periode === 'bulanan' && $request->month && $request->year) {
      $data = $data->where(DB::raw("MONTH(registrations.created_at)"), $request->month)
        ->where(DB::raw("YEAR(registrations.created_at)"), $request->year);
    }

    $data = $data->groupBy('branches.branch_name', 'complaints.id')
      ->orderBy('complaints.id')
      ->get()
      ->map(function ($item) {
        $item->total_patient = (int) $item->total_patient;
        return $item;
      });

    return response()->json($data, 200);
  }

  public function BarChartInPatient(Request $request)
  {
    $data = DB::table('check_up_results as cur')
      ->join('registrations as reg', 'cur.patient_registration_id', '=', 'reg.id')
      ->join('users', 'cur.user_id', '=', 'users.id')
      ->join('branches', 'users.branch_id', '=', 'branches.id')
      ->join('detail_service_patients as dsp', 'dsp.check_up_result_id', '=', 'cur.id')
      ->join('price_services as ps', 'dsp.price_service_id', '=', 'ps.id')
      ->join('list_of_services as los', 'ps.list_of_services_id', '=', 'los.id')
      ->select(DB::raw('SUM(dsp.quantity) as total_patient'), 'branches.branch_name')
      ->where('reg.isDeleted', '=', 0)
      ->where('los.service_name', 'like', '%inap%');

    if ($request->user()->role !== 'admin') {
      $data = $data->where('branches.id', '=', $request->user()->branch_id);
    }

    $periode = $request->periode ?? 'bulanan';

    if ($periode === 'harian' && $request->date) {
      $data = $data->where(DB::raw("DATE(cur.created_at)"), $request->date);
    } elseif ($periode === 'mingguan' && $request->date_from && $request->date_to) {
      $data = $data->whereRaw("DATE(cur.created_at) BETWEEN ? AND ?", [$request->date_from, $request->date_to]);
    } elseif ($periode === 'bulanan' && $request->month && $request->year) {
      $data = $data->where(DB::raw("MONTH(cur.created_at)"), $request->month)
        ->where(DB::raw("YEAR(cur.created_at)"), $request->year);
    }

    $data = $data->groupby('branches.branch_name')
      ->get()
      ->map(function ($item) {
        $item->total_patient = (int) $item->total_patient;
        return $item;
      });

    return response()->json($data, 200);
  }

  public function PasienTidakPengabaran(Request $request)
  {
    $periode = $request->periode ?? 'bulanan';

    $base = DB::table('check_up_results as cur')
      ->join('registrations as reg', 'cur.patient_registration_id', '=', 'reg.id')
      ->join('patients as pa', 'reg.patient_id', '=', 'pa.id')
      ->join('owners as ow', 'pa.owner_id', '=', 'ow.id')
      ->join('users', 'cur.user_id', '=', 'users.id')
      ->join('branches', 'users.branch_id', '=', 'branches.id')
      ->where('reg.isDeleted', '=', 0)
      ->where(function ($q) {
        $q->where('cur.status_pengabaran', '=', 0)
          ->orWhereNull('cur.status_pengabaran');
      });

    if ($request->user()->role !== 'admin') {
      $base->where('branches.id', '=', $request->user()->branch_id);
    } elseif ($request->branch_id) {
      $base->where('branches.id', $request->branch_id);
    }

    if ($periode === 'harian' && $request->date) {
      $base->where(DB::raw("DATE(cur.created_at)"), $request->date);
    } elseif ($periode === 'mingguan' && $request->date_from && $request->date_to) {
      $base->whereRaw("DATE(cur.created_at) BETWEEN ? AND ?", [$request->date_from, $request->date_to]);
    } elseif ($periode === 'bulanan' && $request->month && $request->year) {
      $base->where(DB::raw("MONTH(cur.created_at)"), $request->month)
        ->where(DB::raw("YEAR(cur.created_at)"), $request->year);
    }

    $chart = (clone $base)
      ->select('branches.branch_name', DB::raw('COUNT(cur.id) as total'))
      ->groupBy('branches.branch_name')
      ->orderBy('branches.branch_name')
      ->get()
      ->map(fn($item) => tap($item, fn($i) => $i->total = (int) $i->total));

    $list = (clone $base)
      ->select(
        'pa.pet_name',
        DB::raw("TRIM(COALESCE(NULLIF(pa.owner_name,''), ow.owner_name, '')) as owner_name"),
        'branches.branch_name',
        DB::raw("COALESCE(cur.alasan_tidak_pengabaran, '-') as alasan"),
        DB::raw("DATE_FORMAT(cur.created_at, '%d %b %Y') as tanggal")
      )
      ->orderBy('cur.created_at', 'desc')
      ->get();

    return response()->json(['chart' => $chart, 'list' => $list], 200);
  }
}
