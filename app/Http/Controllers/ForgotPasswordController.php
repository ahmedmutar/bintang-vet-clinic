<?php

namespace App\Http\Controllers;

use App\Mail\OtpResetPasswordMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ForgotPasswordController extends Controller
{
    private const OTP_EXPIRY_MINUTES = 10;

    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Email tidak valid!',
                'errors'  => $validator->errors()->all(),
            ], 422);
        }

        $user = DB::table('users')
            ->where('email', $request->email)
            ->where('isDeleted', 0)
            ->where('status', 1)
            ->first();

        if (!$user) {
            return response()->json([
                'message' => 'Email tidak ditemukan!',
                'errors'  => ['Email tidak terdaftar atau akun tidak aktif!'],
            ], 404);
        }

        DB::table('password_resets')->where('email', $request->email)->delete();

        $otp   = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $token = Hash::make($otp);

        DB::table('password_resets')->insert([
            'email'      => $request->email,
            'token'      => $token,
            'created_at' => Carbon::now(),
        ]);

        Mail::to($request->email)->send(new OtpResetPasswordMail($user->fullname, $otp));

        return response()->json([
            'message' => 'Kode OTP telah dikirim ke email Anda. Berlaku selama ' . self::OTP_EXPIRY_MINUTES . ' menit.',
        ], 200);
    }

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp'   => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Data tidak valid!',
                'errors'  => $validator->errors()->all(),
            ], 422);
        }

        $record = DB::table('password_resets')
            ->where('email', $request->email)
            ->first();

        if (!$record || !Hash::check($request->otp, $record->token)) {
            return response()->json([
                'message' => 'OTP tidak valid!',
                'errors'  => ['Kode OTP yang Anda masukkan salah!'],
            ], 422);
        }

        if (Carbon::parse($record->created_at)->addMinutes(self::OTP_EXPIRY_MINUTES)->isPast()) {
            DB::table('password_resets')->where('email', $request->email)->delete();
            return response()->json([
                'message' => 'OTP kadaluarsa!',
                'errors'  => ['Kode OTP sudah kadaluarsa! Silakan minta kode baru.'],
            ], 422);
        }

        return response()->json([
            'message' => 'OTP valid!',
        ], 200);
    }

    public function reset(Request $request)
    {
        $messages = [
            'new_password.regex' => 'Password harus mengandung huruf besar, huruf kecil, simbol, dan angka!',
        ];

        $validator = Validator::make($request->all(), [
            'email'            => 'required|email',
            'otp'              => 'required|string|size:6',
            'new_password'     => 'required|min:8|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/',
            'confirm_password' => 'required|string|same:new_password',
        ], $messages);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Data tidak valid!',
                'errors'  => $validator->errors()->all(),
            ], 422);
        }

        $record = DB::table('password_resets')
            ->where('email', $request->email)
            ->first();

        if (!$record || !Hash::check($request->otp, $record->token)) {
            return response()->json([
                'message' => 'OTP tidak valid!',
                'errors'  => ['Sesi reset password tidak valid! Silakan mulai ulang.'],
            ], 422);
        }

        if (Carbon::parse($record->created_at)->addMinutes(self::OTP_EXPIRY_MINUTES)->isPast()) {
            DB::table('password_resets')->where('email', $request->email)->delete();
            return response()->json([
                'message' => 'OTP kadaluarsa!',
                'errors'  => ['Sesi reset password sudah kadaluarsa! Silakan mulai ulang.'],
            ], 422);
        }

        $user = User::where('email', $request->email)->where('isDeleted', 0)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User tidak ditemukan!',
                'errors'  => ['User tidak ditemukan!'],
            ], 404);
        }

        $user->password   = bcrypt($request->new_password);
        $user->updated_at = Carbon::now();
        $user->save();

        DB::table('password_resets')->where('email', $request->email)->delete();

        return response()->json([
            'message' => 'Password berhasil direset! Silakan login dengan password baru Anda.',
        ], 200);
    }
}
