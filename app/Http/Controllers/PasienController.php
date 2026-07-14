<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Owner;
use App\Models\Patient;
use App\Models\PetCategory;
use DB;
use Illuminate\Http\Request;
use Validator;

class PasienController extends Controller
{
  public function index(Request $request)
  {

    $items_per_page = 50;

    $page = $request->page;

    $patient = DB::table('patients')
      ->join('branches', 'patients.branch_id', '=', 'branches.id')
      ->join('users', 'patients.user_id', '=', 'users.id')
      ->join('owners', 'patients.owner_id', '=', 'owners.id')
      ->leftJoin('pet_categories', 'patients.pet_category_id', '=', 'pet_categories.id')
      ->select(
        'patients.id',
        'patients.branch_id',
        'branches.branch_name',
        'patients.id_member',
        'patients.pet_category_id',
        'patients.other_pet_category',
        DB::raw('CASE WHEN patients.pet_category_id = 6 AND patients.other_pet_category IS NOT NULL AND patients.other_pet_category != "" THEN patients.other_pet_category ELSE COALESCE(pet_categories.name, patients.pet_category) END as pet_category'),
        'patients.pet_name',
        'patients.pet_gender',
        'patients.pet_year_age',
        'patients.pet_month_age',
        'patients.pet_day_age',
        DB::raw('(CASE WHEN patients.owner_name = "" THEN owners.owner_name ELSE patients.owner_name END) AS owner_name'),
        DB::raw('(CASE WHEN patients.owner_address = "" THEN owners.owner_address ELSE patients.owner_address END) AS owner_address'),
        DB::raw('(CASE WHEN patients.owner_phone_number = "" THEN owners.owner_phone_number ELSE patients.owner_phone_number END) AS owner_phone_number'),
        'users.fullname as created_by',
        DB::raw("DATE_FORMAT(patients.created_at, '%d %b %Y %H:%i:%s') as created_at"),
        'owners.id as owner_id',
        'owners.owner_name as owner_name_new',
        'owners.owner_address as owner_address_new',
        'owners.owner_phone_number as owner_phone_number_new'
      )
      ->where('patients.isDeleted', '=', 'false');

    if ($request->keyword) {
      $res = $this->Search($request);
      if ($res) {
        $patient = $patient->where($res, 'like', '%' . $request->keyword . '%');
      } else {
        $patient = [];
        return response()->json([
          'total_paging' => 0,
          'data' => $patient
        ], 200);
      }
    }

    if ($request->user()->role == 'dokter' || $request->user()->role == 'resepsionis') {
      $patient = $patient->where('patients.branch_id', '=', $request->user()->branch_id);
    }

    if ($request->branch_id && $request->user()->role == 'admin') {
      $patient = $patient->where('patients.branch_id', '=', $request->branch_id);
    }

    if ($request->orderby) {

      $patient = $patient->orderBy($request->column, $request->orderby);
    }

    $patient = $patient->orderBy('id', 'desc');

    $offset = ($page - 1) * $items_per_page;

    $count_data = $patient->count();
    $count_result = $count_data - $offset;

    if ($count_result < 0) {
      $patient = $patient->offset(0)->limit($items_per_page)->get();
    } else {
      $patient = $patient->offset($offset)->limit($items_per_page)->get();
    }

    $total_paging = $count_data / $items_per_page;

    return response()->json([
      'total_paging' => ceil($total_paging),
      'data' => $patient
    ], 200);

    // if ($request->keyword) {

    //     $res = $this->Search($request);

    //     $patient = DB::table('patients')
    //         ->join('branches', 'patients.branch_id', '=', 'branches.id')
    //         ->join('users', 'patients.user_id', '=', 'users.id')
    //         ->join('owners', 'patients.owner_id', '=', 'owners.id')
    //         ->select('patients.id',
    //             'patients.branch_id',
    //             'branches.branch_name',
    //             'patients.id_member',
    //             'patients.pet_category',
    //             'patients.pet_name',
    //             'patients.pet_gender',
    //             'patients.pet_year_age',
    //             'patients.pet_month_age',
    //             DB::raw('(CASE WHEN patients.owner_name = "" THEN owners.owner_name ELSE patients.owner_name END) AS owner_name'),
    //             DB::raw('(CASE WHEN patients.owner_address = "" THEN owners.owner_address ELSE patients.owner_address END) AS owner_address'),
    //             DB::raw('(CASE WHEN patients.owner_phone_number = "" THEN owners.owner_phone_number ELSE patients.owner_phone_number END) AS owner_phone_number'),
    //             'branches.branch_name',
    //             'users.fullname as created_by',
    //             DB::raw("DATE_FORMAT(patients.created_at, '%d %b %Y') as created_at"),
    //             'owners.id as owner_id',
    //             'owners.owner_name as owner_name_new',
    //             'owners.owner_address as owner_address_new',
    //             'owners.owner_phone_number as owner_phone_number_new')
    //         ->where('patients.isDeleted', '=', 'false');

    //     if ($res) {
    //         $patient = $patient->where($res, 'like', '%' . $request->keyword . '%');
    //     } else {
    //         $data = [];
    //         return response()->json($data, 200);
    //     }

    //     if ($request->user()->role == 'dokter' || $request->user()->role == 'resepsionis') {
    //         $patient = $patient->where('patients.branch_id', '=', $request->user()->branch_id);
    //     }

    //     if ($request->branch_id && $request->user()->role == 'admin') {
    //         $patient = $patient->where('patients.branch_id', '=', $request->branch_id);
    //     }

    //     if ($request->orderby) {

    //         $patient = $patient->orderBy($request->column, $request->orderby);
    //     }

    //     $patient = $patient->orderBy('id', 'desc');

    //     $patient = $patient->get();

    //     return response()->json($patient, 200);

    // } else {

    //     $patient = DB::table('patients')
    //         ->join('branches', 'patients.branch_id', '=', 'branches.id')
    //         ->join('users', 'patients.user_id', '=', 'users.id')
    //         ->join('owners', 'patients.owner_id', '=', 'owners.id')
    //         ->select(
    //             'patients.id',
    //             'patients.branch_id',
    //             'branches.branch_name',
    //             'patients.id_member',
    //             'patients.pet_category',
    //             'patients.pet_name',
    //             'patients.pet_gender',
    //             'patients.pet_year_age',
    //             'patients.pet_month_age',
    //             DB::raw('(CASE WHEN patients.owner_name = "" THEN owners.owner_name ELSE patients.owner_name END) AS owner_name'),
    //             DB::raw('(CASE WHEN patients.owner_address = "" THEN owners.owner_address ELSE patients.owner_address END) AS owner_address'),
    //             DB::raw('(CASE WHEN patients.owner_phone_number = "" THEN owners.owner_phone_number ELSE patients.owner_phone_number END) AS owner_phone_number'),
    //             'branches.branch_name',
    //             'users.fullname as created_by',
    //             'owners.id as owner_id',
    //             DB::raw("DATE_FORMAT(patients.created_at, '%d %b %Y') as created_at"))
    //         ->where('patients.isDeleted', '=', 'false');

    //     if ($request->user()->role == 'dokter' || $request->user()->role == 'resepsionis') {
    //         $patient = $patient->where('patients.branch_id', '=', $request->user()->branch_id);
    //     }

    //     if ($request->branch_id && $request->user()->role == 'admin') {
    //         $patient = $patient->where('patients.branch_id', '=', $request->branch_id);
    //     }

    //     if ($request->orderby) {

    //         $patient = $patient->orderBy($request->column, $request->orderby);
    //     }

    //     $patient = $patient->orderBy('id', 'desc');

    //     $patient = $patient->get();

    //     return response()->json($patient, 200);
    // }

  }

  private function Search($request)
  {
    $temp_column = '';
    $data = DB::table('patients')
      ->join('branches', 'patients.branch_id', '=', 'branches.id')
      ->join('users', 'patients.user_id', '=', 'users.id')
      ->select(
        'patients.id',
        'patients.branch_id',
        'branches.branch_name',
        'patients.id_member',
        'patients.pet_category',
        'patients.pet_name',
        'patients.pet_gender',
        'patients.pet_year_age',
        'patients.pet_month_age',
        'patients.owner_name',
        'patients.owner_address',
        'patients.owner_phone_number',
        'branches.branch_name',
        'users.fullname as created_by',
        DB::raw("DATE_FORMAT(patients.created_at, '%d %b %Y') as created_at")
      )
      ->where('patients.isDeleted', '=', 0);

    if ($request->user()->role == 'dokter' || $request->user()->role == 'resepsionis') {
      $data = $data->where('patients.branch_id', '=', $request->user()->branch_id);
    }

    if ($request->branch_id && $request->user()->role == 'admin') {
      $data = $data->where('patients.branch_id', '=', $request->branch_id);
    }

    if ($request->keyword) {
      $data = $data->where('patients.pet_category', 'like', '%' . $request->keyword . '%');
    }

    $data = $data->get();

    if (count($data)) {
      $temp_column = 'patients.pet_category';
      return $temp_column;
    }
    //=======================================================

    $data = DB::table('patients')
      ->join('branches', 'patients.branch_id', '=', 'branches.id')
      ->join('users', 'patients.user_id', '=', 'users.id')
      ->select(
        'patients.id',
        'patients.branch_id',
        'branches.branch_name',
        'patients.id_member',
        'patients.pet_category',
        'patients.pet_name',
        'patients.pet_gender',
        'patients.pet_year_age',
        'patients.pet_month_age',
        'patients.owner_name',
        'patients.owner_address',
        'patients.owner_phone_number',
        'branches.branch_name',
        'users.fullname as created_by',
        DB::raw("DATE_FORMAT(patients.created_at, '%d %b %Y') as created_at")
      )
      ->where('patients.isDeleted', '=', 0);

    if ($request->user()->role == 'dokter' || $request->user()->role == 'resepsionis') {
      $data = $data->where('patients.branch_id', '=', $request->user()->branch_id);
    }

    if ($request->branch_id && $request->user()->role == 'admin') {
      $data = $data->where('patients.branch_id', '=', $request->branch_id);
    }

    if ($request->keyword) {
      $data = $data->where('patients.pet_name', 'like', '%' . $request->keyword . '%');
    }

    $data = $data->get();

    if (count($data)) {
      $temp_column = 'patients.pet_name';
      return $temp_column;
    }
    //=======================================================

    $data = DB::table('patients')
      ->join('branches', 'patients.branch_id', '=', 'branches.id')
      ->join('users', 'patients.user_id', '=', 'users.id')
      ->select(
        'patients.id',
        'patients.branch_id',
        'branches.branch_name',
        'patients.id_member',
        'patients.pet_category',
        'patients.pet_name',
        'patients.pet_gender',
        'patients.pet_year_age',
        'patients.pet_month_age',
        'patients.owner_name',
        'patients.owner_address',
        'patients.owner_phone_number',
        'branches.branch_name',
        'users.fullname as created_by',
        DB::raw("DATE_FORMAT(patients.created_at, '%d %b %Y') as created_at")
      )
      ->where('patients.isDeleted', '=', 0);

    if ($request->user()->role == 'dokter' || $request->user()->role == 'resepsionis') {
      $data = $data->where('patients.branch_id', '=', $request->user()->branch_id);
    }

    if ($request->branch_id && $request->user()->role == 'admin') {
      $data = $data->where('patients.branch_id', '=', $request->branch_id);
    }

    if ($request->keyword) {
      $data = $data->where('patients.owner_name', 'like', '%' . $request->keyword . '%');
    }

    $data = $data->get();

    if (count($data)) {
      $temp_column = 'patients.owner_name';
      return $temp_column;
    }
    //=======================================================

    $data = DB::table('patients')
      ->join('branches', 'patients.branch_id', '=', 'branches.id')
      ->join('users', 'patients.user_id', '=', 'users.id')
      ->select(
        'patients.id',
        'patients.branch_id',
        'branches.branch_name',
        'patients.id_member',
        'patients.pet_category',
        'patients.pet_name',
        'patients.pet_gender',
        'patients.pet_year_age',
        'patients.pet_month_age',
        'patients.owner_name',
        'patients.owner_address',
        'patients.owner_phone_number',
        'branches.branch_name',
        'users.fullname as created_by',
        DB::raw("DATE_FORMAT(patients.created_at, '%d %b %Y') as created_at")
      )
      ->where('patients.isDeleted', '=', 0);

    if ($request->user()->role == 'dokter' || $request->user()->role == 'resepsionis') {
      $data = $data->where('patients.branch_id', '=', $request->user()->branch_id);
    }

    if ($request->branch_id && $request->user()->role == 'admin') {
      $data = $data->where('patients.branch_id', '=', $request->branch_id);
    }

    if ($request->keyword) {
      $data = $data->where('patients.pet_gender', 'like', '%' . $request->keyword . '%');
    }

    $data = $data->get();

    if (count($data)) {
      $temp_column = 'patients.pet_gender';
      return $temp_column;
    }
  }

  public function create(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'pet_category_id'    => 'required|integer|exists:pet_categories,id',
      'other_pet_category' => 'required_if:pet_category_id,6|nullable|string|max:50',
      'nama_hewan'         => 'required|min:3|max:50',
      'jenis_kelamin_hewan' => 'required|string|max:50',
      'usia_tahun_hewan'   => 'required|numeric|min:0',
      'usia_bulan_hewan'   => 'required|numeric|min:0|max:12',
      'usia_hari_hewan'    => 'required|numeric|min:0|max:30',
    ]);

    if ($validator->fails()) {
      $errors = $validator->errors()->all();

      return response()->json([
        'message' => 'Pasien yang dimasukkan tidak valid!',
        'errors' => $errors,
      ], 422);
    }

    $temp_branch = 0;

    if ($request->user()->role == 'admin') {

      $branch = Branch::find($request->id_cabang);

      if (is_null($branch)) {

        return response()->json([
          'message' => 'Cabang yang dimasukkan tidak valid!',
          'errors' => ['Data tidak ditemukan!'],
        ], 422);
      }

      $temp_branch = $request->id_cabang;
    } else {
      $temp_branch = $request->user()->branch_id;
    }

    $owner_id = 0;

    if ($request->id_pemilik) {
      $check_owner = DB::table('owners')
        ->where('id', '=', $request->id_pemilik)
        ->count();

      if (!$check_owner) {
        return response()->json([
          'message' => 'Data owner tidak ditemukan!',
          'errors' => ['Data tidak ditemukan!'],
        ], 422);
      }

      $owner_id = $request->id_pemilik;
    } else {

      $validator_owner = Validator::make($request->all(), [
        'nama_pemilik' => 'required|string|max:50',
        'alamat_pemilik' => 'required|string|max:100',
        'nomor_ponsel_pengirim' => 'required|numeric|digits_between:10,13',
      ]);

      if ($validator_owner->fails()) {
        $errors_validator = $validator_owner->errors()->all();

        return response()->json([
          'message' => 'Data pasien yang dimasukkan tidak valid!',
          'errors' => $errors_validator,
        ], 422);
      }

      $check_owner = DB::table('owners')
        ->where('owner_name', '=', $request->nama_pemilik)
        ->where('branch_id', '=', $temp_branch)
        ->count();

      if ($check_owner) {
        return response()->json([
          'message' => 'Nama pemiliki sudah ada!',
          'errors' => ['Nama pemiliki sudah ada!'],
        ], 422);
      }

      $owner = Owner::create([
        'branch_id' => $temp_branch,
        'owner_name' => $request->nama_pemilik,
        'owner_address' => $request->alamat_pemilik,
        'owner_phone_number' => strval($request->nomor_ponsel_pengirim),
      ]);

      $owner_id = $owner->id;
    }

    $lastpatient = DB::table('patients')
      ->where('branch_id', '=', $request->user()->branch_id)
      ->count();

    $getbranchuser = DB::table('branches')
      ->select('branch_code')
      ->where('id', '=', $request->user()->branch_id)
      ->first();

    $patient_number = 'BVC-P-' . $getbranchuser->branch_code . '-' . str_pad($lastpatient + 1, 4, 0, STR_PAD_LEFT);

    $petCategory = PetCategory::find($request->pet_category_id);

    $patient = Patient::create([
      'id_member'          => $patient_number,
      'pet_category'       => $petCategory->name,
      'pet_category_id'    => $request->pet_category_id,
      'other_pet_category' => $request->pet_category_id == 6 ? $request->other_pet_category : null,
      'pet_name' => $request->nama_hewan,
      'pet_gender' => $request->jenis_kelamin_hewan,
      'pet_year_age' => $request->usia_tahun_hewan,
      'pet_month_age' => $request->usia_bulan_hewan,
      'pet_day_age' => $request->usia_hari_hewan,
      'pet_birth_date' => \Carbon\Carbon::today()
          ->subYears((int) $request->usia_tahun_hewan)
          ->subMonths((int) $request->usia_bulan_hewan)
          ->subDays((int) $request->usia_hari_hewan)
          ->toDateString(),
      'owner_name' => '',
      'owner_address' => '',
      'owner_phone_number' => '',
      'branch_id' => $temp_branch,
      'owner_id' => $owner_id,
      'user_id' => $request->user()->id,
    ]);

    return response()->json(
      [
        'message' => 'Tambah Pasien Berhasil!',
      ],
      200
    );
  }

  public function update(Request $request)
  {
    if ($request->user()->role == 'resepsionis') {
      return response()->json([
        'message' => 'The user role was invalid.',
        'errors' => ['Akses User tidak diizinkan!'],
      ], 403);
    }

    $validator = Validator::make($request->all(), [
      'pet_category_id'    => 'required|integer|exists:pet_categories,id',
      'other_pet_category' => 'required_if:pet_category_id,6|nullable|string|max:50',
      'nama_hewan'         => 'required|min:3|max:50',
      'jenis_kelamin_hewan' => 'required|string|max:50',
      'usia_tahun_hewan'   => 'required|numeric|min:0',
      'usia_bulan_hewan'   => 'required|numeric|min:0|max:12',
      'usia_hari_hewan'    => 'required|numeric|min:0|max:30',
    ]);

    if ($validator->fails()) {
      $errors = $validator->errors()->all();

      return response()->json([
        'message' => 'Pasien yang dimasukkan tidak valid!',
        'errors' => $errors,
      ], 422);
    }

    $patient = Patient::find($request->id);

    if (is_null($patient)) {
      return response()->json([
        'message' => 'The data was invalid.',
        'errors' => ['Data tidak ditemukan!'],
      ], 404);
    }

    $temp_branch = 0;
    $temp_id_member = "";

    if ($request->user()->role == 'admin') {

      $branch = Branch::find($request->id_cabang);

      if (is_null($branch)) {

        return response()->json([
          'message' => 'Cabang yang dimasukkan tidak valid!',
          'errors' => ['Data tidak ditemukan!'],
        ], 422);
      }

      $lastpatient = DB::table('patients')
        ->where('branch_id', '=', $request->id_cabang)
        ->count();

      $getbranchuser = DB::table('branches')
        ->select('branch_code')
        ->where('id', '=', $request->id_cabang)
        ->first();

      $patient_number = 'BVC-P-' . $getbranchuser->branch_code . '-' . str_pad($lastpatient + 1, 4, 0, STR_PAD_LEFT);

      $temp_id_member = $patient_number;
      $temp_branch = $request->id_cabang;
    } else {
      $temp_branch = $request->user()->branch_id;
      $temp_id_member = $request->id_member;
    }

    $owner_id = 0;

    if ($request->id_pemilik != 0 || is_null($request->id_pemilik)) {

      $check_owner = DB::table('owners')
        ->where('id', '=', $request->id_pemilik)
        ->count();

      if (!$check_owner) {
        return response()->json([
          'message' => 'Data owner tidak ditemukan!',
          'errors' => ['Data tidak ditemukan!'],
        ], 422);
      }

      $owner_id = $request->id_pemilik;
    } else {
      $validator_owner = Validator::make($request->all(), [
        'nama_pemilik' => 'required|string|max:50',
        'alamat_pemilik' => 'required|string|max:100',
        'nomor_ponsel_pengirim' => 'required|numeric|digits_between:10,13',
      ]);

      if ($validator_owner->fails()) {
        $errors_validator = $validator_owner->errors()->all();

        return response()->json([
          'message' => 'Data pasien yang dimasukkan tidak valid!',
          'errors' => $errors_validator,
        ], 422);
      }

      $check_owner = DB::table('owners')
        ->where('owner_name', '=', $request->nama_pemilik)
        ->where('branch_id', '=', $temp_branch)
        ->count();

      if ($check_owner) {
        return response()->json([
          'message' => 'Nama pemiliki sudah ada!',
          'errors' => ['Nama pemiliki sudah ada!'],
        ], 422);
      }

      $owner = Owner::create([
        'branch_id' => $temp_branch,
        'owner_name' => $request->nama_pemilik,
        'owner_address' => $request->alamat_pemilik,
        'owner_phone_number' => strval($request->nomor_ponsel_pengirim),
      ]);

      $owner_id = $owner->id;
    }

    $petCategory = PetCategory::find($request->pet_category_id);

    $patient->id_member          = $temp_id_member;
    $patient->pet_category       = $petCategory->name;
    $patient->pet_category_id    = $request->pet_category_id;
    $patient->other_pet_category = $request->pet_category_id == 6 ? $request->other_pet_category : null;
    $patient->pet_name = $request->nama_hewan;
    $patient->pet_gender = $request->jenis_kelamin_hewan;
    $patient->pet_year_age  = $request->usia_tahun_hewan;
    $patient->pet_month_age = $request->usia_bulan_hewan;
    $patient->pet_day_age   = $request->usia_hari_hewan;
    $patient->pet_birth_date = \Carbon\Carbon::today()
        ->subYears((int) $request->usia_tahun_hewan)
        ->subMonths((int) $request->usia_bulan_hewan)
        ->subDays((int) $request->usia_hari_hewan)
        ->toDateString();
    // $patient->owner_name = $request->nama_pemilik;
    // $patient->owner_address = $request->alamat_pemilik;
    // $patient->owner_phone_number = $request->nomor_ponsel_pengirim;
    $patient->owner_id = $owner_id;
    $patient->user_update_id = $request->user()->id;
    $patient->branch_id = $temp_branch;
    $patient->updated_at = \Carbon\Carbon::now();
    $patient->save();

    return response()->json([
      'message' => 'Berhasil mengupdate Pasien',
    ], 200);
  }

  public function delete(Request $request)
  {
    if ($request->user()->role == 'resepsionis') {
      return response()->json([
        'message' => 'The user role was invalid.',
        'errors' => ['Akses User tidak diizinkan!'],
      ], 403);
    }

    $patient = Patient::find($request->id);

    if (is_null($patient)) {
      return response()->json([
        'message' => 'The data was invalid.',
        'errors' => ['Data tidak ditemukan!'],
      ], 404);
    }

    $patient->isDeleted = true;
    $patient->deleted_by = $request->user()->fullname;
    $patient->deleted_at = \Carbon\Carbon::now();
    $patient->save();

    //$patient->delete();

    return response()->json([
      'message' => 'Berhasil menghapus Pasien',
    ], 200);
  }

  public function patient_accept_only(Request $request)
  {
    if ($request->user()->role == 'resepsionis') {
      return response()->json([
        'message' => 'The user role was invalid.',
        'errors' => ['Akses User tidak diizinkan!'],
      ], 403);
    }

    $data = DB::table('registrations')
      ->join('users', 'registrations.user_id', '=', 'users.id')
      ->join('users as user_doctor', 'registrations.doctor_user_id', '=', 'user_doctor.id')
      ->join('patients', 'registrations.patient_id', '=', 'patients.id')
      ->join('owners', 'patients.owner_id', '=', 'owners.id')
      ->join('branches', 'patients.branch_id', '=', 'branches.id')
      ->select(
        'registrations.id as registration_id',
        'registrations.id_number as registration_number',
        'registrations.patient_id',
        'patients.id_member as id_number_patient',
        'patients.pet_category',
        'patients.pet_name',
        'patients.pet_gender',
        'patients.pet_year_age',
        'patients.pet_month_age',
        'patients.pet_day_age',
        DB::raw('(CASE WHEN patients.owner_name = "" THEN owners.owner_name ELSE patients.owner_name END) AS owner_name'),
        DB::raw('(CASE WHEN patients.owner_address = "" THEN owners.owner_address ELSE patients.owner_address END) AS owner_address'),
        DB::raw('(CASE WHEN patients.owner_phone_number = "" THEN owners.owner_phone_number ELSE patients.owner_phone_number END) AS owner_phone_number'),
        'registrations.complaint',
        'registrations.registrant',
        'user_doctor.id as user_doctor_id',
        'user_doctor.username as username_doctor',
        'users.fullname as created_by',
        'registrations.acceptance_status',
        DB::raw("DATE_FORMAT(registrations.created_at, '%d %b %Y') as created_at"),
        'users.branch_id as user_branch_id',
        'branches.id as branch_id',
        'branches.branch_name as branch_name'
      )
      ->where('registrations.acceptance_status', '=', '1')
      ->where('registrations.is_hide_from_drop_down', '=', 0);

    if ($request->user()->role == 'dokter') {
      $data = $data
        // ->where('users.branch_id', '=', $request->user()->branch_id)
        ->where('registrations.doctor_user_id', '=', $request->user()->id);
    }

    $data_check = DB::table('check_up_results')
      ->select('check_up_results.patient_registration_id')
      ->where('isDeleted', '=', 0)
      ->get();

    $res = "";

    foreach ($data_check as $dat) {
      $res = $res . (string) $dat->patient_registration_id . ",";
    }

    $res = rtrim($res, ", ");

    $myArray = explode(',', $res);

    $data = $data->whereNotIn('registrations.id', $myArray);

    $data = $data->orderBy('registrations.id', 'desc');

    $data = $data->get();

    return response()->json($data, 200);
  }

  public function HistoryPatient(Request $request)
  {
    if ($request->user()->role == 'resepsionis') {
      return response()->json([
        'message' => 'The user role was invalid.',
        'errors' => ['Akses User tidak diizinkan!'],
      ], 403);
    }

    $data = DB::table('registrations')
      ->join('users', 'registrations.user_id', '=', 'users.id')
      ->join('users as user_doctor', 'registrations.doctor_user_id', '=', 'user_doctor.id')
      ->join('patients', 'registrations.patient_id', '=', 'patients.id')
      ->join('branches', 'patients.branch_id', '=', 'branches.id')
      ->select(
        'registrations.id as registration_id',
        'registrations.id_number as registration_number',
        'registrations.patient_id',
        'patients.id_member as id_number_patient',
        'patients.pet_category',
        'patients.pet_name',
        'patients.pet_gender',
        'patients.pet_year_age',
        'patients.pet_month_age',
        'patients.owner_name',
        'patients.owner_address',
        'patients.owner_phone_number',
        'complaint',
        'registrant',
        'user_doctor.id as user_doctor_id',
        'user_doctor.username as username_doctor',
        'users.fullname as created_by',
        'registrations.acceptance_status',
        DB::raw("DATE_FORMAT(registrations.created_at, '%d %b %Y') as created_at"),
        'users.branch_id as user_branch_id',
        'branches.id as branch_id',
        'branches.branch_name as branch_name'
      )
      ->where('registrations.acceptance_status', '=', '3')
      ->where('patients.id', '=', $request->patient_id);

    if ($request->user()->role == 'dokter') {
      $data = $data->where('users.branch_id', '=', $request->user()->branch_id)
        ->where('registrations.doctor_user_id', '=', $request->user()->id);
    }

    $data = $data->orderBy('registrations.id', 'desc');

    $data = $data->get();

    return response()->json($data, 200);
  }

  public function DetailHistoryPatient(Request $request)
  {
    if ($request->user()->role == 'resepsionis') {
      return response()->json([
        'message' => 'The user role was invalid.',
        'errors' => ['Akses User tidak diizinkan!'],
      ], 403);
    }

    $data = DB::table('check_up_results')
      ->join('registrations', 'check_up_results.patient_registration_id', '=', 'registrations.id')
      ->join('users', 'check_up_results.user_id', '=', 'users.id')
      ->select(
        'check_up_results.id as check_up_result_id',
        'registrations.id_number as registration_number',
        'check_up_results.anamnesa',
        'check_up_results.sign',
        'check_up_results.diagnosa',
        'check_up_results.status_outpatient_inpatient',
        'check_up_results.status_finish',
        'check_up_results.status_paid_off',
        DB::raw("DATE_FORMAT(check_up_results.created_at, '%d %b %Y') as created_at"),
        'users.fullname as created_by'
      )
      ->where('patient_registration_id', '=', $request->patient_registration_id)
      ->first();

    if (is_null($data)) {

      return response()->json([
        'message' => 'The data was invalid.',
        'errors' => ['Data Hasil Pemeriksaan tidak ditemukan!'],
      ], 404);
    }

    $services = DB::table('detail_service_patients')
      ->join('price_services', 'detail_service_patients.price_service_id', '=', 'price_services.id')
      ->join('list_of_services', 'price_services.list_of_services_id', '=', 'list_of_services.id')
      ->join('service_categories', 'list_of_services.service_category_id', '=', 'service_categories.id')
      ->join('users', 'detail_service_patients.user_id', '=', 'users.id')
      ->select(
        'detail_service_patients.id as detail_service_patient_id',
        'price_services.id as price_service_id',
        'list_of_services.id as list_of_service_id',
        'list_of_services.service_name',
        'detail_service_patients.quantity',
        DB::raw("TRIM(detail_service_patients.price_overall)+0 as price_overall"),
        'service_categories.category_name',
        DB::raw("TRIM(price_services.selling_price)+0 as selling_price"),
        'users.fullname as created_by',
        DB::raw("DATE_FORMAT(detail_service_patients.created_at, '%d %b %Y') as created_at")
      )
      ->where('detail_service_patients.check_up_result_id', '=', $data->check_up_result_id)
      ->orderBy('detail_service_patients.id', 'desc')
      ->get();

    $data->services = $services;

    // $item = DB::table('detail_item_patients')
    //     ->join('price_medicine_groups', 'detail_item_patients.medicine_group_id', '=', 'price_medicine_groups.id')
    //     ->join('medicine_groups', 'price_medicine_groups.medicine_group_id', '=', 'medicine_groups.id')
    //     ->join('branches', 'medicine_groups.branch_id', '=', 'branches.id')
    //     ->select('price_medicine_groups.id as price_medicine_group_id', DB::raw("TRIM(price_medicine_groups.selling_price)+0 as selling_price"), 'detail_item_patients.medicine_group_id as medicine_group_id',
    //         'medicine_groups.group_name', 'branches.id as branch_id', 'branches.branch_name')
    //     ->where('detail_item_patients.check_up_result_id', '=', $data->check_up_result_id)
    //     ->groupBy('price_medicine_groups.id', 'price_medicine_groups.selling_price', 'detail_item_patients.medicine_group_id', 'medicine_groups.group_name', 'branches.id', 'branches.branch_name')
    //     ->get();

    // foreach ($item as $value) {

    //     $detail_item = DB::table('detail_item_patients')
    //         ->join('price_items', 'detail_item_patients.price_item_id', '=', 'price_items.id')
    //         ->join('list_of_items', 'price_items.list_of_items_id', '=', 'list_of_items.id')
    //         ->join('category_item', 'list_of_items.category_item_id', '=', 'category_item.id')
    //         ->join('unit_item', 'list_of_items.unit_item_id', '=', 'unit_item.id')
    //         ->join('users', 'detail_item_patients.user_id', '=', 'users.id')
    //         ->select('detail_item_patients.id as detail_item_patients_id', 'list_of_items.id as list_of_item_id', 'price_items.id as price_item_id', 'list_of_items.item_name', 'detail_item_patients.quantity',
    //             DB::raw("TRIM(detail_item_patients.price_overall)+0 as price_overall"), 'unit_item.unit_name',
    //             'category_item.category_name', DB::raw("TRIM(price_items.selling_price)+0 as selling_price"),
    //             'users.fullname as created_by', DB::raw("DATE_FORMAT(detail_item_patients.created_at, '%d %b %Y') as created_at"))
    //         ->where('detail_item_patients.check_up_result_id', '=', $data->check_up_result_id)
    //         ->where('detail_item_patients.medicine_group_id', '=', $value->medicine_group_id)
    //         ->orderBy('detail_item_patients.id', 'desc')
    //         ->get();

    //     $value->list_of_medicine = $detail_item;
    // }

    // $data->item = $item;

    $item = DB::table('detail_medicine_group_check_up_results')
      ->join('price_medicine_groups', 'detail_medicine_group_check_up_results.medicine_group_id', '=', 'price_medicine_groups.id')
      ->join('medicine_groups', 'price_medicine_groups.medicine_group_id', '=', 'medicine_groups.id')
      ->join('branches', 'medicine_groups.branch_id', '=', 'branches.id')
      ->select(
        'detail_medicine_group_check_up_results.id as id',
        'price_medicine_groups.id as price_medicine_group_id',
        DB::raw("TRIM(price_medicine_groups.selling_price)+0 as selling_price"),
        'detail_medicine_group_check_up_results.medicine_group_id as medicine_group_id',
        'medicine_groups.group_name',
        'branches.id as branch_id',
        'branches.branch_name'
      )
      ->where('detail_medicine_group_check_up_results.check_up_result_id', '=', $data->check_up_result_id)
      ->get();

    foreach ($item as $value) {

      $detail_item = DB::table('detail_item_patients')
        ->join('price_items', 'detail_item_patients.price_item_id', '=', 'price_items.id')
        ->join('list_of_items', 'price_items.list_of_items_id', '=', 'list_of_items.id')
        ->join('category_item', 'list_of_items.category_item_id', '=', 'category_item.id')
        ->join('unit_item', 'list_of_items.unit_item_id', '=', 'unit_item.id')
        ->join('users', 'detail_item_patients.user_id', '=', 'users.id')
        ->select(
          'detail_item_patients.id as detail_item_patients_id',
          'list_of_items.id as list_of_item_id',
          'price_items.id as price_item_id',
          'list_of_items.item_name',
          'detail_item_patients.quantity',
          DB::raw("TRIM(detail_item_patients.price_overall)+0 as price_overall"),
          'unit_item.unit_name',
          'category_item.category_name',
          DB::raw("TRIM(price_items.selling_price)+0 as selling_price"),
          'users.fullname as created_by',
          DB::raw("DATE_FORMAT(detail_item_patients.created_at, '%d %b %Y') as created_at")
        )
        //->where('detail_item_patients.check_up_result_id', '=', $data->id)
        ->where('detail_item_patients.detail_medicine_group_id', '=', $value->id)
        ->orderBy('detail_item_patients.id', 'asc')
        ->get();

      $value->list_of_medicine = $detail_item;
    }

    $data->item = $item;

    $inpatient = DB::table('in_patients')
      ->join('users', 'in_patients.user_id', '=', 'users.id')
      ->select(
        'in_patients.id as in_patient_id',
        'in_patients.check_up_result_id',
        'in_patients.description',
        'users.fullname as created_by',
        DB::raw("DATE_FORMAT(in_patients.created_at, '%d %b %Y') as created_at")
      )
      ->where('check_up_result_id', '=', $data->check_up_result_id)
      ->orderBy('in_patients.id', 'desc')
      ->get();

    $data->inpatient = $inpatient;

    return response()->json($data, 200);
  }

  public function petCategories()
  {
    $categories = PetCategory::orderBy('id')->get(['id', 'name']);
    return response()->json($categories, 200);
  }

  public function ListOwner(Request $request)
  {
    $owner = DB::table('owners')
      ->select('id', 'owner_name', 'owner_address', 'owner_phone_number', 'branch_id')
      ->where('branch_id', '=', $request->branch_id)
      ->get();

    return response()->json($owner, 200);
  }

  public function dropdown(Request $request)
  {
    $patient = DB::table('patients')
      ->join('branches', 'patients.branch_id', '=', 'branches.id')
      ->join('users', 'patients.user_id', '=', 'users.id')
      ->join('owners', 'patients.owner_id', '=', 'owners.id')
      ->leftJoin('pet_categories', 'patients.pet_category_id', '=', 'pet_categories.id')
      ->select(
        'patients.id',
        'patients.branch_id',
        'branches.branch_name',
        'patients.id_member',
        'patients.pet_category_id',
        'patients.other_pet_category',
        DB::raw('CASE WHEN patients.pet_category_id = 6 AND patients.other_pet_category IS NOT NULL AND patients.other_pet_category != "" THEN patients.other_pet_category ELSE COALESCE(pet_categories.name, patients.pet_category) END as pet_category'),
        'patients.pet_name',
        'patients.pet_gender',
        'patients.pet_year_age',
        'patients.pet_month_age',
        'patients.pet_day_age',
        DB::raw('(CASE WHEN patients.owner_name = "" THEN owners.owner_name ELSE patients.owner_name END) AS owner_name'),
        DB::raw('(CASE WHEN patients.owner_address = "" THEN owners.owner_address ELSE patients.owner_address END) AS owner_address'),
        DB::raw('(CASE WHEN patients.owner_phone_number = "" THEN owners.owner_phone_number ELSE patients.owner_phone_number END) AS owner_phone_number'),
        'users.fullname as created_by',
        DB::raw("DATE_FORMAT(patients.created_at, '%d %b %Y') as created_at"),
        'owners.id as owner_id',
        'owners.owner_name as owner_name_new',
        'owners.owner_address as owner_address_new',
        'owners.owner_phone_number as owner_phone_number_new'
      )
      ->where('patients.isDeleted', '=', 'false');

    if ($request->user()->role == 'dokter' || $request->user()->role == 'resepsionis') {
      $patient = $patient->where('patients.branch_id', '=', $request->user()->branch_id);
    }

    if ($request->branch_id && $request->user()->role == 'admin') {
      $patient = $patient->where('patients.branch_id', '=', $request->branch_id);
    }

    if ($request->orderby) {

      $patient = $patient->orderBy($request->column, $request->orderby);
    }

    $patient = $patient->orderBy('id', 'desc');

    $patient = $patient->get();

    return response()->json($patient, 200);
  }
}
