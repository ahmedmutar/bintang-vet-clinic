<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Lupa Password | Bintang Vet Clinic</title>
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <link rel="shortcut icon" type="image/jpg" href="{{ asset('assets/image/logo-vet-clinic.jpg') }}">
  <link rel="stylesheet" href="{{ asset('bootstrap/dist/css/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('bower_components/font-awesome/css/font-awesome.min.css') }}">
  <link rel="stylesheet" href="{{ asset('dist/css/AdminLTE.css') }}">
  <link rel="stylesheet" type='text/css' href="{{ asset('main/css/input-custom.css') }}">
  <link rel="stylesheet" type='text/css' href="{{ asset('main/css/global.css') }}">
  <link rel="stylesheet" type='text/css' href="{{ asset('main/css/login.css') }}">
  <style>
    .step-indicator { display: flex; justify-content: center; align-items: center; margin-bottom: 24px; gap: 0; }
    .step-item { display: flex; flex-direction: column; align-items: center; flex: 1; position: relative; }
    .step-item:not(:last-child)::after { content: ''; position: absolute; top: 14px; left: 60%; width: 80%; height: 2px; background: #ddd; z-index: 0; }
    .step-item.done:not(:last-child)::after { background: #3c8dbc; }
    .step-circle { width: 28px; height: 28px; border-radius: 50%; border: 2px solid #ddd; background: #fff; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; color: #bbb; z-index: 1; position: relative; }
    .step-item.active .step-circle { border-color: #3c8dbc; color: #3c8dbc; }
    .step-item.done .step-circle { background: #3c8dbc; border-color: #3c8dbc; color: #fff; }
    .step-label { font-size: 10px; color: #bbb; margin-top: 4px; text-align: center; }
    .step-item.active .step-label, .step-item.done .step-label { color: #3c8dbc; }
    .otp-input-group { display: flex; gap: 8px; justify-content: center; margin: 16px 0; }
    .otp-digit { width: 44px; height: 52px; text-align: center; font-size: 22px; font-weight: bold; border: 2px solid #ddd; border-radius: 6px; outline: none; }
    .otp-digit:focus { border-color: #3c8dbc; box-shadow: 0 0 0 2px rgba(60,141,188,0.2); }
    .success-section { text-align: center; padding: 16px 0; }
    .success-section .fa-check-circle { font-size: 56px; color: #00a65a; margin-bottom: 16px; }
    .m-t-10px { margin-top: 10px; }
    .m-t-20px { margin-top: 20px; }
    .text-link-center { text-align: center; margin-top: 14px; }
    .resend-section { text-align: center; margin-top: 12px; font-size: 13px; color: #888; }
    #btnResendOtp { background: none; border: none; color: #3c8dbc; cursor: pointer; padding: 0; font-size: 13px; }
    #btnResendOtp:disabled { color: #bbb; cursor: not-allowed; }
  </style>
</head>
<body class="hold-transition login-page">

<div class="login-box">
  <input type="hidden" id="baseUrl" value="{{ url('/') }}">

  <div class="header-login-section">
    <div class="title-login">Sistem Administrasi Bintang Vet Clinic</div>
    <img src="{{ asset('assets/image/logo-vet-clinic.jpg') }}">
  </div>

  <div class="login-container">
    <div class="login-box-body">

      <!-- Step Indicator -->
      <div class="step-indicator" id="stepIndicator">
        <div class="step-item active" id="stepItem1">
          <div class="step-circle">1</div>
          <div class="step-label">Email</div>
        </div>
        <div class="step-item" id="stepItem2">
          <div class="step-circle">2</div>
          <div class="step-label">Verifikasi OTP</div>
        </div>
        <div class="step-item" id="stepItem3">
          <div class="step-circle">3</div>
          <div class="step-label">Password Baru</div>
        </div>
      </div>

      <!-- Step 1: Input Email -->
      <div id="step1">
        <p class="login-box-msg" style="margin-bottom:8px;">Lupa Password</p>
        <p style="font-size:13px; color:#777; text-align:center; margin-bottom:20px;">
          Masukkan email Anda dan kami akan mengirimkan kode OTP.
        </p>
        <div class="form-group has-feedback">
          <input type="email" id="inputEmail" class="form-control" placeholder="Alamat email terdaftar">
          <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
          <div id="emailErr" class="validate-error"></div>
        </div>
        <div id="beErr1" class="validate-error m-t-10px"></div>
        <button type="button" id="btnKirimOtp" class="btn btn-primary btn-block btn-flat m-t-10px">
          <i class="fa fa-paper-plane"></i> Kirim Kode OTP
        </button>
        <div class="text-link-center">
          <a href="{{ url('/masuk') }}"><i class="fa fa-arrow-left"></i> Kembali ke Login</a>
        </div>
      </div>

      <!-- Step 2: Input OTP -->
      <div id="step2" style="display:none;">
        <p class="login-box-msg" style="margin-bottom:8px;">Masukkan Kode OTP</p>
        <p style="font-size:13px; color:#777; text-align:center; margin-bottom:4px;">
          Kode 6 digit telah dikirim ke
        </p>
        <p style="font-size:14px; font-weight:bold; color:#3c8dbc; text-align:center; margin-bottom:20px;" id="emailDisplay"></p>

        <div class="otp-input-group">
          <input type="text" class="otp-digit" maxlength="1" data-idx="0" inputmode="numeric">
          <input type="text" class="otp-digit" maxlength="1" data-idx="1" inputmode="numeric">
          <input type="text" class="otp-digit" maxlength="1" data-idx="2" inputmode="numeric">
          <input type="text" class="otp-digit" maxlength="1" data-idx="3" inputmode="numeric">
          <input type="text" class="otp-digit" maxlength="1" data-idx="4" inputmode="numeric">
          <input type="text" class="otp-digit" maxlength="1" data-idx="5" inputmode="numeric">
        </div>

        <div id="beErr2" class="validate-error m-t-10px" style="text-align:center;"></div>

        <button type="button" id="btnVerifikasiOtp" class="btn btn-primary btn-block btn-flat m-t-10px" disabled>
          <i class="fa fa-check"></i> Verifikasi OTP
        </button>

        <div class="resend-section">
          Tidak menerima kode? &nbsp;
          <button type="button" id="btnResendOtp" disabled>
            Kirim ulang (<span id="countdown">60</span>s)
          </button>
        </div>
      </div>

      <!-- Step 3: Input Password Baru -->
      <div id="step3" style="display:none;">
        <p class="login-box-msg" style="margin-bottom:8px;">Buat Password Baru</p>
        <p style="font-size:13px; color:#777; text-align:center; margin-bottom:20px;">
          OTP terverifikasi. Masukkan password baru Anda.
        </p>
        <div class="form-group">
          <div class="p-relative">
            <input type="password" id="newPassword" class="form-control p-right-42px" placeholder="Password baru (min. 8 karakter)">
            <span id="toggleNewPwd" class="glyphicon icon-password glyphicon-eye-open"></span>
          </div>
          <div id="newPasswordErr" class="validate-error"></div>
        </div>
        <div class="form-group">
          <div class="p-relative">
            <input type="password" id="confirmPassword" class="form-control p-right-42px" placeholder="Ulangi password baru">
            <span id="toggleConfirmPwd" class="glyphicon icon-password glyphicon-eye-open"></span>
          </div>
          <div id="confirmPasswordErr" class="validate-error"></div>
        </div>
        <div id="beErr3" class="validate-error m-t-10px"></div>
        <button type="button" id="btnResetPassword" class="btn btn-primary btn-block btn-flat m-t-10px" disabled>
          <i class="fa fa-lock"></i> Reset Password
        </button>
      </div>

      <!-- Sukses -->
      <div id="stepSuccess" style="display:none;">
        <div class="success-section">
          <i class="fa fa-check-circle"></i>
          <h4>Password Berhasil Direset!</h4>
          <p style="color:#777; font-size:14px;">Silakan login menggunakan password baru Anda.</p>
          <a href="{{ url('/masuk') }}" class="btn btn-success btn-block btn-flat m-t-20px">
            <i class="fa fa-sign-in"></i> Login Sekarang
          </a>
        </div>
      </div>

    </div>
  </div>

  <div id="loading-screen"></div>
</div>

<script src="{{ asset('bower_components/jquery/dist/jquery.min.js') }}"></script>
<script src="{{ asset('bootstrap/dist/js/bootstrap.min.js') }}"></script>
<script src="{{ asset('main/js/auth/lupa-password.js') }}"></script>
</body>
</html>
