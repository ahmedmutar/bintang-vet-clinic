/**
 * Absen Keluar (Pulang)
 * - Cek status absensi hari ini setelah halaman load
 * - Tampilkan banner jika sudah masuk tapi belum keluar
 * - Modal selfie untuk konfirmasi pulang
 */
(function () {
  var stream        = null;
  var fotoBase64    = null;
  var sudahFoto     = false;
  var jamInterval   = null;
  var shiftJamKeluar = null; // "HH:MM:SS" dari server
  var perluAlasan   = false; // true jika jam sekarang > jam shift + 1 jam
  var pulangDini    = false; // true jika jam sekarang < jam shift pulang

  $(document).ready(function () {
    var stored = localStorage.getItem('vet-clinic');
    if (!stored) return;

    var auth    = JSON.parse(stored);
    var token   = auth.token;
    var baseUrl = $('.baseUrl').val();

    // Cek status absensi hari ini
    $.ajax({
      url    : baseUrl + '/api/absensi/cek-hari-ini',
      headers: { 'Authorization': 'Bearer ' + token },
      success: function (res) {
        // Sudah absen masuk tapi belum absen keluar → tampilkan banner
        if (res.sudah_absen && !res.sudah_keluar) {
          $('#banner-absen-keluar').css('display', 'flex');
          shiftJamKeluar = res.shift_jam_keluar || null;
        }
      },
      error: function () {}
    });

    // Buka modal absen keluar dari banner
    $('#btn-buka-modal-keluar').on('click', function () {
      bukaModalKeluar();
    });

    // Batal — tutup modal, hentikan kamera
    $('#btn-batal-keluar').on('click', function () {
      tutupModalKeluar();
    });

    $('#modal-absen-keluar').on('hidden.bs.modal', function () {
      tutupModalKeluar();
    });

    // Ambil foto
    $('#btn-ambil-foto-keluar').on('click', function () {
      ambilFoto();
    });

    // Ulangi foto
    $('#btn-ulangi-foto-keluar').on('click', function () {
      ulangiKamera();
    });

    // Alasan textarea berubah
    $('#input-alasan-keluar').on('input', function () {
      cekSiapSubmit();
    });

    // Submit absen keluar
    $('#btn-submit-keluar').on('click', function () {
      submitKeluar(token, baseUrl);
    });
  });

  // ── BUKA MODAL ────────────────────────────────────────────────
  function bukaModalKeluar() {
    fotoBase64  = null;
    sudahFoto   = false;
    perluAlasan = false;
    pulangDini  = false;

    // Reset tampilan kamera
    $('#video-keluar').show();
    $('#preview-keluar').hide().attr('src', '');
    $('#btn-ambil-foto-keluar').show();
    $('#btn-ulangi-foto-keluar').hide();
    $('#btn-submit-keluar').prop('disabled', true);
    $('#alert-absen-keluar').hide();

    // Reset alasan
    $('#input-alasan-keluar').val('');
    $('#form-alasan-keluar').hide();

    // Cek kondisi berdasarkan jam shift
    if (shiftJamKeluar) {
      var now    = new Date();
      var parts  = shiftJamKeluar.split(':');
      var shiftEnd = new Date();
      shiftEnd.setHours(parseInt(parts[0], 10), parseInt(parts[1], 10), parseInt(parts[2] || 0, 10), 0);

      if (now < shiftEnd) {
        // Pulang sebelum jam shift — tandai dan tampilkan peringatan
        pulangDini = true;
        tampilAlertKeluar('warning',
          '⚠ Absensi Tidak Sesuai (Berpotensi Potong Gaji) — Anda pulang sebelum jam shift selesai (' +
          shiftJamKeluar.substring(0, 5) + ').');
      } else {
        var batas = new Date(shiftEnd.getTime() + 60 * 60 * 1000); // +1 jam
        if (now > batas) {
          // Pulang lebih dari 1 jam setelah shift — tampilkan form alasan
          perluAlasan = true;
          $('#form-alasan-keluar').show();
        }
      }
    }

    mulaiJam();
    bukaKamera();
    $('#modal-absen-keluar').modal('show');
  }

  function tutupModalKeluar() {
    hentikanStream();
    clearInterval(jamInterval);
    $('#modal-absen-keluar').modal('hide');
  }

  // ── JAM REAL-TIME ─────────────────────────────────────────────
  function mulaiJam() {
    clearInterval(jamInterval);
    function update() {
      var now = new Date();
      var hh  = String(now.getHours()).padStart(2, '0');
      var mm  = String(now.getMinutes()).padStart(2, '0');
      var ss  = String(now.getSeconds()).padStart(2, '0');
      $('#jam-absen-keluar').text(hh + ':' + mm + ':' + ss);
    }
    update();
    jamInterval = setInterval(update, 1000);
  }

  // ── KAMERA ────────────────────────────────────────────────────
  function bukaKamera() {
    var video = document.getElementById('video-keluar');
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      tampilAlertKeluar('warning', 'Kamera tidak tersedia di browser ini.');
      return;
    }
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false })
      .then(function (s) {
        stream = s;
        video.srcObject = s;
      })
      .catch(function () {
        tampilAlertKeluar('warning', 'Kamera tidak dapat diakses. Pastikan izin kamera diberikan.');
        $('#btn-ambil-foto-keluar').prop('disabled', true);
      });
  }

  function ambilFoto() {
    var video   = document.getElementById('video-keluar');
    var canvas  = document.getElementById('canvas-keluar');
    var preview = document.getElementById('preview-keluar');

    canvas.width  = video.videoWidth  || 640;
    canvas.height = video.videoHeight || 480;
    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
    fotoBase64 = canvas.toDataURL('image/jpeg', 0.8);

    preview.src              = fotoBase64;
    preview.style.display    = 'block';
    video.style.display      = 'none';
    $('#btn-ambil-foto-keluar').hide();
    $('#btn-ulangi-foto-keluar').show();

    sudahFoto = true;
    cekSiapSubmit();
  }

  function ulangiKamera() {
    var video   = document.getElementById('video-keluar');
    var preview = document.getElementById('preview-keluar');

    preview.style.display = 'none';
    video.style.display   = 'block';
    $('#btn-ulangi-foto-keluar').hide();
    $('#btn-ambil-foto-keluar').show();

    fotoBase64 = null;
    sudahFoto  = false;
    cekSiapSubmit();
  }

  function hentikanStream() {
    if (stream) {
      stream.getTracks().forEach(function (t) { t.stop(); });
      stream = null;
    }
  }

  // ── VALIDASI SUBMIT ───────────────────────────────────────────
  function cekSiapSubmit() {
    var alasanOk = !perluAlasan || ($('#input-alasan-keluar').val().trim().length > 0);
    $('#btn-submit-keluar').prop('disabled', !sudahFoto || !alasanOk);
  }

  // ── SUBMIT ────────────────────────────────────────────────────
  function submitKeluar(token, baseUrl) {
    if (!fotoBase64) return;

    var alasan = $('#input-alasan-keluar').val().trim();
    if (perluAlasan && !alasan) {
      tampilAlertKeluar('warning', 'Mohon isi alasan pulang terlambat terlebih dahulu.');
      return;
    }

    if (pulangDini) {
      var konfirmasi = confirm(
        'Status absensi Anda akan menjadi "Absensi Tidak Sesuai (Berpotensi Potong Gaji)" karena pulang sebelum jam shift selesai.\n\nApakah Anda tetap ingin konfirmasi pulang?'
      );
      if (!konfirmasi) return;
    }

    $('#btn-submit-keluar').prop('disabled', true)
      .html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');
    $('#alert-absen-keluar').hide();

    var payload = { foto: fotoBase64 };
    if (alasan) payload.alasan_terlambat_pulang = alasan;

    $.ajax({
      url        : baseUrl + '/api/absensi/keluar',
      type       : 'POST',
      contentType: 'application/json',
      headers    : { 'Authorization': 'Bearer ' + token },
      data       : JSON.stringify(payload),
      success    : function (res) {
        tampilAlertKeluar('success', res.message || 'Absen pulang berhasil!');
        $('#banner-absen-keluar').hide();
        hentikanStream();
        clearInterval(jamInterval);
        setTimeout(function () {
          $('#modal-absen-keluar').modal('hide');
        }, 1500);
      },
      error      : function (err) {
        var msg = 'Terjadi kesalahan.';
        if (err.responseJSON && err.responseJSON.message) {
          msg = err.responseJSON.message;
        }
        // Jika server minta alasan (perlu_alasan), tampilkan form alasan
        if (err.responseJSON && err.responseJSON.perlu_alasan) {
          perluAlasan = true;
          $('#form-alasan-keluar').show();
          cekSiapSubmit();
        }
        tampilAlertKeluar('danger', msg);
        $('#btn-submit-keluar').prop('disabled', false)
          .html('<i class="fa fa-check"></i> Konfirmasi Pulang');
      }
    });
  }

  function tampilAlertKeluar(type, msg) {
    $('#alert-absen-keluar')
      .removeClass('alert-success alert-danger alert-warning')
      .addClass('alert-' + type)
      .text(msg)
      .show();
  }
})();
