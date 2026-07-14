const radiusExceptionApp = new Vue({
  el: '#radius-exception-app',
  data: {
    list: [],
    inputUsername: '',
    inputError: '',
    loading: false,
    loadingTambah: false,
    showAlert: false,
    isSuccess: false,
    message: '',
    token: '',
    baseUrl: '',
  },
  mounted() {
    const stored = localStorage.getItem('vet-clinic');
    if (!stored) return;
    const auth = JSON.parse(stored);
    this.token   = auth.token;
    this.baseUrl = document.querySelector('.baseUrl').value;

    const blockedRoles = ['dokter', 'resepsionis', 'paramedis'];
    if (blockedRoles.includes(auth.role.toLowerCase())) {
      window.location.href = this.baseUrl + '/unauthorized';
      return;
    }

    this.initSelect2();
    this.loadList();
  },
  methods: {
    initSelect2() {
      const vm = this;
      let optKaryawan = `<option value=''>Pilih Nama Karyawan - Cabang</option>`;

      $.ajax({
        url    : this.baseUrl + '/api/user',
        headers: { 'Authorization': 'Bearer ' + this.token },
        type   : 'GET',
        beforeSend: function () { $('#loading-screen').show(); },
        success: function (data) {
          if (data.length) {
            for (let i = 0; i < data.length; i++) {
              optKaryawan += `<option value="${data[i].username}">${data[i].fullname} - ${data[i].branch_name}</option>`;
            }
          }
          $('#select-username').append(optKaryawan);
          $('#select-username').select2();
          $('#select-username').on('change', function () {
            vm.inputUsername = $(this).val() || '';
            vm.inputError    = '';
          });
        },
        complete: function () { $('#loading-screen').hide(); },
        error: function (err) {
          if (err.status === 401) {
            localStorage.removeItem('vet-clinic');
            location.href = vm.baseUrl + '/masuk';
          } else {
            vm.tampilAlert(false, 'Gagal memuat daftar karyawan.');
          }
        },
      });
    },
    loadList() {
      this.loading = true;
      axios.get(this.baseUrl + '/api/absensi-radius-exception', {
        headers: { Authorization: 'Bearer ' + this.token },
      }).then(res => {
        this.list = res.data;
      }).catch(() => {
        this.tampilAlert(false, 'Gagal memuat data.');
      }).finally(() => { this.loading = false; });
    },
    tambahException() {
      this.inputError = '';
      const username = this.inputUsername;
      if (!username) { this.inputError = 'Pilih username terlebih dahulu.'; return; }

      this.loadingTambah = true;
      axios.post(this.baseUrl + '/api/absensi-radius-exception', { username }, {
        headers: { Authorization: 'Bearer ' + this.token },
      }).then(res => {
        $('#select-username').val('').trigger('change.select2');
        this.inputUsername = '';
        this.tampilAlert(true, res.data.message);
        this.loadList();
      }).catch(err => {
        const msg = err.response?.data?.errors?.[0] || err.response?.data?.message || 'Terjadi kesalahan.';
        this.inputError = msg;
      }).finally(() => { this.loadingTambah = false; });
    },
    hapus(item) {
      if (!confirm('Hapus username "' + item.username + '" dari daftar pengecualian?')) return;
      axios.delete(this.baseUrl + '/api/absensi-radius-exception', {
        headers: { Authorization: 'Bearer ' + this.token },
        params: { id: item.id },
      }).then(res => {
        this.tampilAlert(true, res.data.message);
        this.loadList();
      }).catch(err => {
        const msg = err.response?.data?.message || 'Gagal menghapus data.';
        this.tampilAlert(false, msg);
      });
    },
    formatTanggal(dateStr) {
      if (!dateStr) return '-';
      const d = new Date(dateStr);
      return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
    },
    tampilAlert(success, msg) {
      this.isSuccess = success;
      this.message   = msg;
      this.showAlert = true;
      setTimeout(() => { this.showAlert = false; }, 3000);
    },
  },
});
