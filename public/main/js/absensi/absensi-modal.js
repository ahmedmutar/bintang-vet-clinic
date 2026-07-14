/**
 * Modal Absensi - dipanggil setelah login berhasil
 * Mengelola: kamera selfie, peta Leaflet, jam real-time, submit absensi
 */
(function () {
  let token = '';
  let baseUrl = '';
  let stream = null;
  let fotoBase64 = null;
  let latitude = null;
  let longitude = null;
  let alamat = '';
  let petaMap = null;
  let petaMarker = null;
  let jamInterval = null;
  let sudahFoto = false;
  let branchLat = null;
  let branchLng = null;
  let jarakOk = true;
  let radiusBypassed = false;

  // Dipanggil dari login-vue.js setelah login berhasil
  window.bukaModalAbsensi = function (authToken, url) {
    token = authToken;
    baseUrl = url;

    // Cek apakah hari ini sudah absen
    axios.get(baseUrl + '/api/absensi/cek-hari-ini', {
      headers: { Authorization: 'Bearer ' + token }
    }).then(function (res) {
      if (res.data.sudah_absen) {
        // Sudah absen masuk, langsung redirect dashboard
        window.location.href = baseUrl + '/';
      } else {
        // Simpan koordinat cabang untuk validasi jarak
        branchLat       = res.data.branch_latitude ? parseFloat(res.data.branch_latitude) : null;
        branchLng       = res.data.branch_longitude ? parseFloat(res.data.branch_longitude) : null;
        radiusBypassed  = res.data.is_radius_bypassed === true;
        // Belum absen, tampilkan modal
        inisialisasiModal();
        $('#modal-absensi').one('shown.bs.modal', function () {
          if (petaMap) petaMap.invalidateSize();
        });
        $('#modal-absensi').modal('show');
      }
    }).catch(function () {
      // Jika API gagal, tetap redirect dashboard
      window.location.href = baseUrl + '/';
    });
  };

  function inisialisasiModal() {
    mulaijam();
    mulaiBukaKamera();
    deteksiLokasi();
    muatShift();
    pasangEventListener();
  }

  // ── JAM REAL-TIME ─────────────────────────────────────────────
  function mulaijam() {
    const hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];

    function updateJam() {
      const now = new Date();
      const hh = String(now.getHours()).padStart(2, '0');
      const mm = String(now.getMinutes()).padStart(2, '0');
      const ss = String(now.getSeconds()).padStart(2, '0');
      $('#jam-absensi').text(hh + ':' + mm + ':' + ss);
      $('#tgl-absensi').text(
        hari[now.getDay()] + ', ' + now.getDate() + ' ' + bulan[now.getMonth()] + ' ' + now.getFullYear()
      );
    }
    updateJam();
    jamInterval = setInterval(updateJam, 1000);
  }

  // ── KAMERA ────────────────────────────────────────────────────
  function mulaiBukaKamera() {
    const video = document.getElementById('video-selfie');
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      $('#status-lokasi').html('<span class="text-warning"><i class="fa fa-exclamation-triangle"></i> Kamera tidak tersedia di browser ini.</span>');
      return;
    }
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false })
      .then(function (s) {
        stream = s;
        video.srcObject = s;
      })
      .catch(function () {
        $('#btn-ambil-foto').prop('disabled', true).text('Kamera tidak dapat diakses');
      });
  }

  function ambilFoto() {
    const video = document.getElementById('video-selfie');
    const canvas = document.getElementById('canvas-selfie');
    const preview = document.getElementById('preview-selfie');

    canvas.width = video.videoWidth || 640;
    canvas.height = video.videoHeight || 480;
    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
    fotoBase64 = canvas.toDataURL('image/jpeg', 0.8);

    preview.src = fotoBase64;
    preview.style.display = 'block';
    video.style.display = 'none';
    $('#btn-ambil-foto').hide();
    $('#btn-ulangi-foto').show();

    sudahFoto = true;
    cekSiapSubmit();
  }

  function ulangiKamera() {
    const video = document.getElementById('video-selfie');
    const preview = document.getElementById('preview-selfie');

    preview.style.display = 'none';
    video.style.display = 'block';
    $('#btn-ulangi-foto').hide();
    $('#btn-ambil-foto').show();

    fotoBase64 = null;
    sudahFoto = false;
    cekSiapSubmit();
  }

  // ── PETA & LOKASI ─────────────────────────────────────────────
  function deteksiLokasi() {
    if (!navigator.geolocation) {
      $('#status-lokasi').html('<span class="text-warning"><i class="fa fa-exclamation-triangle"></i> Geolocation tidak didukung browser ini.</span>');
      inisialisasiPetaDefault();
      return;
    }

    navigator.geolocation.getCurrentPosition(
      function (pos) {
        latitude = pos.coords.latitude;
        longitude = pos.coords.longitude;
        $('#status-lokasi').html(
          '<i class="fa fa-check text-success"></i> Lokasi terdeteksi: ' +
          latitude.toFixed(6) + ', ' + longitude.toFixed(6)
        );
        inisialisasiPeta(latitude, longitude);
        cekJarakKlinik();
        // Reverse geocode pakai Nominatim (gratis, tanpa API key)
        fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + latitude + '&lon=' + longitude)
          .then(function (r) { return r.json(); })
          .then(function (data) {
            alamat = data.display_name || '';
            if (alamat) {
              $('#status-lokasi').html(
                '<i class="fa fa-map-marker text-success"></i> ' + alamat.substring(0, 100) + '...'
              );
            }
          })
          .catch(function () {});
      },
      function () {
        $('#status-lokasi').html('<span class="text-warning"><i class="fa fa-exclamation-triangle"></i> Lokasi tidak dapat dideteksi. Absensi tetap bisa dilakukan.</span>');
        jarakOk = true;
        inisialisasiPetaDefault();
        cekSiapSubmit();
      },
      { timeout: 10000, enableHighAccuracy: true }
    );
  }

  function inisialisasiPeta(lat, lng) {
    if (petaMap) { petaMap.remove(); petaMap = null; }
    petaMap = L.map('peta-absensi').setView([lat, lng], 16);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(petaMap);
    petaMarker = L.marker([lat, lng]).addTo(petaMap).bindPopup('Lokasi Anda').openPopup();
    setTimeout(function () { if (petaMap) petaMap.invalidateSize(); }, 300);
  }

  function inisialisasiPetaDefault() {
    if (petaMap) return;
    petaMap = L.map('peta-absensi').setView([-6.2088, 106.8456], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(petaMap);
    setTimeout(function () { if (petaMap) petaMap.invalidateSize(); }, 300);
  }

  // ── JARAK KE KLINIK ───────────────────────────────────────────
  function hitungJarak(lat1, lon1, lat2, lon2) {
    var R = 6371000;
    var phi1 = lat1 * Math.PI / 180;
    var phi2 = lat2 * Math.PI / 180;
    var dphi = (lat2 - lat1) * Math.PI / 180;
    var dlambda = (lon2 - lon1) * Math.PI / 180;
    var a = Math.sin(dphi / 2) * Math.sin(dphi / 2) +
            Math.cos(phi1) * Math.cos(phi2) *
            Math.sin(dlambda / 2) * Math.sin(dlambda / 2);
    return Math.round(R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)));
  }

  function cekJarakKlinik() {
    if (!branchLat || !branchLng) {
      jarakOk = true;
      $('#status-jarak').hide();
      cekSiapSubmit();
      return;
    }

    var jarak = hitungJarak(latitude, longitude, branchLat, branchLng);

    if (radiusBypassed) {
      jarakOk = true;
      $('#status-jarak')
        .html('<i class="fa fa-info-circle" style="color:#3c8dbc;"></i> <strong style="color:#3c8dbc;">Jarak ke klinik: ' + jarak + ' meter</strong> &mdash; Validasi radius tidak berlaku untuk akun ini.')
        .show();
    } else if (jarak <= 500) {
      jarakOk = true;
      $('#status-jarak')
        .html('<i class="fa fa-check-circle text-success"></i> <strong style="color:#00a65a;">Jarak ke klinik: ' + jarak + ' meter</strong> &mdash; Dalam radius yang diizinkan.')
        .show();
    } else {
      jarakOk = false;
      $('#status-jarak')
        .html('<i class="fa fa-times-circle text-danger"></i> <strong style="color:#dd4b39;">Jarak ke klinik: ' + jarak + ' meter</strong> &mdash; Melebihi radius 500 meter. Absensi tidak dapat dilakukan.')
        .show();
    }
    cekSiapSubmit();
  }

  // ── SHIFT ─────────────────────────────────────────────────────
  function muatShift() {
    axios.get(baseUrl + '/api/shift/dropdown', {
      headers: { Authorization: 'Bearer ' + token }
    }).then(function (res) {
      const shifts = res.data;
      const select = $('#select-shift');
      select.empty();
      if (shifts.length === 0) {
        select.append('<option value="">-- Belum ada shift aktif --</option>');
        return;
      }
      select.append('<option value="">-- Pilih Shift --</option>');
      const now = new Date();
      const jamSekarang = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
      let autoSelect = null;
      shifts.forEach(function (s) {
        const opt = $('<option>').val(s.id).text(s.nama_shift + ' (' + s.jam_masuk + ' - ' + s.jam_keluar + ')');
        opt.data('jam_masuk', s.jam_masuk).data('jam_keluar', s.jam_keluar);
        select.append(opt);
        // Auto-select shift yang sedang berjalan
        if (jamSekarang >= s.jam_masuk.substring(0, 5) && jamSekarang <= s.jam_keluar.substring(0, 5)) {
          autoSelect = s.id;
        }
      });
      if (autoSelect) select.val(autoSelect);
      select.on('change', cekSiapSubmit);
      cekSiapSubmit();
    }).catch(function () {
      $('#select-shift').html('<option value="">-- Gagal memuat shift --</option>');
    });
  }

  // ── VALIDASI SUBMIT ───────────────────────────────────────────
  function cekSiapSubmit() {
    const shiftDipilih = $('#select-shift').val();
    $('#btn-submit-absensi').prop('disabled', !sudahFoto || !shiftDipilih || !jarakOk);
  }

  // ── SUBMIT ABSENSI ────────────────────────────────────────────
  function submitAbsensi() {
    const shiftId = $('#select-shift').val();
    if (!shiftId || !fotoBase64) return;

    $('#btn-submit-absensi').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');
    $('#alert-absensi').hide();

    const payload = {
      shift_id: parseInt(shiftId),
      foto: fotoBase64,
      latitude: latitude,
      longitude: longitude,
      alamat: alamat,
      keterangan: $('#input-keterangan').val(),
    };

    axios.post(baseUrl + '/api/absensi/masuk', payload, {
      headers: { Authorization: 'Bearer ' + token }
    }).then(function (res) {
      tampilAlertModal('success', res.data.message);
      hentikanStream();
      clearInterval(jamInterval);
      setTimeout(function () {
        $('#modal-absensi').modal('hide');
        window.location.href = baseUrl + '/';
      }, 1500);
    }).catch(function (err) {
      const msg = err.response?.data?.errors?.[0] || err.response?.data?.message || 'Terjadi kesalahan.';
      tampilAlertModal('danger', msg);
      $('#btn-submit-absensi').prop('disabled', false).html('<i class="fa fa-check"></i> Absen Sekarang');
    });
  }

  // ── SKIP ──────────────────────────────────────────────────────
  function skipAbsensi() {
    hentikanStream();
    clearInterval(jamInterval);
    $('#modal-absensi').modal('hide');
    window.location.href = baseUrl + '/';
  }

  function hentikanStream() {
    if (stream) {
      stream.getTracks().forEach(function (t) { t.stop(); });
      stream = null;
    }
    if (petaMap) { petaMap.remove(); petaMap = null; }
  }

  function tampilAlertModal(type, msg) {
    $('#alert-absensi')
      .removeClass('alert-success alert-danger alert-warning')
      .addClass('alert-' + type)
      .text(msg)
      .show();
  }

  // ── EVENT LISTENERS ───────────────────────────────────────────
  function pasangEventListener() {
    $('#btn-ambil-foto').off('click').on('click', ambilFoto);
    $('#btn-ulangi-foto').off('click').on('click', ulangiKamera);
    $('#btn-submit-absensi').off('click').on('click', submitAbsensi);
    $('#btn-skip-absensi').off('click').on('click', skipAbsensi);
  }
})();
