<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Sistem Administrasi Kuma Vet Clinic | Masuk</title>
  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <link rel="shortcut icon" type="image/jpg" href="{{ asset('assets/image/kuma.jpeg') }}">
  <!-- Bootstrap 3.3.7 -->
  <link rel="stylesheet" href="{{ asset('bootstrap/dist/css/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('bower_components/font-awesome/css/font-awesome.min.css') }}">
  <link rel="stylesheet" href="{{ asset('bower_components/Ionicons/css/ionicons.min.css') }}">
  <link rel="stylesheet" href="{{ asset('dist/css/AdminLTE.css') }}">
  <link rel="stylesheet" href="{{ asset('plugins/iCheck/square/blue.css') }}">
  <!-- Leaflet CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

  <script src="{{ asset('vuejs/vue.js') }}"></script>
  <script src="{{ asset('vuejs/axios.js') }}"></script>

  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
  <link rel="stylesheet" type='text/css' href="{{ asset('main/css/alert-custom.css') }}">
  <link rel="stylesheet" type='text/css' href="{{ asset('main/css/input-custom.css') }}">
  <link rel="stylesheet" type='text/css' href="{{ asset('main/css/global.css') }}">
  <link rel="stylesheet" type='text/css' href="{{ asset('main/css/login.css') }}">

  <style>
    /* tablet+ only — mobile uses Bootstrap's default width:auto margin:10px */
    @media (min-width: 768px) {
      #modal-absensi .modal-dialog { width: 680px; }
    }
    #video-selfie   { width: 100%; border-radius: 6px; background: #000; }
    #canvas-selfie  { display: none; }
    #preview-selfie { width: 100%; border-radius: 6px; display: none; border: 2px solid #00a65a; }
    #peta-absensi   { height: 150px; border-radius: 6px; background: #eee; }
    @media (min-width: 768px) {
      #peta-absensi { height: 200px; }
    }
    .jam-realtime    { font-size: 22px; font-weight: bold; color: #3c8dbc; text-align: center; margin-bottom: 8px; }
    @media (min-width: 768px) {
      .jam-realtime  { font-size: 28px; margin-bottom: 10px; }
    }
    .tanggal-absensi { text-align: center; color: #555; margin-bottom: 12px; font-size: 13px; }
    .status-lokasi   { font-size: 12px; color: #777; margin-top: 6px; }
    .section-kamera  { position: relative; }
    .foto-actions    { text-align: center; margin-top: 8px; }

    /* ── Scrollable body agar modal tidak melampaui tinggi layar ── */
    #modal-absensi .modal-body {
      max-height: calc(100vh - 130px);
      overflow-y: auto;
      -webkit-overflow-scrolling: touch;
    }

    /* ── Mobile kecil (≤480px) ── */
    @media (max-width: 480px) {
      .jam-realtime { font-size: 18px; }

      #modal-absensi .modal-body {
        padding: 10px;
      }

      #modal-absensi .modal-footer {
        display: flex;
        flex-direction: column-reverse;
        gap: 8px;
        padding: 10px;
      }

      #modal-absensi .modal-footer .btn {
        width: 100%;
        margin: 0;
      }
    }
  </style>
</head>
<body class="hold-transition login-page">
<div class="login-box" id="login-app">
  <input ref="baseUrl" type="hidden" value="{{ url('/') }}"/>

    <div class="header-login-section">
      <div class="title-login">Sistem Administrasi Kuma Vet Clinic</div>
      <img src="{{ asset('assets/image/kuma.jpeg') }}">
    </div>
    <div class="login-container">
    <div v-if="showAlert" class="alert alert-dismissible"
      v-bind:class="{ 'alert-success': isSuccess, 'alert-danger': !isSuccess }">
      <button type="button" @click="showAlert = false" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
      <h4><i class="icon fa" v-bind:class="{ 'fa-check': isSuccess, 'fa-ban': !isSuccess }"></i> Alert!</h4>
      @{{message}}
    </div>
    <div class="login-box-body">
      <p class="login-box-msg">Masuk</p>
      <form>
        <div class="form-group has-feedback">
          <input type="text" class="form-control" :class="{'error-form-control' : usernameError}"
            @keyup="usernameKeyup" @keydown.enter="onSubmit" placeholder="Username" v-model="form.username">
          <span class="glyphicon glyphicon-user form-control-feedback"></span>
          <span class="validate-error">@{{usernameError ? 'Username perlu di isi' : ''}}</span>
        </div>
        <div class="form-group has-feedback">
          <input v-bind:type="passwordType" class="form-control" :class="{'error-form-control' : passwordError}"
            @keyup="passwordKeyup" @keydown.enter="onSubmit" placeholder="Kata Sandi" v-model="form.password">
          <span @click="togglePassword" class="glyphicon icon-password"
            :class="{ 'glyphicon-eye-open': showPassword, 'glyphicon-eye-close': !showPassword }"></span>
          <span class="validate-error">@{{passwordError ? 'Kata Sandi perlu di isi' : ''}}</span>
        </div>
        <div class="row">
          <div class="col-md-12">
            <button type="button" :disabled="disableSubmit"
              class="btn btn-primary btn-block btn-flat m-b-15px" @click="onSubmit">Masuk</button>
          </div>
        </div>
        <div class="row">
          <div class="col-md-12 text-center">
            <a href="{{ url('/lupa-password') }}" style="font-size:13px; color:#3c8dbc;">Lupa Password?</a>
          </div>
        </div>
      </form>
    </div>
  </div>
  <div id="loading-screen"></div>
</div>

<!-- Modal Absensi -->
<div class="modal fade" id="modal-absensi" tabindex="-1" data-backdrop="static" data-keyboard="false" id="absensi-app">
  <div class="modal-dialog">
    <div class="modal-content" id="absensi-app">
      <div class="modal-header" style="background:#3c8dbc; color:#fff;">
        <h4 class="modal-title"><i class="fa fa-clock-o"></i> Absen Masuk</h4>
      </div>
      <div class="modal-body">

        <div class="jam-realtime" id="jam-absensi">--:--:--</div>
        <div class="tanggal-absensi" id="tgl-absensi"></div>

        <!-- Shift -->
        <div class="form-group">
          <label>Shift Kerja <span class="text-danger">*</span></label>
          <select class="form-control" id="select-shift">
            <option value="">-- Memuat shift... --</option>
          </select>
        </div>

        <!-- Kamera Selfie -->
        <div class="form-group">
          <label>Foto Selfie <span class="text-danger">*</span></label>
          <div class="section-kamera">
            <video id="video-selfie" autoplay playsinline></video>
            <canvas id="canvas-selfie"></canvas>
            <img id="preview-selfie" src="" alt="Foto selfie">
            <div class="foto-actions">
              <button type="button" class="btn btn-sm btn-info m-t-5px" id="btn-ambil-foto">
                <i class="fa fa-camera"></i> Ambil Foto
              </button>
              <button type="button" class="btn btn-sm btn-default m-t-5px" id="btn-ulangi-foto" style="display:none;">
                <i class="fa fa-refresh"></i> Ulangi
              </button>
            </div>
          </div>
        </div>

        <!-- Peta Lokasi -->
        <div class="form-group">
          <label><i class="fa fa-map-marker"></i> Lokasi Saat Ini</label>
          <div id="peta-absensi"></div>
          <div class="status-lokasi" id="status-lokasi">
            <i class="fa fa-spinner fa-spin"></i> Mendeteksi lokasi...
          </div>
          <div class="status-lokasi" id="status-jarak" style="display:none;"></div>
        </div>

        <!-- Keterangan -->
        <div class="form-group">
          <label>Keterangan</label>
          <textarea class="form-control" id="input-keterangan" rows="3" placeholder="Masukkan keterangan (opsional)"></textarea>
        </div>

        <div id="alert-absensi" class="alert" style="display:none;"></div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" id="btn-skip-absensi">Lewati</button>
        <button type="button" class="btn btn-success" id="btn-submit-absensi" disabled>
          <i class="fa fa-check"></i> Absen Sekarang
        </button>
      </div>
    </div>
  </div>
</div>

<!-- jQuery & Bootstrap -->
<script src="{{ asset('bower_components/jquery/dist/jquery.min.js') }}"></script>
<script src="{{ asset('bootstrap/dist/js/bootstrap.min.js') }}"></script>
<script src="{{ asset('plugins/iCheck/icheck.min.js') }}"></script>
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
  $(function () {
    $('input').iCheck({
      checkboxClass: 'icheckbox_square-blue',
      radioClass: 'iradio_square-blue',
      increaseArea: '20%'
    });
  });
</script>
<script src="{{ asset('main/js/auth/login-vue.js') }}"></script>
<script src="{{ asset('main/js/absensi/absensi-modal.js') }}"></script>
</body>
</html>
