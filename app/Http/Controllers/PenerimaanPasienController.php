<?php

namespace App\Http\Controllers;

namespace App\Http\Controllers;

use App\Models\DoctorAcceptance;
use App\Models\Registration;
use DB;
use Illuminate\Http\Request;
use Validator;

class PenerimaanPasienController extends Controller
{
    public function index(Request $request)
    {
        if ($request->user()->role == 'resepsionis') {
            return response()->json([
                'message' => 'The user role was invalid.',
                'errors' => ['Akses User tidak diizinkan!'],
            ], 403);
        }

        $items_per_page = 50;

        $page = $request->page;

        $data = DB::table('registrations')
            ->join('users', 'registrations.user_id', '=', 'users.id')
            ->join('users as user_doctor', 'registrations.doctor_user_id', '=', 'user_doctor.id')
            ->join('patients', 'registrations.patient_id', '=', 'patients.id')
            ->join('owners', 'patients.owner_id', '=', 'owners.id')
            ->join('branches', 'patients.branch_id', '=', 'branches.id')
            ->leftJoin('complaints', 'registrations.complaint_id', '=', 'complaints.id')
            ->select(
                'registrations.id as id',
                'registrations.id_number',
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
                'registrations.complaint_id',
                'registrations.other_complaint',
                DB::raw('CASE WHEN registrations.complaint_id = 11 AND registrations.other_complaint IS NOT NULL THEN registrations.other_complaint ELSE COALESCE(complaints.name, registrations.complaint) END AS complaint'),
                'registrant',
                'user_doctor.id as user_doctor_id',
                'user_doctor.username as username_doctor',
                'users.fullname as created_by',
                'registrations.acceptance_status',
                DB::raw("DATE_FORMAT(registrations.created_at, '%d %b %Y %H:%i:%s') as created_at"),
                'users.branch_id as user_branch_id')
            ->where('registrations.acceptance_status', '=', '0')
            ->where('registrations.isDeleted', '=', '0');

        if ($request->keyword) {
            $res = $this->Search($request);

            if ($res) {
                $data = $data->where($res, 'like', '%' . $request->keyword . '%');
            } else {
                $data = [];
                return response()->json(['total_paging' => 0,
                    'data' => $data], 200);
            }
        }

        if ($request->user()->role == 'dokter') {
            $data = $data->where('user_doctor.id', '=', $request->user()->id);
        }

        if ($request->orderby) {

            $data = $data->orderBy($request->column, $request->orderby);
        }

        $data = $data->orderBy('registrations.id', 'desc');

        $offset = ($page - 1) * $items_per_page;

        $count_data = $data->count();
        $count_result = $count_data - $offset;

        if ($count_result < 0) {
            $data = $data->offset(0)->limit($items_per_page)->get();
        } else {
            $data = $data->offset($offset)->limit($items_per_page)->get();
        }

        $total_paging = $count_data / $items_per_page;

        return response()->json(['total_paging' => ceil($total_paging),
            'data' => $data], 200);

    }

    private function Search($request)
    {
        $temp_column = '';

        $data = DB::table('registrations')
            ->join('users', 'registrations.user_id', '=', 'users.id')
            ->join('users as user_doctor', 'registrations.doctor_user_id', '=', 'user_doctor.id')
            ->join('patients', 'registrations.patient_id', '=', 'patients.id')
            ->join('branches', 'patients.branch_id', '=', 'branches.id')
            ->select(
                'registrations.id_number',
                'patients.id_member',
                'patients.pet_category',
                'patients.pet_name',
                'patients.owner_name',
                'complaint',
                'registrant',
                'users.fullname'
            )
            ->where('registrations.acceptance_status', '=', '0');

        if ($request->user()->role == 'dokter') {
            $data = $data->where('user_doctor.id', '=', $request->user()->id);
        }

        if ($request->keyword) {
            $data = $data->where('registrations.id_number', 'like', '%' . $request->keyword . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'registrations.id_number';
            return $temp_column;
        }

        //=====================================

        $data = DB::table('registrations')
            ->join('users', 'registrations.user_id', '=', 'users.id')
            ->join('users as user_doctor', 'registrations.doctor_user_id', '=', 'user_doctor.id')
            ->join('patients', 'registrations.patient_id', '=', 'patients.id')
            ->join('branches', 'patients.branch_id', '=', 'branches.id')
            ->select(
                'registrations.id_number',
                'patients.id_member',
                'patients.pet_category',
                'patients.pet_name',
                'patients.owner_name',
                'complaint',
                'registrant',
                'users.fullname'
            )
            ->where('registrations.acceptance_status', '=', '0');

        if ($request->user()->role == 'dokter') {
            $data = $data->where('user_doctor.id', '=', $request->user()->id);
        }

        if ($request->keyword) {
            $data = $data->where('patients.id_member', 'like', '%' . $request->keyword . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'patients.id_member';
            return $temp_column;
        }

        //=====================================

        $data = DB::table('registrations')
            ->join('users', 'registrations.user_id', '=', 'users.id')
            ->join('users as user_doctor', 'registrations.doctor_user_id', '=', 'user_doctor.id')
            ->join('patients', 'registrations.patient_id', '=', 'patients.id')
            ->join('branches', 'patients.branch_id', '=', 'branches.id')
            ->select(
                'registrations.id_number',
                'patients.id_member',
                'patients.pet_category',
                'patients.pet_name',
                'patients.owner_name',
                'complaint',
                'registrant',
                'users.fullname'
            )
            ->where('registrations.acceptance_status', '=', '0');

        if ($request->user()->role == 'dokter') {
            $data = $data->where('user_doctor.id', '=', $request->user()->id);
        }

        if ($request->keyword) {
            $data = $data->where('patients.pet_category', 'like', '%' . $request->keyword . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'patients.pet_category';
            return $temp_column;
        }

        //=====================================

        $data = DB::table('registrations')
            ->join('users', 'registrations.user_id', '=', 'users.id')
            ->join('users as user_doctor', 'registrations.doctor_user_id', '=', 'user_doctor.id')
            ->join('patients', 'registrations.patient_id', '=', 'patients.id')
            ->join('branches', 'patients.branch_id', '=', 'branches.id')
            ->select(
                'registrations.id_number',
                'patients.id_member',
                'patients.pet_category',
                'patients.pet_name',
                'patients.owner_name',
                'complaint',
                'registrant',
                'users.fullname'
            )
            ->where('registrations.acceptance_status', '=', '0');

        if ($request->user()->role == 'dokter') {
            $data = $data->where('user_doctor.id', '=', $request->user()->id);
        }

        if ($request->keyword) {
            $data = $data->where('patients.pet_name', 'like', '%' . $request->keyword . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'patients.pet_name';
            return $temp_column;
        }

        //=====================================

        $data = DB::table('registrations')
            ->join('users', 'registrations.user_id', '=', 'users.id')
            ->join('users as user_doctor', 'registrations.doctor_user_id', '=', 'user_doctor.id')
            ->join('patients', 'registrations.patient_id', '=', 'patients.id')
            ->join('branches', 'patients.branch_id', '=', 'branches.id')
            ->select(
                'registrations.id_number',
                'patients.id_member',
                'patients.pet_category',
                'patients.pet_name',
                'patients.owner_name',
                'complaint',
                'registrant',
                'users.fullname'
            )
            ->where('registrations.acceptance_status', '=', '0');

        if ($request->user()->role == 'dokter') {
            $data = $data->where('user_doctor.id', '=', $request->user()->id);
        }

        if ($request->keyword) {
            $data = $data->where('patients.owner_name', 'like', '%' . $request->keyword . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'patients.owner_name';
            return $temp_column;
        }

        //=====================================

        $data = DB::table('registrations')
            ->join('users', 'registrations.user_id', '=', 'users.id')
            ->join('users as user_doctor', 'registrations.doctor_user_id', '=', 'user_doctor.id')
            ->join('patients', 'registrations.patient_id', '=', 'patients.id')
            ->join('branches', 'patients.branch_id', '=', 'branches.id')
            ->select(
                'registrations.id_number',
                'patients.id_member',
                'patients.pet_category',
                'patients.pet_name',
                'patients.owner_name',
                'complaint',
                'registrant',
                'users.fullname'
            )
            ->where('registrations.acceptance_status', '=', '0');

        if ($request->user()->role == 'dokter') {
            $data = $data->where('user_doctor.id', '=', $request->user()->id);
        }

        if ($request->keyword) {
            $data = $data->where('complaint', 'like', '%' . $request->keyword . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'complaint';
            return $temp_column;
        }

        //=====================================

        $data = DB::table('registrations')
            ->join('users', 'registrations.user_id', '=', 'users.id')
            ->join('users as user_doctor', 'registrations.doctor_user_id', '=', 'user_doctor.id')
            ->join('patients', 'registrations.patient_id', '=', 'patients.id')
            ->join('branches', 'patients.branch_id', '=', 'branches.id')
            ->select(
                'registrations.id_number',
                'patients.id_member',
                'patients.pet_category',
                'patients.pet_name',
                'patients.owner_name',
                'complaint',
                'registrant',
                'users.fullname'
            )
            ->where('registrations.acceptance_status', '=', '0');

        if ($request->user()->role == 'dokter') {
            $data = $data->where('user_doctor.id', '=', $request->user()->id);
        }

        if ($request->keyword) {
            $data = $data->where('registrant', 'like', '%' . $request->keyword . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'registrant';
            return $temp_column;
        }

        //=====================================

        $data = DB::table('registrations')
            ->join('users', 'registrations.user_id', '=', 'users.id')
            ->join('users as user_doctor', 'registrations.doctor_user_id', '=', 'user_doctor.id')
            ->join('patients', 'registrations.patient_id', '=', 'patients.id')
            ->join('branches', 'patients.branch_id', '=', 'branches.id')
            ->select(
                'registrations.id_number',
                'patients.id_member',
                'patients.pet_category',
                'patients.pet_name',
                'patients.owner_name',
                'complaint',
                'registrant',
                'users.fullname'
            )
            ->where('registrations.acceptance_status', '=', '0');

        if ($request->user()->role == 'dokter') {
            $data = $data->where('user_doctor.id', '=', $request->user()->id);
        }

        if ($request->keyword) {
            $data = $data->where('users.fullname', 'like', '%' . $request->keyword . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'users.fullname';
            return $temp_column;
        }

        //=====================================
    }

    public function accept(Request $request)
    {
        if ($request->user()->role == 'resepsionis') {
            return response()->json([
                'message' => 'The user role was invalid.',
                'errors' => ['Akses User tidak diizinkan!'],
            ], 403);
        }

        $registration = Registration::find($request->id);

        if (is_null($registration)) {
            return response()->json([
                'message' => 'The data was invalid.',
                'errors' => ['Data tidak ditemukan!'],
            ], 404);
        } elseif ($registration->acceptance_status == 1) {
            return response()->json([
                'message' => 'The data was invalid.',
                'errors' => ['Data tidak dapat diubah karena sudah diterima oleh dokter!'],
            ], 422);
        } elseif ($registration->acceptance_status == 2) {
            return response()->json([
                'message' => 'The data was invalid.',
                'errors' => ['Data tidak dapat diubah karena sudah ditolak oleh dokter!'],
            ], 422);
        } elseif ($registration->acceptance_status == 3) {
            return response()->json([
                'message' => 'The data was invalid.',
                'errors' => ['Data tidak dapat diubah karena sudah selesai!'],
            ], 422);
        }

        $registration->acceptance_status = 1;
        $registration->user_update_id = $request->user()->id;
        $registration->updated_at = \Carbon\Carbon::now();
        $registration->save();

        $patient = DoctorAcceptance::create([
            'patient_registration_id' => $registration->id,
            'reason' => '',
            'acceptance_status' => 1,
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Proses Data Berhasil',
        ], 200);
    }

    public function decline(Request $request)
    {
        if ($request->user()->role == 'resepsionis') {
            return response()->json([
                'message' => 'The user role was invalid.',
                'errors' => ['Akses User tidak diizinkan!'],
            ], 403);
        }

        $registration = Registration::find($request->id);

        if (is_null($registration)) {
            return response()->json([
                'message' => 'The data was invalid.',
                'errors' => ['Data tidak ditemukan!'],
            ], 404);
        } elseif ($registration->acceptance_status == 1) {
            return response()->json([
                'message' => 'The data was invalid.',
                'errors' => ['Data tidak dapat diubah karena sudah diterima oleh dokter!'],
            ], 422);
        } elseif ($registration->acceptance_status == 2) {
            return response()->json([
                'message' => 'The data was invalid.',
                'errors' => ['Data tidak dapat diubah karena sudah ditolak oleh dokter!'],
            ], 422);
        } elseif ($registration->acceptance_status == 3) {
            return response()->json([
                'message' => 'The data was invalid.',
                'errors' => ['Data tidak dapat diubah karena sudah selesai!'],
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'alasan' => 'required|string|min:10|max:100',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->all();

            return response()->json([
                'message' => 'Data yang dimasukkan tidak valid!',
                'errors' => $errors,
            ], 422);
        }

        $patient = DoctorAcceptance::create([
            'patient_registration_id' => $registration->id,
            'reason' => $request->alasan,
            'acceptance_status' => 2,
            'user_id' => $request->user()->id,
        ]);

        $registration->acceptance_status = 2;
        $registration->user_update_id = $request->user()->id;
        $registration->updated_at = \Carbon\Carbon::now();
        $registration->save();

        return response()->json([
            'message' => 'Proses Data Berhasil',
        ], 200);
    }
}
