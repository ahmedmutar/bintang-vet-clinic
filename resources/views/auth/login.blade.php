<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Sistem Administrasi Stella Vet Clinic | Masuk</title>
  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <link rel="shortcut icon" type="image/jpg" href="{{ asset('assets/image/stella.jpeg') }}">
  <!-- Bootstrap 3.3.7 -->
  <link rel="stylesheet" href="{{ asset('bootstrap/dist/css/bootstrap.min.css') }}">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="{{ asset('bower_components/font-awesome/css/font-awesome.min.css') }}">
  <!-- Ionicons -->
  <link rel="stylesheet" href="{{ asset('bower_components/Ionicons/css/ionicons.min.css') }}">
  <!-- Theme style -->
  <link rel="stylesheet" href="{{ asset('dist/css/AdminLTE.css') }}">
  <!-- iCheck -->
  <link rel="stylesheet" href="{{ asset('plugins/iCheck/square/blue.css') }}">

  <!-- Vue JS -->
  <script src="{{ asset('vuejs/vue.js') }}"></script>

  <!-- Axios  -->
  <script src="{{ asset('vuejs/axios.js') }}"></script>

  <!-- Google Font -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">

  <link rel="stylesheet" type='text/css' href="{{ asset('main/css/alert-custom.css') }}">
  <link rel="stylesheet" type='text/css' href="{{ asset('main/css/input-custom.css') }}">
  <link rel="stylesheet" type='text/css' href="{{ asset('main/css/global.css') }}">
  <link rel="stylesheet" type='text/css' href="{{ asset('main/css/login.css') }}">
</head>
<body class="hold-transition login-page">
<div class="login-box" id="login-app">
  <input ref="baseUrl" type="hidden" value="{{ url('/') }}"/>

    <div class="header-login-section">
      <div class="title-login">Sistem Administrasi Stella Vet Clinic</div>
      <img src="{{ asset('assets/image/stella.jpeg') }}">
    </div>
    <div class="login-container">
    <div v-if="showAlert" class="alert alert-dismissible"
      v-bind:class="{ 'alert-success': isSuccess, 'alert-danger': !isSuccess }">
      <button type="button" @click="showAlert = false" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
      <h4><i class="icon fa" v-bind:class="{ 'fa-check': isSuccess, 'fa-ban': !isSuccess }"></i> Alert!</h4>
      @{{message}}
    </div>
    <!-- /.login-logo -->
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
      </form>

    </div>
    <!-- /.login-box-body -->
  </div>
  <div id="loading-screen"></div>
</div>
<!-- /.login-box -->

<!-- jQuery 3 -->
<script src="{{ asset('bower_components/jquery/dist/jquery.min.js') }}"></script>
<!-- Bootstrap 3.3.7 -->
<script src="{{ asset('bootstrap/dist/js/bootstrap.min.js') }}"></script>
<!-- iCheck -->
<script src="{{ asset('plugins/iCheck/icheck.min.js') }}"></script>
<script>
  $(function () {
    $('input').iCheck({
      checkboxClass: 'icheckbox_square-blue',
      radioClass: 'iradio_square-blue',
      increaseArea: '20%' /* optional */
    });
  });
</script>
<script src="{{ asset('main/js/auth/login-vue.js') }}"></script>
</body>
</html>
