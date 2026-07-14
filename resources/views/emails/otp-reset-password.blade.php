<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kode OTP Reset Password</title>
  <style>
    body { margin: 0; padding: 0; background-color: #f4f6f9; font-family: Arial, sans-serif; }
    .wrapper { max-width: 520px; margin: 40px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
    .header { background-color: #3c8dbc; padding: 28px 32px; text-align: center; }
    .header img { height: 52px; border-radius: 50%; }
    .header h1 { color: #ffffff; font-size: 20px; margin: 12px 0 0; }
    .body { padding: 32px; color: #444444; }
    .body p { margin: 0 0 16px; line-height: 1.6; font-size: 15px; }
    .otp-box { background: #f0f8ff; border: 2px dashed #3c8dbc; border-radius: 8px; text-align: center; padding: 20px; margin: 24px 0; }
    .otp-code { font-size: 42px; font-weight: bold; letter-spacing: 10px; color: #3c8dbc; }
    .expiry { font-size: 13px; color: #888; margin-top: 8px; }
    .warning { background: #fff8e1; border-left: 4px solid #f39c12; padding: 12px 16px; border-radius: 4px; font-size: 13px; color: #7a6000; margin-top: 20px; }
    .footer { background: #f4f6f9; padding: 20px 32px; text-align: center; font-size: 12px; color: #999; }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="header">
      <img src="{{ asset('assets/image/logo-vet-clinic.jpg') }}" alt="Bintang Vet Clinic">
      <h1>Bintang Vet Clinic</h1>
    </div>
    <div class="body">
      <p>Halo, <strong>{{ $fullname }}</strong>,</p>
      <p>Kami menerima permintaan reset password untuk akun Anda. Gunakan kode OTP berikut untuk melanjutkan:</p>

      <div class="otp-box">
        <div class="otp-code">{{ $otp }}</div>
        <div class="expiry">Berlaku selama <strong>10 menit</strong></div>
      </div>

      <p>Masukkan kode ini di halaman reset password yang sedang Anda buka.</p>

      <div class="warning">
        <strong>Peringatan:</strong> Jangan bagikan kode ini kepada siapapun. Tim Bintang Vet Clinic tidak pernah meminta kode OTP Anda.
        Jika Anda tidak merasa meminta reset password, abaikan email ini.
      </div>
    </div>
    <div class="footer">
      &copy; {{ date('Y') }} Bintang Vet Clinic. Semua hak dilindungi.
    </div>
  </div>
</body>
</html>
