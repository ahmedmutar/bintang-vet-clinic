<?php

namespace App\Http\Controllers;

use App\Models\AbsensiRadiusException;
use Illuminate\Http\Request;
use Validator;

class AbsensiRadiusExceptionController extends Controller
{
    public function index(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Akses tidak diijinkan!'], 403);
        }

        return response()->json(
            AbsensiRadiusException::orderBy('username')->get(['id', 'username', 'created_by', 'created_at']),
            200
        );
    }

    public function create(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Akses tidak diijinkan!'], 403);
        }

        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Data tidak valid!', 'errors' => $validator->errors()->all()], 422);
        }

        $username = trim($request->username);

        // Cek apakah user dengan username ini ada
        $userExists = \DB::table('users')->where('username', $username)->exists();
        if (!$userExists) {
            return response()->json(['message' => 'Username tidak ditemukan!', 'errors' => ['Username "' . $username . '" tidak ditemukan di data user.']], 422);
        }

        // Cek duplikat
        if (AbsensiRadiusException::where('username', $username)->exists()) {
            return response()->json(['message' => 'Username sudah ada dalam daftar pengecualian!', 'errors' => ['Username "' . $username . '" sudah terdaftar.']], 422);
        }

        AbsensiRadiusException::create([
            'username'   => $username,
            'created_by' => $request->user()->fullname,
        ]);

        return response()->json(['message' => 'Username berhasil ditambahkan ke daftar pengecualian.'], 200);
    }

    public function delete(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Akses tidak diijinkan!'], 403);
        }

        $exception = AbsensiRadiusException::find($request->id);

        if (!$exception) {
            return response()->json(['message' => 'Data tidak ditemukan!'], 404);
        }

        $exception->delete();

        return response()->json(['message' => 'Username berhasil dihapus dari daftar pengecualian.'], 200);
    }
}
