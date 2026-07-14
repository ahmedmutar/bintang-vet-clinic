@extends('layout.master')

@section('content')
<div class="row">
  <div class="col-md-3">
    <!-- Profile Image -->
    <div class="box box-info">
      <div class="box-body box-profile">
        <img class="profile-user-img img-responsive img-circle">
        <h3 class="profile-username text-center">-</h3>
        <p class="profile-role text-muted text-center">-</p>
        <div class="text-center">
          <button class="btn btn-info text-center btn-upload-foto">Upload Foto</button>
        </div>
      </div>
      <!-- /.box-body -->
    </div>
  </div>
  <div class="col-md-9">
    <div class="box box-info">    
      <div class="box-body">
        <div class="form-group">
          <label for="username">Username</label>
          <input type="text" class="form-control" id="username" placeholder="username">
          <div id="usernameErr1" class="validate-error"></div>
        </div>
        <div class="form-group">
          <label for="tempatLahir">Tempat Lahir</label>
          <input type="text" class="form-control" id="tempatLahir" placeholder="tempat lahir">
          <div id="tempatLahirErr1" class="validate-error"></div>
        </div>
        <div class="form-group">
          <label for="tanggalLahir">Tanggal Lahir</label>
          <div class="input-group date">
            <div class="input-group-addon">
              <i class="fa fa-calendar"></i>
            </div>
            <input type="text" class="form-control" id="tanggalLahir" placeholder="yyyy-mm-dd" autocomplete="off">
          </div>
          <div id="tanggalLahirErr1" class="validate-error"></div>
        </div>
        <div class="form-group">
          <label for="email">Email</label>
          <input type="text" class="form-control" id="email" placeholder="contoh: budi@gmail.com">
          <div id="emailErr1" class="validate-error"></div>
        </div>
        <div class="form-group">
          <label for="nomor ponsel">Nomor Ponsel</label>
          <input type="number" class="form-control" id="nomorponsel" placeholder="nomor ponsel">
          <div id="noponselErr1" class="validate-error"></div>
        </div>
        <div class="form-group">
          <label for="alamat">Alamat</label>
          <textarea class="form-control" rows="3" cols="3" id="alamat" placeholder="alamat"></textarea>
          <div id="alamatErr1" class="validate-error"></div>
        </div>
        <div class="form-group">
          <div id="beErr" class="validate-error"></div>
        </div>
      </div>
      <div class="box-footer">
        <button class="btn btn-info" id="btnSubmitProfil">Simpan</button>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="box box-info">
      <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-lock"></i> Ganti Password</h3>
      </div>
      <div class="box-body">
        <div class="form-group">
          <label for="oldPassword">Password Lama</label>
          <div class="p-relative">
            <input type="password" class="form-control p-right-42px" id="oldPassword" placeholder="Masukkan password lama">
            <span id="toggleOldPassword" class="glyphicon icon-password glyphicon-eye-open"></span>
          </div>
          <div id="oldPasswordErr" class="validate-error"></div>
        </div>
        <div class="form-group">
          <label for="newPassword">Password Baru</label>
          <div class="p-relative">
            <input type="password" class="form-control p-right-42px" id="newPassword" placeholder="Min. 8 karakter, huruf besar, kecil, angka, simbol">
            <span id="toggleNewPassword" class="glyphicon icon-password glyphicon-eye-open"></span>
          </div>
          <div id="newPasswordErr" class="validate-error"></div>
        </div>
        <div class="form-group">
          <label for="confirmPassword">Konfirmasi Password Baru</label>
          <div class="p-relative">
            <input type="password" class="form-control p-right-42px" id="confirmPassword" placeholder="Ulangi password baru">
            <span id="toggleConfirmPassword" class="glyphicon icon-password glyphicon-eye-open"></span>
          </div>
          <div id="confirmPasswordErr" class="validate-error"></div>
        </div>
        <div class="form-group">
          <div id="beErrPassword" class="validate-error"></div>
        </div>
      </div>
      <div class="box-footer">
        <button class="btn btn-info" id="btnGantiPassword">Ganti Password</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modal-profile-upload-foto">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
        <div class="temp-img-upload-section">
          <img class="profile-user-img img-responsive img-circle">
        </div>
        <div class="input-file-section">
          <input type="file" id="inputfileimg">
        </div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-primary" id="btnSubmitUploadImage">Simpan</button>
			</div>
		</div>
		<!-- /.modal-content -->
	</div>
	<!-- /.modal-dialog -->
</div>

@component('layout.message-box') @endcomponent

@endsection

@section('script-content')
  <script src="{{ asset('main/js/profil/profil.js') }}"></script>
  <script src="{{ asset('bower_components/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js') }}"></script>
@endsection
@section('css-content')
  <link rel="stylesheet" type='text/css' href="{{ asset('main/css/profil.css') }}">
  <link rel="stylesheet" href="{{ asset('bower_components/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css') }}">
@endsection
@section('vue-content')@endsection