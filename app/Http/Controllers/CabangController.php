<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use DB;
use Illuminate\Http\Request;
use Validator;

class CabangController extends Controller
{
  public function index(Request $request)
  {
    // if ($request->user()->role == 'dokter' || $request->user()->role == 'resepsionis') {
    //     return response()->json([
    //         'message' => 'The user role was invalid.',
    //         'errors' => ['Akses User tidak diizinkan!'],
    //     ], 403);
    // }

    $branch = DB::table('branches')
      ->join('users', 'branches.user_id', '=', 'users.id')
      ->select(
        'branches.id',
        'branch_code',
        'branch_name',
        'payment_instruction',
        'users.fullname as created_by',
        DB::raw("DATE_FORMAT(branches.created_at, '%d %b %Y') as created_at"),
        'branches.address'
      )
      ->where('branches.isDeleted', '=', 0);

    if ($request->keyword) {
      $branch = $branch->where('branch_code', 'like', '%' . $request->keyword . '%')
        ->orwhere('branches.branch_name', 'like', '%' . $request->keyword . '%')
        ->orwhere('branches.address', 'like', '%' . $request->keyword . '%')
        ->orwhere('branches.payment_instruction', 'like', '%' . $request->keyword . '%')
        ->orwhere('users.fullname', 'like', '%' . $request->keyword . '%');
    }

    if ($request->user()->role == 'dokter' || $request->user()->role == 'resepsionis') {
      $branch = $branch->where('branches.id', '=', $request->user()->branch_id);
    }

    if ($request->orderby) {

      $branch = $branch->orderBy($request->column, $request->orderby);
    }

    $branch = $branch->orderBy('id', 'desc');

    $branch = $branch->get();

    return response()->json($branch, 200);
  }

  public function create(Request $request)
  {

    if ($request->user()->role == 'dokter' || $request->user()->role == 'resepsionis') {
      return response()->json([
        'message' => 'The user role was invalid.',
        'errors' => ['Akses User tidak diizinkan!'],
      ], 403);
    }

    $validate = Validator::make($request->all(), [
      'KodeCabang' => 'required|string|max:5|unique:branches,branch_code',
      'NamaCabang' => 'required|string|max:50',
      'Alamat' => 'required|string|min:5',
      'InstruksiPembayaran' => 'required|string|min:5',
    ]);

    if ($validate->fails()) {
      $errors = $validate->errors()->all();

      return response()->json([
        'message' => 'The given data was invalid.',
        'errors' => $errors,
      ], 422);
    }

    Branch::create([
      'branch_code' => $request->KodeCabang,
      'branch_name' => $request->NamaCabang,
      'address' => $request->Alamat,
      'payment_instruction' => $request->InstruksiPembayaran,
      'user_id' => $request->user()->id,
    ]);

    return response()->json([
      'message' => 'Berhasil menambah Cabang',
    ], 200);
  }

  public function update(Request $request)
  {

    if ($request->user()->role == 'dokter' || $request->user()->role == 'resepsionis') {
      return response()->json([
        'message' => 'The user role was invalid.',
        'errors' => ['Akses User tidak diizinkan!'],
      ], 403);
    }

    $validate = Validator::make($request->all(), [
      //'KodeCabang' => 'required|string|max:5', //|unique:branches,branch_code
      'NamaCabang' => 'required|string|max:50',
      'Alamat' => 'required|string|min:5',
    ]);

    if ($validate->fails()) {
      $errors = $validate->errors()->all();

      return response()->json([
        'message' => 'The given data was invalid.',
        'errors' => $errors,
      ], 422);
    }

    $branch = Branch::find($request->id);

    if (is_null($branch)) {
      return response()->json([
        'message' => 'The data was invalid.',
        'errors' => ['Data tidak ditemukan!'],
      ], 404);
    }

    $branch->branch_name = $request->NamaCabang;
    $branch->address = $request->Alamat;
    $branch->payment_instruction = $request->InstruksiPembayaran;
    $branch->user_update_id = $request->user()->id;
    $branch->updated_at = \Carbon\Carbon::now();
    $branch->save();

    return response()->json([
      'message' => 'Berhasil mengupdate Cabang',
    ], 200);
  }

  public function delete(Request $request)
  {

    if ($request->user()->role == 'dokter' || $request->user()->role == 'resepsionis') {
      return response()->json([
        'message' => 'The user role was invalid.',
        'errors' => ['Akses User tidak diizinkan!'],
      ], 403);
    }

    $branch = Branch::find($request->id);

    if (is_null($branch)) {
      return response()->json([
        'message' => 'The data was invalid.',
        'errors' => ['Data tidak ditemukan!'],
      ], 404);
    }

    $branch->isDeleted = true;
    $branch->deleted_by = $request->user()->fullname;
    $branch->deleted_at = \Carbon\Carbon::now();
    $branch->save();

    //$branch->delete();

    return response()->json([
      'message' => 'Berhasil menghapus Cabang',
    ], 200);
  }

  public function listCabangAll()
  {
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

    return response()->json($branches, 200);
  }
}
