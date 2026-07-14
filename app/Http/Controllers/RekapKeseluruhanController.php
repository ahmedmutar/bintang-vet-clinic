<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RekapOmsetExport;

class RekapKeseluruhanController extends Controller
{
    private function getFirstTransactionDate(?Request $request = null): string
    {
        // Jika branch tertentu dipilih, cari tanggal transaksi pertama branch itu saja
        if ($request && $request->branch_id && $request->connection) {
            try {
                $row = DB::connection($request->connection)
                    ->table('list_of_payments as lop')
                    ->join('users', 'lop.user_id', '=', 'users.id')
                    ->join('branches', 'users.branch_id', '=', 'branches.id')
                    ->selectRaw('MIN(DATE(lop.created_at)) as min_date')
                    ->where('branches.id', $request->branch_id)
                    ->first();

                if ($row && $row->min_date) {
                    return $row->min_date;
                }
            } catch (\Exception) {}

            return Carbon::now()->subYear()->format('Y-m-d');
        }

        // Semua branch → global minimum dari semua koneksi
        $conns   = ['mysql', 'mysql_second', 'mysql_third', 'mysql_forth'];
        $minDate = null;

        foreach ($conns as $conn) {
            try {
                $row = DB::connection($conn)
                    ->table('list_of_payments')
                    ->selectRaw('MIN(DATE(created_at)) as min_date')
                    ->first();

                if ($row && $row->min_date) {
                    if (!$minDate || $row->min_date < $minDate) {
                        $minDate = $row->min_date;
                    }
                }
            } catch (\Exception) {
                // skip connection jika tidak tersedia
            }
        }

        return $minDate ?? Carbon::now()->subYear()->format('Y-m-d');
    }

    /**
     * Build period list based on filter type.
     *
     * Returns: [$periods, $groupBy, $dateFrom, $dateTo]
     *   $periods  — array of ['key' => ..., 'label' => ...]
     *   $groupBy  — 'day' | 'month'
     *   $dateFrom — SQL-safe date string for WHERE
     *   $dateTo   — SQL-safe date string for WHERE
     */
    private function buildPeriods(Request $request): array
    {
        Carbon::setLocale('id');
        $periode = $request->periode ?? 'mingguan';
        $periods = [];
        $groupBy = 'day';

        switch ($periode) {
            case 'bulanan':
                $cur = $request->start_month
                    ? Carbon::createFromFormat('Y-m', $request->start_month)->startOfMonth()
                    : Carbon::now()->startOfMonth();
                $end = $request->end_month
                    ? Carbon::createFromFormat('Y-m', $request->end_month)->startOfMonth()
                    : Carbon::now()->startOfMonth();
                while ($cur->lte($end)) {
                    $periods[] = [
                        'key'   => $cur->format('Y-m'),
                        'label' => $cur->translatedFormat('F Y'),
                    ];
                    $cur->addMonth();
                }
                $groupBy = 'month';
                break;

            case 'tahunan':
                $year = $request->tahun ? (int) $request->tahun : Carbon::now()->year;
                for ($m = 1; $m <= 12; $m++) {
                    $month = Carbon::createFromDate($year, $m, 1);
                    $periods[] = [
                        'key'   => $month->format('Y-m'),
                        'label' => $month->translatedFormat('F Y'),
                    ];
                }
                $groupBy = 'month';
                break;

            case 'sejak_dibuka':
                $firstDate = $this->getFirstTransactionDate($request);
                $cur = Carbon::parse($firstDate)->startOfMonth();
                $end = Carbon::now()->startOfMonth();
                while ($cur->lte($end)) {
                    $periods[] = [
                        'key'   => $cur->format('Y-m'),
                        'label' => $cur->translatedFormat('F Y'),
                    ];
                    $cur->addMonth();
                }
                $groupBy = 'month';
                break;

            default: // mingguan
                $cur = $request->start_date
                    ? Carbon::parse($request->start_date)
                    : Carbon::now()->startOfWeek();
                $end = $request->end_date
                    ? Carbon::parse($request->end_date)
                    : Carbon::now()->endOfWeek();
                while ($cur->lte($end)) {
                    $periods[] = [
                        'key'   => $cur->format('Y-m-d'),
                        'label' => $cur->translatedFormat('j M Y'),
                    ];
                    $cur->addDay();
                }
                $groupBy = 'day';
                break;
        }

        $keys     = array_column($periods, 'key');
        $dateFrom = $groupBy === 'day'
            ? $keys[0]
            : Carbon::createFromFormat('Y-m', $keys[0])->startOfMonth()->format('Y-m-d');
        $dateTo   = $groupBy === 'day'
            ? $keys[count($keys) - 1]
            : Carbon::createFromFormat('Y-m', $keys[count($keys) - 1])->endOfMonth()->format('Y-m-d');

        return [$periods, $groupBy, $dateFrom, $dateTo];
    }

    // Load branches from all connections, adding branch_slug to each
    private function getBranches(Request $request): Collection
    {
        if ($request->branch_id) {
            return DB::connection($request->connection)->table('branches')
                ->select('id', 'branch_name', DB::raw("'{$request->connection}' as connection"))
                ->where('isDeleted', 0)
                ->where('id', $request->branch_id)
                ->get()
                ->map(function ($b) {
                    $b->branch_slug = Str::slug($b->branch_name, '_');
                    return $b;
                });
        }

        $sources = [
            'mysql'        => 4,
            'mysql_second' => 1,
            'mysql_third'  => null,
            'mysql_forth'  => null,
        ];

        $all = collect();
        foreach ($sources as $conn => $excludeId) {
            $q = DB::connection($conn)->table('branches')
                ->select('id', 'branch_name', DB::raw("'{$conn}' as connection"))
                ->where('isDeleted', 0);
            if ($excludeId !== null) {
                $q->where('id', '!=', $excludeId);
            }
            $all = $all->merge($q->get());
        }

        return $all->map(function ($b) {
            $b->branch_slug = Str::slug($b->branch_name, '_');
            return $b;
        })->values();
    }

    /**
     * Fetch total omset per branch per period.
     * Formula mirrors LaporanKeuanganMingguanController (diff>0 block, line 230-231):
     *   price_overall = medicine + service + petshop_with_clinic + petshop
     * Single UNION ALL query per connection for efficiency.
     */
    private function fetchNetByBranchAndPeriod(
        Collection $branches,
        array $periods,
        string $groupBy,
        string $dateFrom,
        string $dateTo
    ): array {
        $keys    = array_column($periods, 'key');
        $netMap  = [];
        $grpDay  = $groupBy === 'day';
        $grpExpr = $grpDay ? 'DATE(%s)' : "DATE_FORMAT(%s, '%%Y-%%m')";

        foreach ($branches->groupBy('connection') as $conn => $connBranches) {
            $branchIds = $connBranches->pluck('id')->toArray();
            $inList    = implode(',', array_map('intval', $branchIds));

            $gMed  = sprintf($grpExpr, 'lopm.updated_at');
            $gSvc  = sprintf($grpExpr, 'lops.updated_at');
            $gShop = sprintf($grpExpr, 'ppwc.created_at');
            $gPet  = sprintf($grpExpr, 'pp.created_at');

            $sql = "
                SELECT branch_id, period, SUM(total) AS total FROM (
                    SELECT b.id AS branch_id, {$gMed} AS period,
                        SUM(CASE WHEN lopm.quantity = 0 THEN pmg.selling_price ELSE pmg.selling_price * lopm.quantity END) AS total
                    FROM list_of_payments lop
                    JOIN list_of_payment_medicine_groups lopm ON lop.id = lopm.list_of_payment_id
                    JOIN price_medicine_groups pmg ON lopm.medicine_group_id = pmg.id
                    JOIN users ON lop.user_id = users.id
                    JOIN branches b ON users.branch_id = b.id
                    WHERE b.id IN ({$inList}) AND DATE(lopm.updated_at) BETWEEN ? AND ?
                    GROUP BY b.id, {$gMed}

                    UNION ALL

                    SELECT b.id AS branch_id, {$gSvc} AS period,
                        SUM(dsp.price_overall) AS total
                    FROM list_of_payments lop2
                    JOIN check_up_results cur ON lop2.check_up_result_id = cur.id
                    JOIN list_of_payment_services lops ON cur.id = lops.check_up_result_id
                    JOIN detail_service_patients dsp ON lops.detail_service_patient_id = dsp.id
                    JOIN users ON cur.user_id = users.id
                    JOIN branches b ON users.branch_id = b.id
                    WHERE b.id IN ({$inList}) AND DATE(lops.updated_at) BETWEEN ? AND ?
                    GROUP BY b.id, {$gSvc}

                    UNION ALL

                    SELECT b.id AS branch_id, {$gShop} AS period,
                        SUM(pip.selling_price * ppwc.total_item) AS total
                    FROM payment_petshop_with_clinics ppwc
                    JOIN price_item_pet_shops pip ON ppwc.price_item_pet_shop_id = pip.id
                    JOIN users ON ppwc.user_id = users.id
                    JOIN branches b ON users.branch_id = b.id
                    WHERE b.id IN ({$inList}) AND DATE(ppwc.created_at) BETWEEN ? AND ?
                    GROUP BY b.id, {$gShop}

                    UNION ALL

                    SELECT b.id AS branch_id, {$gPet} AS period,
                        SUM(pip.selling_price * pp.total_item) AS total
                    FROM payment_petshops pp
                    JOIN price_item_pet_shops pip ON pp.price_item_pet_shop_id = pip.id
                    JOIN users ON pp.user_id = users.id
                    JOIN branches b ON users.branch_id = b.id
                    WHERE b.id IN ({$inList}) AND DATE(pp.created_at) BETWEEN ? AND ?
                    GROUP BY b.id, {$gPet}
                ) r GROUP BY branch_id, period
            ";

            $rows = DB::connection($conn)->select($sql, [
                $dateFrom, $dateTo,
                $dateFrom, $dateTo,
                $dateFrom, $dateTo,
                $dateFrom, $dateTo,
            ]);

            $map = [];
            foreach ($rows as $row) {
                $map[$row->branch_id][$row->period] = (float) $row->total;
            }

            // Key by connection + branch id: branch ids are NOT unique across
            // the separate databases, so keying by id alone would let branches
            // from different connections clobber each other.
            foreach ($connBranches as $branch) {
                foreach ($keys as $key) {
                    $netMap["{$conn}:{$branch->id}"][$key] = $map[$branch->id][$key] ?? 0;
                }
            }
        }

        return $netMap;
    }

    public function index(Request $request)
    {
        set_time_limit(0);
        [$periods, $groupBy, $dateFrom, $dateTo] = $this->buildPeriods($request);
        $branches = $this->getBranches($request);
        $netMap   = $this->fetchNetByBranchAndPeriod($branches, $periods, $groupBy, $dateFrom, $dateTo);

        $datas = collect();

        if ($request->branch_id) {
            foreach ($periods as $period) {
                $total = 0;
                foreach ($branches as $b) {
                    $total += $netMap["{$b->connection}:{$b->id}"][$period['key']] ?? 0;
                }
                $datas->push(['dates' => $period['label'], 'total_omset' => $total]);
            }

            return response()->json(['datas' => $datas->toArray(), 'branches' => $branches], 200);
        }

        foreach ($periods as $period) {
            $row   = ['dates' => $period['label']];
            $total = 0;
            foreach ($branches as $b) {
                $net                  = $netMap["{$b->connection}:{$b->id}"][$period['key']] ?? 0;
                $row[$b->branch_slug] = $net;
                $total               += $net;
            }
            $row['total_omset'] = $total;
            $datas->push($row);
        }

        return response()->json(['datas' => $datas->toArray(), 'branches' => $branches], 200);
    }

    public function export(Request $request)
    {
        set_time_limit(0);
        [$periods, $groupBy, $dateFrom, $dateTo] = $this->buildPeriods($request);
        $branches = $this->getBranches($request);
        $netMap   = $this->fetchNetByBranchAndPeriod($branches, $periods, $groupBy, $dateFrom, $dateTo);

        $rows  = [];
        $idx   = 1;
        $periodeMap = [
            'mingguan'     => 'Mingguan',
            'bulanan'      => 'Bulanan',
            'tahunan'      => 'Tahunan',
            'sejak_dibuka' => 'Sejak Dibuka',
        ];
        $periodeLabel = $periodeMap[$request->periode ?? 'mingguan'] ?? ucfirst($request->periode ?? 'mingguan');

        if ($request->branch_id) {
            $headingRow = ['No', 'Periode', 'Total Omset (Rp)'];
            foreach ($periods as $period) {
                $total = 0;
                foreach ($branches as $b) {
                    $total += $netMap["{$b->connection}:{$b->id}"][$period['key']] ?? 0;
                }
                $rows[] = [$idx++, $period['label'], number_format($total, 0, ',', '.')];
            }
        } else {
            $branchNames = $branches->pluck('branch_name')->toArray();
            $headingRow  = array_merge(['No', 'Periode', 'Total Omset (Rp)'], $branchNames);
            foreach ($periods as $period) {
                $total        = 0;
                $branchValues = [];
                foreach ($branches as $b) {
                    $net            = $netMap["{$b->connection}:{$b->id}"][$period['key']] ?? 0;
                    $total         += $net;
                    $branchValues[] = number_format($net, 0, ',', '.');
                }
                $rows[] = array_merge([$idx++, $period['label'], number_format($total, 0, ',', '.')], $branchValues);
            }
        }

        $periode = $request->periode ?? 'mingguan';
        switch ($periode) {
            case 'mingguan':
                $suffix = ($request->start_date ?? '') . '-sd-' . ($request->end_date ?? '');
                break;
            case 'bulanan':
                $suffix = ($request->start_month ?? '') . '-sd-' . ($request->end_month ?? '');
                break;
            case 'tahunan':
                $suffix = $request->tahun ?? Carbon::now()->year;
                break;
            case 'sejak_dibuka':
                $keys   = array_column($periods, 'key');
                $suffix = ($keys[0] ?? '') . '-sd-' . ($keys[count($keys) - 1] ?? '');
                break;
            default:
                $suffix = now()->format('Ymd');
        }
        $filename = 'rekap-omset-' . str_replace('_', '-', $periode) . '-' . $suffix . '.xlsx';

        return Excel::download(new RekapOmsetExport($rows, $headingRow, $periodeLabel), $filename);
    }

    public function chart(Request $request)
    {
        set_time_limit(0);
        [$periods, $groupBy, $dateFrom, $dateTo] = $this->buildPeriods($request);
        $branches = $this->getBranches($request);
        $netMap   = $this->fetchNetByBranchAndPeriod($branches, $periods, $groupBy, $dateFrom, $dateTo);

        $datas = collect();
        foreach ($periods as $period) {
            $total = 0;
            foreach ($branches as $b) {
                $total += $netMap["{$b->connection}:{$b->id}"][$period['key']] ?? 0;
            }
            $datas->push(['dates' => $period['label'], 'total_omset' => $total]);
        }

        return response()->json($datas, 200);
    }
}
