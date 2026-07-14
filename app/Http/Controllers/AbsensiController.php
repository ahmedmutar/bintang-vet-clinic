<?php

namespace App\Http\Controllers;

use App\Exports\AbsensiExport;
use App\Models\AbsensiRadiusException;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Shift;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Validator;

class AbsensiController extends Controller
{
    public function cekHariIni(Request $request)
    {
        $today = Carbon::now()->lt(Carbon::today()->setTime(5, 30))
            ? Carbon::yesterday()->toDateString()
            : Carbon::today()->toDateString();
        $attendance = DB::table('attendances')
            ->where('user_id', $request->user()->id)
            ->where('tanggal', $today)
            ->first();

        $branch = Branch::where('id', $request->user()->branch_id)
            ->select('latitude', 'longitude')
            ->first();

        $isBypassed = AbsensiRadiusException::where('username', $request->user()->username)->exists();

        $shiftJamKeluar = null;
        if ($attendance && !$attendance->jam_keluar && $attendance->shift_id) {
            $shift = Shift::find($attendance->shift_id);
            $shiftJamKeluar = $shift ? $shift->jam_keluar : null;
        }

        return response()->json([
            'sudah_absen'        => $attendance ? true : false,
            'sudah_keluar'       => $attendance && $attendance->jam_keluar ? true : false,
            'data'               => $attendance,
            'branch_latitude'    => $branch ? $branch->latitude : null,
            'branch_longitude'   => $branch ? $branch->longitude : null,
            'is_radius_bypassed' => $isBypassed,
            'shift_jam_keluar'   => $shiftJamKeluar,
        ], 200);
    }

    public function masuk(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shift_id' => 'required|integer',
            'foto'     => 'required|string',
            'latitude'    => 'nullable|numeric',
            'longitude'   => 'nullable|numeric',
            'alamat'      => 'nullable|string',
            'keterangan'  => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Data tidak valid!', 'errors' => $validator->errors()->all()], 422);
        }

        $today = Carbon::now()->lt(Carbon::today()->setTime(5, 30))
            ? Carbon::yesterday()->toDateString()
            : Carbon::today()->toDateString();

        $existing = DB::table('attendances')
            ->where('user_id', $request->user()->id)
            ->where('tanggal', $today)
            ->first();

        if ($existing) {
            return response()->json(['message' => 'Anda sudah melakukan absen masuk hari ini!', 'errors' => ['Sudah absen masuk!']], 422);
        }

        $shift = Shift::find($request->shift_id);
        if (!$shift) {
            return response()->json(['message' => 'Shift tidak ditemukan!', 'errors' => ['Shift tidak valid!']], 404);
        }

        $jamMasukHariIni = Carbon::today()->setTimeFromTimeString($shift->jam_masuk);
        $bolehAbsenDari  = $jamMasukHariIni->copy()->subHours(2);

        // Hanya blokir jika window belum dimulai hari ini (tidak berlaku untuk shift dini hari
        // yang window-nya jatuh di hari sebelumnya — dalam kasus itu, izinkan saja).
        if ($bolehAbsenDari->isToday() && Carbon::now()->lt($bolehAbsenDari)) {
            $waktuBoleh = $bolehAbsenDari->format('H:i');
            return response()->json([
                'message' => "Absensi belum bisa dilakukan. Anda baru bisa absen mulai pukul {$waktuBoleh}.",
                'errors'  => ["Absensi hanya bisa dilakukan mulai pukul {$waktuBoleh} (2 jam sebelum jam masuk)."],
            ], 422);
        }

        $sekarang    = Carbon::now();
        $batasLambat = $jamMasukHariIni->copy()->addMinutes($shift->toleransi_menit);
        $status      = $sekarang->lte($batasLambat) ? 'hadir' : 'terlambat';

        $fotoPath = $this->simpanFoto($request->foto, 'absensi/masuk');

        $jarakMeter = null;
        if ($request->latitude && $request->longitude) {
            $branch = Branch::where('id', $request->user()->branch_id)->first();
            if ($branch && $branch->latitude && $branch->longitude) {
                $jarakMeter = $this->hitungJarak(
                    (float) $request->latitude,
                    (float) $request->longitude,
                    (float) $branch->latitude,
                    (float) $branch->longitude
                );

                if ($jarakMeter > 500) {
                    $isBypassed = AbsensiRadiusException::where('username', $request->user()->username)->exists();
                    if (!$isBypassed) {
                        return response()->json([
                            'message'     => "Absensi gagal. Anda berada {$jarakMeter} meter dari klinik. Maksimal jarak yang diizinkan adalah 500 meter.",
                            'errors'      => ["Jarak terlalu jauh ({$jarakMeter} m). Anda harus berada dalam radius 500 meter dari klinik."],
                            'jarak_meter' => $jarakMeter,
                        ], 422);
                    }
                }
            }
        }

        Attendance::create([
            'user_id'     => $request->user()->id,
            'shift_id'    => $request->shift_id,
            'tanggal'     => $today,
            'jam_masuk'   => $sekarang->format('H:i:s'),
            'latitude'    => $request->latitude,
            'longitude'   => $request->longitude,
            'alamat'      => $request->alamat,
            'jarak_meter' => $jarakMeter,
            'keterangan'  => $request->keterangan,
            'foto_masuk'  => $fotoPath,
            'status'      => $status,
        ]);

        $pesan = $status === 'terlambat' ? 'Absen masuk berhasil (Terlambat).' : 'Absen masuk berhasil!';

        return response()->json(['message' => $pesan, 'status' => $status], 200);
    }

    public function keluar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'foto'                    => 'required|string',
            'alasan_terlambat_pulang' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Data tidak valid!', 'errors' => $validator->errors()->all()], 422);
        }

        $today = Carbon::now()->lt(Carbon::today()->setTime(5, 30))
            ? Carbon::yesterday()->toDateString()
            : Carbon::today()->toDateString();

        $attendance = Attendance::where('user_id', $request->user()->id)
            ->where('tanggal', $today)
            ->first();

        if (!$attendance) {
            return response()->json(['message' => 'Anda belum melakukan absen masuk hari ini!', 'errors' => ['Belum absen masuk!']], 422);
        }

        if ($attendance->jam_keluar) {
            return response()->json(['message' => 'Anda sudah melakukan absen keluar hari ini!', 'errors' => ['Sudah absen keluar!']], 422);
        }

        $fotoPath = $this->simpanFoto($request->foto, 'absensi/keluar');

        $jamKeluar         = Carbon::now();
        $shift             = Shift::find($attendance->shift_id);
        $shiftSelesai      = Carbon::parse($attendance->tanggal)->setTimeFromTimeString($shift->jam_keluar);
        $batasLambatPulang = $shiftSelesai->copy()->addHour();

        if ($jamKeluar->gt($batasLambatPulang) && !$request->alasan_terlambat_pulang) {
            return response()->json([
                'message' => 'Anda pulang lebih dari 1 jam setelah jam shift selesai. Mohon isi alasan terlebih dahulu.',
                'errors'  => ['Alasan wajib diisi jika pulang lebih dari 1 jam setelah jam shift.'],
                'perlu_alasan' => true,
            ], 422);
        }

        $attendance->jam_keluar              = $jamKeluar->format('H:i:s');
        $attendance->foto_keluar             = $fotoPath;
        $attendance->alasan_terlambat_pulang = $request->alasan_terlambat_pulang ?: null;
        if ($jamKeluar->lt($shiftSelesai)) {
            $attendance->status = 'tidak_sesuai';
        }
        $attendance->save();

        $pesan = $jamKeluar->lt($shiftSelesai)
            ? 'Absen keluar berhasil. Anda pulang sebelum jam shift selesai (Absensi Tidak Sesuai).'
            : 'Absen keluar berhasil!';

        return response()->json(['message' => $pesan], 200);
    }

    public function index(Request $request)
    {
        $items_per_page = 50;
        $page           = max(1, (int) ($request->page ?? 1));

        $query = DB::table('attendances as a')
            ->join('users', 'a.user_id', '=', 'users.id')
            ->join('shifts', 'a.shift_id', '=', 'shifts.id')
            ->join('branches', 'users.branch_id', '=', 'branches.id')
            ->select(
                'a.id',
                'users.fullname',
                'users.username',
                'branches.branch_name',
                'shifts.nama_shift',
                DB::raw("DATE_FORMAT(a.tanggal, '%d %b %Y') as tanggal"),
                DB::raw("TIME_FORMAT(a.jam_masuk, '%H:%i') as jam_masuk"),
                DB::raw("TIME_FORMAT(a.jam_keluar, '%H:%i') as jam_keluar"),
                DB::raw("TIME_FORMAT(shifts.jam_masuk, '%H:%i') as shift_jam_masuk"),
                DB::raw("TIME_FORMAT(shifts.jam_keluar, '%H:%i') as shift_jam_keluar"),
                'a.foto_masuk',
                'a.foto_keluar',
                'a.latitude',
                'a.longitude',
                'a.alamat',
                'a.jarak_meter',
                'a.keterangan',
                DB::raw("
                    CASE
                        WHEN a.status = 'tidak_sesuai' THEN 'tidak_sesuai'
                        WHEN a.jam_keluar IS NOT NULL
                            AND a.jam_keluar < shifts.jam_keluar
                            AND NOT (a.jam_keluar <= '05:30:00' AND shifts.jam_keluar > shifts.jam_masuk)
                            THEN 'tidak_sesuai'
                        WHEN a.jam_keluar IS NULL AND (
                            a.tanggal < CURDATE()
                            OR (a.tanggal = CURDATE() AND TIME(NOW()) > shifts.jam_keluar)
                        ) THEN 'tidak_sesuai'
                        ELSE a.status
                    END as status
                ")
            );

        if ($request->user()->role !== 'admin') {
            $query->where('a.user_id', '=', $request->user()->id);
        }

        if ($request->branch_id) {
            $query->where('users.branch_id', '=', $request->branch_id);
        }

        if ($request->tanggal_dari) {
            $query->where('a.tanggal', '>=', $request->tanggal_dari);
        }

        if ($request->tanggal_sampai) {
            $query->where('a.tanggal', '<=', $request->tanggal_sampai);
        }

        if ($request->shift_id) {
            $query->where('a.shift_id', '=', $request->shift_id);
        }

        if ($request->status) {
            $query->havingRaw('status = ?', [$request->status]);
        }

        if ($request->keyword) {
            $query->where('users.fullname', 'like', '%' . $request->keyword . '%');
        }

        // Order by
        $allowedColumns = [
            'fullname'    => 'users.fullname',
            'branch_name' => 'branches.branch_name',
            'nama_shift'  => 'shifts.nama_shift',
            'tanggal'     => 'a.tanggal',
            'jam_masuk'   => 'a.jam_masuk',
            'jam_keluar'  => 'a.jam_keluar',
            'status'      => 'status',
        ];

        $orderColumn = $allowedColumns[$request->column ?? ''] ?? 'a.tanggal';
        $orderDir    = strtolower($request->orderby ?? '') === 'asc' ? 'asc' : 'desc';

        if ($orderColumn === 'status') {
            $query->orderByRaw("status {$orderDir}");
        } else {
            $query->orderBy($orderColumn, $orderDir);
        }
        $query->orderBy('a.id', 'desc');

        // Ambil semua data yang sesuai filter (termasuk HAVING), lalu paginate manual
        $allResults   = $query->get();
        $count_data   = $allResults->count();
        $total_paging = $count_data > 0 ? (int) ceil($count_data / $items_per_page) : 1;
        $offset       = ($page - 1) * $items_per_page;
        $data         = $allResults->slice($offset, $items_per_page)->values();

        return response()->json([
            'total_paging' => $total_paging,
            'data'         => $data,
        ], 200);
    }

    public function export(Request $request)
    {
        $filters = [
            'tanggal_dari'   => $request->tanggal_dari,
            'tanggal_sampai' => $request->tanggal_sampai,
            'shift_id'       => $request->shift_id,
            'status'         => $request->status,
            'keyword'        => $request->keyword,
        ];

        if ($request->user()->role !== 'admin') {
            $filters['user_id'] = $request->user()->id;
        } else {
            // admin: filter by branch_id from request if provided, otherwise no restriction
            $filters['branch_id'] = $request->branch_id ?: null;
        }

        $dari   = $filters['tanggal_dari'] ?? now()->format('Y-m-d');
        $sampai = $filters['tanggal_sampai'] ?? now()->format('Y-m-d');
        $filename = 'Laporan Absensi ' . $dari . ' sd ' . $sampai . '.xlsx';

        return Excel::download(new AbsensiExport($filters), $filename);
    }

    private function hitungJarak(float $lat1, float $lon1, float $lat2, float $lon2): int
    {
        $R = 6371000;
        $phi1 = deg2rad($lat1);
        $phi2 = deg2rad($lat2);
        $dphi = deg2rad($lat2 - $lat1);
        $dlambda = deg2rad($lon2 - $lon1);
        $a = sin($dphi / 2) ** 2 + cos($phi1) * cos($phi2) * sin($dlambda / 2) ** 2;
        return (int) round($R * 2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    private function simpanFoto(string $base64, string $folder): string
    {
        $image = str_replace(['data:image/jpeg;base64,', 'data:image/png;base64,', 'data:image/webp;base64,'], '', $base64);
        $image = base64_decode($image);

        $dir = public_path('uploads/' . $folder);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = time() . '_' . uniqid() . '.jpg';
        file_put_contents($dir . '/' . $filename, $image);

        return 'uploads/' . $folder . '/' . $filename;
    }
}
