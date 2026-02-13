<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>404 Page</title>

  <link rel="shortcut icon" type="image/jpg" href="{{ asset('assets/image/astra.jpeg') }}">
  <link rel="stylesheet" href="{{ asset('bower_components/font-awesome/css/font-awesome.min.css') }}">
  <!-- Bootstrap 3.3.7 -->
  <link rel="stylesheet" href="{{ asset('bootstrap/dist/css/bootstrap.min.css') }}">
  <!-- Theme style -->
  <link rel="stylesheet" href="{{ asset('dist/css/AdminLTE.css') }}">
  <link rel="stylesheet" href="{{ asset('dist/css/skins/_all-skins.css') }}">
  <link rel="stylesheet" type='text/css' href="{{ asset('main/css/global.css') }}">

   <!-- Google Font -->
   <link rel="stylesheet"
   href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">

  <!-- jQuery 3 -->
  <script src="{{ asset('bower_components/jquery/dist/jquery.min.js') }}"></script>
  <!-- Bootstrap 3.3.7 -->
  <script src="{{ asset('bootstrap/dist/js/bootstrap.min.js') }}"></script>
</head>
<style>
  .container-404 {
    /* display: flex; align-items: center; justify-content: center; height: 100%; */
    margin: auto;
    display: block;
    text-align: center;
    padding-top: 15%;
    height: 100%;
  }
  .container-404 .text-404 {
    font-size: 35px;
  }
  .container-404 .desc {
    font-size: 20px;
    margin-bottom: 10px;
  }
</style>
<body>
  <input class="baseUrl" type="hidden" value="{{ url('/') }}"/>
  <div class="container-404">
    <div class="text-404">404</div>
    <div class="desc"><i class="fa fa-warning text-yellow"></i> Halaman yang anda tuju tidak ditemukan.</div>
    <button class="btn btn-primary" id="backToHome">
      <i class="fa fa-home" aria-hidden="true"></i> Halaman Utama
    </button>
  </div>
</body>
<script>
  $(document).ready(function() {
    $('#backToHome').click(function() {
      window.location.href = $('.baseUrl').val();
    })
  });
</script>
</html>
