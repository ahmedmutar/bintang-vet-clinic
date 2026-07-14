$(document).ready(function () {
  const baseUrl = $('#baseUrl').val();
  let currentEmail = '';
  let currentOtp   = '';
  let countdownTimer = null;
  let showNewPwd     = false;
  let showConfirmPwd = false;

  // ─── OTP digit inputs ────────────────────────────────────────────────────
  $('.otp-digit').on('input', function () {
    const val = $(this).val().replace(/[^0-9]/g, '');
    $(this).val(val);

    if (val && $(this).data('idx') < 5) {
      $('.otp-digit[data-idx="' + ($(this).data('idx') + 1) + '"]').focus();
    }

    toggleVerifyBtn();
  });

  $('.otp-digit').on('keydown', function (e) {
    if (e.key === 'Backspace' && !$(this).val() && $(this).data('idx') > 0) {
      $('.otp-digit[data-idx="' + ($(this).data('idx') - 1) + '"]').focus().val('');
      toggleVerifyBtn();
    }
  });

  function toggleVerifyBtn() {
    const otp = getOtpValue();
    $('#btnVerifikasiOtp').prop('disabled', otp.length !== 6);
  }

  function getOtpValue() {
    let otp = '';
    $('.otp-digit').each(function () { otp += $(this).val(); });
    return otp;
  }

  function clearOtpInputs() {
    $('.otp-digit').val('');
    $('#beErr2').empty();
    $('#btnVerifikasiOtp').prop('disabled', true);
    $('.otp-digit[data-idx="0"]').focus();
  }

  // ─── Password toggle ─────────────────────────────────────────────────────
  $('#toggleNewPwd').click(function () {
    showNewPwd = !showNewPwd;
    $('#newPassword').attr('type', showNewPwd ? 'text' : 'password');
    $(this).toggleClass('glyphicon-eye-open glyphicon-eye-close');
  });

  $('#toggleConfirmPwd').click(function () {
    showConfirmPwd = !showConfirmPwd;
    $('#confirmPassword').attr('type', showConfirmPwd ? 'text' : 'password');
    $(this).toggleClass('glyphicon-eye-open glyphicon-eye-close');
  });

  $('#newPassword, #confirmPassword').keyup(function () { validatePasswordForm(); });

  // ─── Step navigation helpers ─────────────────────────────────────────────
  function goToStep(n) {
    $('#step1, #step2, #step3, #stepSuccess').hide();
    $('#step' + n).show();

    for (let i = 1; i <= 3; i++) {
      const $item = $('#stepItem' + i);
      $item.removeClass('active done');
      if (i < n)       $item.addClass('done');
      else if (i === n) $item.addClass('active');
    }
  }

  function showSuccess() {
    $('#step1, #step2, #step3').hide();
    $('#stepIndicator').hide();
    $('#stepSuccess').show();
  }

  // ─── Step 1: Kirim OTP ───────────────────────────────────────────────────
  $('#btnKirimOtp').click(function () {
    const email = $('#inputEmail').val().trim();
    $('#emailErr').text('');
    $('#beErr1').empty();

    if (!email) {
      $('#emailErr').text('Email harus diisi');
      return;
    }

    $.ajax({
      url       : baseUrl + '/api/lupa-password/kirim-otp',
      type      : 'POST',
      dataType  : 'JSON',
      data      : { email },
      beforeSend: function () { $('#loading-screen').show(); },
      success   : function () {
        currentEmail = email;
        $('#emailDisplay').text(email);
        clearOtpInputs();
        startCountdown();
        goToStep(2);
      },
      complete  : function () { $('#loading-screen').hide(); },
      error     : function (err) {
        if (err.status === 422 || err.status === 404) {
          $('#beErr1').empty();
          $.each(err.responseJSON.errors, function (idx, v) {
            if (idx > 0) $('#beErr1').append($('<br>'));
            $('#beErr1').append($('<span>').text(v));
          });
        }
      }
    });
  });

  // ─── Countdown & resend ──────────────────────────────────────────────────
  function startCountdown() {
    let seconds = 60;
    $('#btnResendOtp').prop('disabled', true);
    $('#countdown').text(seconds);

    clearInterval(countdownTimer);
    countdownTimer = setInterval(function () {
      seconds--;
      $('#countdown').text(seconds);
      if (seconds <= 0) {
        clearInterval(countdownTimer);
        $('#btnResendOtp').prop('disabled', false).html('Kirim ulang');
      }
    }, 1000);
  }

  $('#btnResendOtp').click(function () {
    clearOtpInputs();
    $('#beErr2').empty();

    $.ajax({
      url       : baseUrl + '/api/lupa-password/kirim-otp',
      type      : 'POST',
      dataType  : 'JSON',
      data      : { email: currentEmail },
      beforeSend: function () { $('#loading-screen').show(); },
      success   : function () {
        startCountdown();
      },
      complete  : function () { $('#loading-screen').hide(); },
      error     : function () {
        $('#beErr2').empty().append($('<span>').text('Gagal mengirim ulang OTP. Silakan coba lagi.'));
      }
    });
  });

  // ─── Step 2: Verifikasi OTP ──────────────────────────────────────────────
  $('#btnVerifikasiOtp').click(function () {
    const otp = getOtpValue();
    $('#beErr2').empty();

    $.ajax({
      url       : baseUrl + '/api/lupa-password/verifikasi-otp',
      type      : 'POST',
      dataType  : 'JSON',
      data      : { email: currentEmail, otp },
      beforeSend: function () { $('#loading-screen').show(); },
      success   : function () {
        currentOtp = otp;
        clearInterval(countdownTimer);
        $('#newPassword').val('');
        $('#confirmPassword').val('');
        $('#beErr3').empty();
        $('#btnResetPassword').prop('disabled', true);
        goToStep(3);
      },
      complete  : function () { $('#loading-screen').hide(); },
      error     : function (err) {
        if (err.responseJSON && err.responseJSON.errors) {
          $('#beErr2').empty();
          $.each(err.responseJSON.errors, function (idx, v) {
            if (idx > 0) $('#beErr2').append($('<br>'));
            $('#beErr2').append($('<span>').text(v));
          });
        }
        clearOtpInputs();
      }
    });
  });

  // ─── Step 3: Reset Password ──────────────────────────────────────────────
  function validatePasswordForm() {
    let valid = true;

    if (!$('#newPassword').val()) {
      $('#newPasswordErr').text('Password baru harus diisi'); valid = false;
    } else {
      $('#newPasswordErr').text('');
    }

    if (!$('#confirmPassword').val()) {
      $('#confirmPasswordErr').text('Konfirmasi password harus diisi'); valid = false;
    } else if ($('#confirmPassword').val() !== $('#newPassword').val()) {
      $('#confirmPasswordErr').text('Konfirmasi password tidak sama'); valid = false;
    } else {
      $('#confirmPasswordErr').text('');
    }

    $('#btnResetPassword').prop('disabled', !valid);
  }

  $('#btnResetPassword').click(function () {
    $('#beErr3').empty();

    $.ajax({
      url       : baseUrl + '/api/lupa-password/reset',
      type      : 'POST',
      dataType  : 'JSON',
      data      : {
        email            : currentEmail,
        otp              : currentOtp,
        new_password     : $('#newPassword').val(),
        confirm_password : $('#confirmPassword').val(),
      },
      beforeSend: function () { $('#loading-screen').show(); },
      success   : function () {
        showSuccess();
      },
      complete  : function () { $('#loading-screen').hide(); },
      error     : function (err) {
        if (err.responseJSON && err.responseJSON.errors) {
          $('#beErr3').empty();
          $.each(err.responseJSON.errors, function (idx, v) {
            if (idx > 0) $('#beErr3').append($('<br>'));
            $('#beErr3').append($('<span>').text(v));
          });
        }
      }
    });
  });

  // Enter key support
  $('#inputEmail').keydown(function (e) {
    if (e.key === 'Enter') $('#btnKirimOtp').click();
  });
  $('#newPassword, #confirmPassword').keydown(function (e) {
    if (e.key === 'Enter' && !$('#btnResetPassword').prop('disabled')) $('#btnResetPassword').click();
  });
});
