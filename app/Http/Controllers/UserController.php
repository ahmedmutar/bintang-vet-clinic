<?php

namespace App\Http\Controllers;

use App\Models\User;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class UserController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            $errors = $validator->messages();

            return response()->json([
                'message' => 'User yang dimasukkan tidak valid!',
                'errors' => $errors,
            ], 401);
        }

        $credentials = $request->only('username', 'password');
        $token = null;

        try {
            // attempt to verify the credentials and create a token for the user
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'message' => 'User yang dimasukkan tidak valid!',
                    'errors' => ['Username atau password yang dimasukkan salah!'],
                ], 401);
            }
        } catch (JWTException $e) {
            // something went wrong whilst attempting to encode the token
            return response()->json([
                'message' => 'Tidak dapat membuat token!',
            ], 500);
            //return response()->json(['error' => 'could_not_create_token'], 500);
        }

        $user = DB::table('users')
            ->join('branches', 'users.branch_id', '=', 'branches.id')
            ->select('users.branch_id', 'branches.branch_name', 'users.username', 'users.image_profile', 'users.fullname', 'users.email', 'users.role', 'users.status', 'users.isDeleted')
            ->where('users.id', '=', $request->user()->id)
            ->get();

        if ($user[0]->status == 0) {
            return response()->json([
                'message' => 'Akun yang anda gunakan tidak aktif!',
                'errors' => ['Akses tidak diijinkan!'],
            ], 403);
        } elseif ($user[0]->isDeleted == 1) {
            return response()->json([
                'message' => 'Akun yang anda gunakan sudah dihapus!',
                'errors' => ['Akses tidak diijinkan!'],
            ], 403);
        }

        return response()->json(
            [
                'user_id' => $request->user()->id,
                'branch_id' => $user[0]->branch_id,
                'branch_name' => $user[0]->branch_name,
                'token' => $token,
                'username' => $user[0]->username,
                'fullname' => $user[0]->fullname,
                'email' => $user[0]->email,
                'image_profile' => $user[0]->image_profile,
                'role' => $user[0]->role,
            ]
        );
    }

    public function register(Request $request)
    {

        if ($request->user()->role == 'dokter' || $request->user()->role == 'resepsionis') {
            return response()->json([
                'message' => 'User yang dimasukkan tidak valid!',
                'errors' => ['Akses tidak diijinkan!'],
            ], 403);
        }

        $messages = [
            'password.regex' => 'Password harus mengandung huruf besar, huruf kecil, simbol, dan angka!',
        ];

        $validator = Validator::make($request->all(), [
            'username' => 'required|min:3|max:25|unique:users,username',
            'nama_lengkap' => 'required|min:3|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|min:8|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/',
            'nomor_ponsel' => 'required|numeric|digits_between:10,12|unique:users,phone_number',
            'role' => 'required|string',
            'status' => 'required|boolean',
            'kode_cabang' => 'required|string',
            'id_cabang' => 'required|integer',
        ], $messages);

        if ($validator->fails()) {
            $errors = $validator->errors()->all();

            return response()->json([
                'message' => 'User yang dimasukkan tidak valid!',
                'errors' => $errors,
            ], 422);
        }

        $lastuser = DB::table('users')
            ->where('branch_id', '=', $request->id_cabang)
            ->count();

        $staff_number = 'BVC-U-' . $request->kode_cabang . '-' . str_pad($lastuser + 1, 4, 0, STR_PAD_LEFT);

        $user = User::create([
            'staffing_number' => $staff_number,
            'username' => $request->username,
            'fullname' => $request->nama_lengkap,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'status' => $request->status,
            'phone_number' => strval($request->nomor_ponsel),
            'role' => $request->role,
            'branch_id' => $request->id_cabang,
            'created_by' => $request->user()->fullname,
        ]);

        return response()->json(
            [
                'message' => 'Register Berhasil!',
            ], 200
        );
    }

    public function index(Request $request)
    {
        if ($request->keyword) {

            $res = $this->Search($request);

            $user = DB::table('users')
                ->join('branches', 'users.branch_id', '=', 'branches.id')
                ->select('users.id', 'users.branch_id', 'users.staffing_number', 'users.username', 'users.fullname', 'users.email'
                    , 'users.role', 'users.phone_number', 'branches.branch_name', 'users.status', 'users.created_by',
                    DB::raw("DATE_FORMAT(users.created_at, '%d %b %Y') as created_at"))
                ->where('users.isDeleted', '=', 0);

            if ($request->user()->role == 'dokter' || $request->user()->role == 'resepsionis') {
                $user = $user->where('branches.id','=',$request->user()->branch_id);
            }

            if ($res) {
                $user = $user->where($res, 'like', '%' . $request->keyword . '%');
            } else {
                $data = [];
                return response()->json($data, 200);
            }

            if ($request->branch_id) {
                $user = $user->where('users.branch_id', '=', $request->branch_id);
            }

            if ($request->orderby) {

                $user = $user->orderBy($request->column, $request->orderby);
            }

            $user = $user->orderBy('users.id', 'desc');

            $user = $user->get();
            return response()->json($user, 200);

        } else {

            $user = DB::table('users')
                ->join('branches', 'users.branch_id', '=', 'branches.id')
                ->select('users.id', 'users.branch_id', 'users.staffing_number', 'users.username', 'users.fullname', 'users.email'
                    , 'users.role', 'users.phone_number', 'branches.branch_name', 'users.status', 'users.created_by',
                    DB::raw("DATE_FORMAT(users.created_at, '%d %b %Y') as created_at"))
                ->where('users.isDeleted', '=', 0);

                if ($request->user()->role == 'dokter' || $request->user()->role == 'resepsionis') {
                    $user = $user->where('branches.id','=',$request->user()->branch_id);
                }

            if ($request->branch_id) {
                $user = $user->where('users.branch_id', '=', $request->branch_id);
            }

            if ($request->orderby) {

                $user = $user->orderBy($request->column, $request->orderby);
            }

            $user = $user->orderBy('users.id', 'desc');

            $user = $user->get();

            return response()->json($user, 200);
        }

    }

    private function Search($request)
    {
        $temp_column = '';
        $data = DB::table('users')
            ->join('branches', 'users.branch_id', '=', 'branches.id')
            ->select('users.id', 'users.branch_id', 'users.staffing_number', 'users.username', 'users.fullname', 'users.email'
                , 'users.role', 'users.phone_number', 'branches.branch_name', 'users.status', 'users.created_by',
                DB::raw("DATE_FORMAT(users.created_at, '%d %b %Y') as created_at"))
            ->where('users.isDeleted', '=', 0);

        if ($request->keyword) {
            $data = $data->where('users.staffing_number', 'like', '%' . $request->keyword . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'users.staffing_number';
            return $temp_column;
        }
        //=====================================
        $data = DB::table('users')
            ->join('branches', 'users.branch_id', '=', 'branches.id')
            ->select('users.id', 'users.branch_id', 'users.staffing_number', 'users.username', 'users.fullname', 'users.email'
                , 'users.role', 'users.phone_number', 'branches.branch_name', 'users.status', 'users.created_by',
                DB::raw("DATE_FORMAT(users.created_at, '%d %b %Y') as created_at"))
            ->where('users.isDeleted', '=', 0);

        if ($request->keyword) {
            $data = $data->where('users.username', 'like', '%' . $request->keyword . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'users.username';
            return $temp_column;
        }
        //=====================================
        $data = DB::table('users')
            ->join('branches', 'users.branch_id', '=', 'branches.id')
            ->select('users.id', 'users.branch_id', 'users.staffing_number', 'users.username', 'users.fullname', 'users.email'
                , 'users.role', 'users.phone_number', 'branches.branch_name', 'users.status', 'users.created_by',
                DB::raw("DATE_FORMAT(users.created_at, '%d %b %Y') as created_at"))
            ->where('users.isDeleted', '=', 0);

        if ($request->keyword) {
            $data = $data->where('users.fullname', 'like', '%' . $request->keyword . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'users.fullname';
            return $temp_column;
        }
        //=====================================
        $data = DB::table('users')
            ->join('branches', 'users.branch_id', '=', 'branches.id')
            ->select('users.id', 'users.branch_id', 'users.staffing_number', 'users.username', 'users.fullname', 'users.email'
                , 'users.role', 'users.phone_number', 'branches.branch_name', 'users.status', 'users.created_by',
                DB::raw("DATE_FORMAT(users.created_at, '%d %b %Y') as created_at"))
            ->where('users.isDeleted', '=', 0);

        if ($request->keyword) {
            $data = $data->where('users.email', 'like', '%' . $request->keyword . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'users.email';
            return $temp_column;
        }

        //=====================================
        $data = DB::table('users')
            ->join('branches', 'users.branch_id', '=', 'branches.id')
            ->select('users.id', 'users.branch_id', 'users.staffing_number', 'users.username', 'users.fullname', 'users.email'
                , 'users.role', 'users.phone_number', 'branches.branch_name', 'users.status', 'users.created_by',
                DB::raw("DATE_FORMAT(users.created_at, '%d %b %Y') as created_at"))
            ->where('users.isDeleted', '=', 0);

        if ($request->keyword) {
            $data = $data->where('users.role', 'like', '%' . $request->keyword . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'users.role';
            return $temp_column;
        }

    }

    public function update(Request $request)
    {
        if ($request->user()->role == 'dokter' || $request->user()->role == 'resepsionis') {
            return response()->json([
                'message' => 'User yang dimasukkan tidak valid!',
                'errors' => ['Akses tidak diijinkan!'],
            ], 403);
        }

        $messages = [
            'password.regex' => 'Password harus mengandung huruf besar, huruf kecil, simbol, dan angka!',
        ];

        $validator = Validator::make($request->all(), [
            'nomor_kepegawaian' => 'required|string',
            'nama_lengkap' => 'required|min:3|max:255',
            //'password' => 'required|min:8|confirmed|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/',
            'role' => 'required|string',
            'status' => 'required|boolean',
            'id_cabang' => 'required|integer',
            'kode_cabang' => 'required|string',
        ], $messages);

        if ($validator->fails()) {
            $errors = $validator->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        $user = User::find($request->id);

        if (is_null($user)) {
            return response()->json([
                'message' => 'The data was invalid.',
                'errors' => ['Data tidak ditemukan!'],
            ], 404);
        }

        $chk_user_branch = DB::table('users')
            ->select('branch_id')
            ->where('id', '=', $request->id)->first();

        $staff_number = '';

        if ($chk_user_branch->branch_id != $request->id_cabang) {
            $lastuser = DB::table('users')
                ->where('branch_id', '=', $request->id_cabang)
                ->count();

            $staff_number = 'BVC-U-' . $request->kode_cabang . '-' . str_pad($lastuser + 1, 4, 0, STR_PAD_LEFT);
        } else {

            $staff_number = $request->nomor_kepegawaian;

        }

        $user->staffing_number = $staff_number;
        $user->fullname = $request->nama_lengkap;
        //$user->password = bcrypt($request->password);
        $user->role = $request->role;
        $user->status = $request->status;
        $user->branch_id = $request->id_cabang;
        $user->status = $request->status;
        $user->update_by = $request->user()->fullname;
        $user->updated_at = \Carbon\Carbon::now();
        $user->save();

        return response()->json([
            'message' => 'Berhasil mengupdate Cabang',
        ], 200);
    }

    public function delete(Request $request)
    {
        if ($request->user()->role == 'dokter' || $request->user()->role == 'resepsionis') {
            return response()->json([
                'message' => 'User yang dimasukkan tidak valid!',
                'errors' => ['Akses tidak diijinkan!'],
            ], 403);
        }

        $user = User::find($request->user_id);

        if (is_null($user)) {
            return response()->json([
                'message' => 'The data was invalid.',
                'errors' => ['Data tidak ditemukan!'],
            ], 404);
        }

        $user->isDeleted = true;
        $user->deleted_by = $request->user()->fullname;
        $user->deleted_at = \Carbon\Carbon::now();
        $user->save();

        //$user->delete();

        return response()->json([
            'message' => 'Berhasil menghapus User',
        ], 200);
    }

    public function logout(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'username' => 'required',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->all();

            return response()->json([
                'message' => 'User yang dimasukkan tidak valid!',
                'errors' => $errors,
            ], 401);
        }

        $credentials = $request->only('username');
        $token = null;

        $token = JWTAuth::invalidate($credentials);

        return response()->json(
            [
                'message' => 'Success!',
            ], 200);
    }

    public function reset_password(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Akses tidak diijinkan!',
                'errors'  => ['Hanya admin yang dapat mereset password user lain!'],
            ], 403);
        }

        $messages = [
            'new_password.regex' => 'Password baru harus mengandung huruf besar, huruf kecil, simbol, dan angka!',
        ];

        $validator = Validator::make($request->all(), [
            'user_id'          => 'required|integer',
            'new_password'     => 'required|min:8|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/',
            'confirm_password' => 'required|string|same:new_password',
        ], $messages);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Data yang dimasukkan tidak valid!',
                'errors'  => $validator->errors()->all(),
            ], 422);
        }

        $user = User::find($request->user_id);

        if (is_null($user)) {
            return response()->json([
                'message' => 'Data tidak ditemukan!',
                'errors'  => ['User tidak ditemukan!'],
            ], 404);
        }

        $user->password   = bcrypt($request->new_password);
        $user->updated_at = \Carbon\Carbon::now();
        $user->save();

        return response()->json(['message' => 'Berhasil mereset Password user'], 200);
    }

    public function doctor(Request $request)
    {
        $data = DB::table('users')
            ->join('branches', 'users.branch_id', '=', 'branches.id')
            ->select('users.id', 'users.username', 'users.role', 'branches.id as branch_id', 'branches.branch_name')
            ->where('users.role', '=', 'dokter')
            ->where('users.status', '=', '1')
            ->where('users.isDeleted', '=', '0');

        if ($request->user()->role == 'resepsionis') {
            $data = $data->where('users.branch_id', '=', $request->user()->branch_id);
        }

        $data = $data->orderBy('users.id', 'desc');

        $data = $data->get();

        return response()->json($data, 200);
    }
}
