<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use DB;
use Illuminate\Http\Request;
use Validator;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('shifts')
            ->join('branches', 'shifts.branch_id', '=', 'branches.id')
            ->select(
                'shifts.id',
                'shifts.branch_id',
                'branches.branch_name',
                'shifts.nama_shift',
                DB::raw("TIME_FORMAT(shifts.jam_masuk, '%H:%i') as jam_masuk"),
                DB::raw("TIME_FORMAT(shifts.jam_keluar, '%H:%i') as jam_keluar"),
                'shifts.toleransi_menit',
                'shifts.status',
                'shifts.for_role',
                'shifts.created_by',
                DB::raw("DATE_FORMAT(shifts.created_at, '%d %b %Y') as created_at")
            );

        if ($request->branch_id) {
            $query->where('shifts.branch_id', '=', $request->branch_id);
        }

        $query->orderBy('shifts.id', 'desc');

        return response()->json($query->get(), 200);
    }

    public function dropdown(Request $request)
    {
        $userRole = $request->user()->role;

        $query = DB::table('shifts')
            ->select('shifts.id', 'shifts.nama_shift', 'shifts.jam_masuk', 'shifts.jam_keluar', 'shifts.toleransi_menit')
            ->where('shifts.status', '=', 1)
            ->where('shifts.branch_id', '=', $request->user()->branch_id)
            ->where(function ($q) use ($userRole) {
                $q->whereNull('shifts.for_role')->orWhere('shifts.for_role', '=', $userRole);
            })
            ->orderBy('shifts.jam_masuk', 'asc');

        return response()->json($query->get(), 200);
    }

    public function create(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Akses tidak diijinkan!', 'errors' => ['Akses tidak diijinkan!']], 403);
        }

        $validator = Validator::make($request->all(), [
            'nama_shift'      => 'required|string|max:100',
            'jam_masuk'       => 'required',
            'jam_keluar'      => 'required',
            'toleransi_menit' => 'required|integer|min:0',
            'id_cabang'       => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Data tidak valid!', 'errors' => $validator->errors()->all()], 422);
        }

        Shift::create([
            'branch_id'       => $request->id_cabang,
            'nama_shift'      => $request->nama_shift,
            'jam_masuk'       => $request->jam_masuk,
            'jam_keluar'      => $request->jam_keluar,
            'toleransi_menit' => $request->toleransi_menit,
            'status'          => 1,
            'for_role'        => $request->for_role ?: null,
            'created_by'      => $request->user()->fullname,
        ]);

        return response()->json(['message' => 'Shift berhasil ditambahkan!'], 200);
    }

    public function update(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Akses tidak diijinkan!', 'errors' => ['Akses tidak diijinkan!']], 403);
        }

        $validator = Validator::make($request->all(), [
            'id'              => 'required|integer',
            'nama_shift'      => 'required|string|max:100',
            'jam_masuk'       => 'required',
            'jam_keluar'      => 'required',
            'toleransi_menit' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Data tidak valid!', 'errors' => $validator->errors()->all()], 422);
        }

        $shift = Shift::find($request->id);

        if (!$shift) {
            return response()->json(['message' => 'Data tidak ditemukan!', 'errors' => ['Data tidak ditemukan!']], 404);
        }

        $shift->nama_shift      = $request->nama_shift;
        $shift->jam_masuk       = $request->jam_masuk;
        $shift->jam_keluar      = $request->jam_keluar;
        $shift->toleransi_menit = $request->toleransi_menit;
        $shift->for_role        = $request->for_role ?: null;
        $shift->updated_by      = $request->user()->fullname;
        $shift->save();

        return response()->json(['message' => 'Shift berhasil diupdate!'], 200);
    }

    public function toggleStatus(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Akses tidak diijinkan!', 'errors' => ['Akses tidak diijinkan!']], 403);
        }

        $shift = Shift::find($request->id);

        if (!$shift) {
            return response()->json(['message' => 'Data tidak ditemukan!', 'errors' => ['Data tidak ditemukan!']], 404);
        }

        $shift->status     = $shift->status == 1 ? 0 : 1;
        $shift->updated_by = $request->user()->fullname;
        $shift->save();

        return response()->json(['message' => 'Status shift berhasil diubah!'], 200);
    }

    public function delete(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Akses tidak diijinkan!', 'errors' => ['Akses tidak diijinkan!']], 403);
        }

        $shift = Shift::find($request->id);

        if (!$shift) {
            return response()->json(['message' => 'Data tidak ditemukan!', 'errors' => ['Data tidak ditemukan!']], 404);
        }

        $shift->delete();

        return response()->json(['message' => 'Shift berhasil dihapus!'], 200);
    }
}
