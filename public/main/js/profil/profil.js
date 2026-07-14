$(document).ready(function() {
  const fullname_profile = fullname;
  const role_profile = role.toUpperCase();

  let isValidUsername = false;
	let isValidTempatLahir = false;
  let isValidTanggalLahir = false;
  let isValidEmail = false;
  let isValidNoPonsel = false;
  let isValidAlamat = false;
  let getFotoProfile = null;

  let showOldPassword = false;
  let showNewPassword = false;
  let showConfirmPassword = false;

  loadProfil(); refreshVariable();
  $('.profile-username').text(fullname_profile);
  $('.profile-role').text(role_profile);

  $('#tanggalLahir').datepicker({
    autoclose: true,
    clearBtn: true,
    format: 'yyyy-mm-dd',
    todayHighlight: true,
  }).on('changeDate', function(e) {
    validationForm();
  });;

  $('#username').keyup(function () { validationForm(); });
  $('#tempatLahir').keyup(function () { validationForm(); });
  $('#email').keyup(function () { validationForm(); });
  $('#nomorponsel').keyup(function () { validationForm(); });
  $('#alamat').keyup(function () { validationForm(); });

  $('.btn-upload-foto').click(function() {
    $('#inputfileimg').val('');
    $('#modal-profile-upload-foto').modal('show');
    // $('.temp-img-upload-section img').attr("src", `${$('.baseUrl').val()}/assets/image/avatar-default.svg`);

    const setUrlImage = `${$('.baseUrl').val()}${getFotoProfile ? getFotoProfile : '/assets/image/avatar-default.svg'}`;
    $('.temp-img-upload-section img').attr("src", setUrlImage);
  });

  $('#btnSubmitProfil').click(function() {

    const datas = {
      username: $('#username').val(),
      tempat_lahir: $('#tempatLahir').val(),
      tanggal_lahir: $('#tanggalLahir').val(),
      email: $('#email').val(),
      nomor_ponsel: $('#nomorponsel').val(),
      alamat: $('#alamat').val()
    };

    $.ajax({
      url : $('.baseUrl').val() + '/api/user/profile',
      type: 'PUT',
      dataType: 'JSON',
      headers: { 'Authorization': `Bearer ${token}` },
      data: datas,
      beforeSend: function() { $('#loading-screen').show(); },
      success: function(resp) {

        $("#msg-box .modal-body").text('Berhasil Mengubah Data');
        $('#msg-box').modal('show');

        setTimeout(() => { refreshVariable(); loadProfil(); }, 1000);
      }, complete: function() { $('#loading-screen').hide(); }
      , error: function(err) {
        if (err.status === 422) {
          let errText = ''; refreshVariable();
          $.each(err.responseJSON.errors, function(idx, v) {
            errText += v + ((idx !== err.responseJSON.errors.length - 1) ? '<br/>' : '');
          });
          $('#beErr').append(errText); isBeErr = true;
        } else if (err.status == 401) {
          localStorage.removeItem('vet-clinic');
          location.href = $('.baseUrl').val() + '/masuk';
        }
      }
    });
  });

  $('#btnSubmitUploadImage').click(function() {
    let image = $('#inputfileimg').prop('files')[0];

    const fd = new FormData();
    fd.append('file', image);

    $.ajax({
      url : $('.baseUrl').val() + '/api/user/upload-image',
      type: 'POST',
      dataType: 'JSON',
      headers: { 'Authorization': `Bearer ${token}` },
      data: fd, contentType: false, cache: false,
      processData: false,
      beforeSend: function() { $('#loading-screen').show(); },
      success: function(resp) {

        $("#msg-box .modal-body").text('Berhasil Menambah Foto');
        $('#msg-box').modal('show');

        setTimeout(() => {
          $('#modal-profile-upload-foto').modal('toggle');
          refreshVariable(); loadProfil();
        }, 1000);
      }, complete: function() { $('#loading-screen').hide(); }
      , error: function(err) {
        // if (err.status === 422) {
        //   let errText = ''; $('#beErr').empty(); $('#btnSubmitUploadImage').attr('disabled', true);
        //   $.each(err.responseJSON.errors, function(idx, v) {
        //     errText += v + ((idx !== err.responseJSON.errors.length - 1) ? '<br/>' : '');
        //   });
        //   $('#beErr').append(errText); isBeErr = true;
        // } else 
        if (err.status == 401) {
          localStorage.removeItem('vet-clinic');
          location.href = $('.baseUrl').val() + '/masuk';
        }
      }
    });

  });

  $('#inputfileimg').change(function() {
    const file = this.files[0];

    if(file) {
      const reader = new FileReader();

      reader.onload = function(event) {
        $('.temp-img-upload-section img').attr("src", this.result);
      };

      reader.readAsDataURL(file);
    }
  });

  $('#toggleOldPassword').click(function() {
    showOldPassword = !showOldPassword;
    $('#oldPassword').attr('type', showOldPassword ? 'text' : 'password');
    $(this).toggleClass('glyphicon-eye-open glyphicon-eye-close');
  });

  $('#toggleNewPassword').click(function() {
    showNewPassword = !showNewPassword;
    $('#newPassword').attr('type', showNewPassword ? 'text' : 'password');
    $(this).toggleClass('glyphicon-eye-open glyphicon-eye-close');
  });

  $('#toggleConfirmPassword').click(function() {
    showConfirmPassword = !showConfirmPassword;
    $('#confirmPassword').attr('type', showConfirmPassword ? 'text' : 'password');
    $(this).toggleClass('glyphicon-eye-open glyphicon-eye-close');
  });

  $('#oldPassword, #newPassword, #confirmPassword').keyup(function() { validationPasswordForm(); });

  $('#btnGantiPassword').click(function() {
    $('#beErrPassword').empty();

    $.ajax({
      url : $('.baseUrl').val() + '/api/user/change-password',
      type: 'PUT',
      dataType: 'JSON',
      headers: { 'Authorization': `Bearer ${token}` },
      data: {
        old_password:     $('#oldPassword').val(),
        new_password:     $('#newPassword').val(),
        confirm_password: $('#confirmPassword').val(),
      },
      beforeSend: function() { $('#loading-screen').show(); },
      success: function() {
        $("#msg-box .modal-body").text('Berhasil mengubah Password. Anda akan diarahkan ke halaman login.');
        $('#msg-box').modal('show');
        refreshPasswordForm();
        setTimeout(function() {
          localStorage.removeItem('vet-clinic');
          location.href = $('.baseUrl').val() + '/masuk';
        }, 2000);
      },
      complete: function() { $('#loading-screen').hide(); },
      error: function(err) {
        if (err.status === 422) {
          $('#beErrPassword').empty();
          $.each(err.responseJSON.errors, function(idx, v) {
            if (idx > 0) $('#beErrPassword').append($('<br>'));
            $('#beErrPassword').append($('<span>').text(v));
          });
        } else if (err.status == 401) {
          localStorage.removeItem('vet-clinic');
          location.href = $('.baseUrl').val() + '/masuk';
        }
      }
    });
  });

  function refreshPasswordForm() {
    $('#oldPassword').val(''); $('#newPassword').val(''); $('#confirmPassword').val('');
    $('#oldPasswordErr').text(''); $('#newPasswordErr').text(''); $('#confirmPasswordErr').text('');
    $('#beErrPassword').empty();
    $('#btnGantiPassword').attr('disabled', true);
  }

  function validationPasswordForm() {
    let valid = true;

    if (!$('#oldPassword').val()) {
      $('#oldPasswordErr').text('Password lama harus di isi'); valid = false;
    } else {
      $('#oldPasswordErr').text('');
    }

    if (!$('#newPassword').val()) {
      $('#newPasswordErr').text('Password baru harus di isi'); valid = false;
    } else {
      $('#newPasswordErr').text('');
    }

    if (!$('#confirmPassword').val()) {
      $('#confirmPasswordErr').text('Konfirmasi password harus di isi'); valid = false;
    } else if ($('#confirmPassword').val() !== $('#newPassword').val()) {
      $('#confirmPasswordErr').text('Konfirmasi password tidak sama dengan password baru'); valid = false;
    } else {
      $('#confirmPasswordErr').text('');
    }

    $('#btnGantiPassword').attr('disabled', !valid);
  }

  refreshPasswordForm();

  function loadProfil() {
    $.ajax({
			url     : $('.baseUrl').val() + '/api/user/profile',
			headers : { 'Authorization': `Bearer ${token}` },
			type    : 'GET',
			beforeSend: function() { $('#loading-screen').show(); },
			success: function(data) {

        $('#username').val(data.username);
        $('#tempatLahir').val(data.birth_place),
        $('#tanggalLahir').val(data.birthdate);
        // $('#tanggalLahir').datepicker('setDate', data.birthdate);
        $('#email').val(data.email);
        $('#nomorponsel').val(data.phone_number);
        $('#alamat').val(data.address);

        replaceDataLocalUser(data);
			}, complete: function() { $('#loading-screen').hide(); },
			error: function(err) {
				if (err.status == 401) {
					localStorage.removeItem('vet-clinic');
					location.href = $('.baseUrl').val() + '/masuk';
				}
			}
		});
  }

  function replaceDataLocalUser(data) {
    let getExistingLocalData = localStorage.getItem('vet-clinic');
    getExistingLocalData = JSON.parse(getExistingLocalData);

    getFotoProfile = data.image_profile;
    const setUrlImage = `${$('.baseUrl').val()}${getFotoProfile ? getFotoProfile : '/assets/image/avatar-default.svg'}`;

    $('.username-txt').text(data.username); // set username on header
    $('.box-profile img').attr("src", setUrlImage);
    $('.image-header').attr("src", setUrlImage);

    localStorage.setItem('vet-clinic', JSON.stringify({
      fullname: getExistingLocalData.fullname,
      username: data.username,
      email: getExistingLocalData.email,
      role: getExistingLocalData.role,
      image_profile: getFotoProfile,
      token: getExistingLocalData.token,
      user_id: getExistingLocalData.user_id,
      branch_name: getExistingLocalData.branch_name
    }));
  }

  function refreshVariable() {
    $('#btnSubmitProfil').attr('disabled', true);
    $('#usernameErr1').text(''); isValidUsername = false;
    $('#tempatLahirErr1').text(''); isValidTempatLahir = false;
    $('#tanggalLahirErr1').text(''); isValidTanggalLahir = false;
    $('#emailErr1').text(''); isValidEmail = false;
    $('#noponselErr1').text(''); isValidNoPonsel = false;
    $('#alamatErr1').text(''); isValidAlamat = false;
    $('#beErr').empty(); isBeErr = false;
  }

  function validationForm() {
    $('#btnSubmitProfil').attr('disabled', false);
    if (!$('#username').val()) {
			$('#usernameErr1').text('Username harus di isi'); isValidUsername = false;
		} else { 
			$('#usernameErr1').text(''); isValidUsername = true;
		}

    if (!$('#tempatLahir').val()) {
			$('#tempatLahirErr1').text('Tempat lahir harus di isi'); isValidTempatLahir = false;
		} else { 
			$('#tempatLahirErr1').text(''); isValidTempatLahir = true;
		}

    if (!$('#tanggalLahir').val()) {
			$('#tanggalLahirErr1').text('Tanggal lahir harus di isi'); isValidTanggalLahir = false;
		} else { 
			$('#tanggalLahirErr1').text(''); isValidTanggalLahir = true;
		}

    if (!$('#email').val()) {
			$('#emailErr1').text('Email harus di isi'); isValidEmail = false;
		} else { 
			$('#emailErr1').text(''); isValidEmail = true;
		}

    if (!$('#nomorponsel').val()) {
			$('#noponselErr1').text('No ponsel harus di isi'); isValidNoPonsel = false;
		} else { 
			$('#noponselErr1').text(''); isValidNoPonsel = true;
		}

    if (!$('#alamat').val()) {
			$('#alamatErr1').text('Alamat harus di isi'); isValidAlamat = false;
		} else { 
			$('#alamatErr1').text(''); isValidAlamat = true;
		}

    $('#beErr').empty(); isBeErr = false;

    if (!isValidUsername || !isValidTempatLahir || !isValidTanggalLahir 
      || !isValidEmail || !isValidNoPonsel || !isValidAlamat || isBeErr) {
      $('#btnSubmitProfil').attr('disabled', true);
    } else {
      $('#btnSubmitProfil').attr('disabled', false);
    }

  }


});
