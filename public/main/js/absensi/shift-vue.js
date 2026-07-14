const shiftApp = new Vue({
  el: '#shift-app',
  data: {
    listShift: [],
    listCabang: [],
    form: {
      id: null,
      nama_shift: '',
      jam_masuk: '',
      jam_keluar: '',
      toleransi_menit: 15,
      id_cabang: '',
      for_role: '',
    },
    errors: {},
    filterBranchId: '',
    isEdit: false,
    loadingSimpan: false,
    showAlert: false,
    isSuccess: false,
    message: '',
    isAdmin: false,
    baseUrl: '',
    token: '',
    branchId: '',
    branchName: '',
    role: '',
  },
  mounted() {
    const stored = localStorage.getItem('vet-clinic');
    if (!stored) return;
    const auth = JSON.parse(stored);
    this.token      = auth.token;
    this.branchId   = auth.branch_id;
    this.branchName = auth.branch_name;
    this.role       = auth.role;
    this.baseUrl    = document.querySelector('.baseUrl').value;

    const blockedRoles = ['dokter', 'resepsionis', 'paramedis'];
    if (blockedRoles.includes(auth.role.toLowerCase())) {
      window.location.href = this.baseUrl + '/unauthorized';
      return;
    }

    this.isAdmin = auth.role === 'admin';

    this.form.id_cabang = auth.branch_id;

    this.loadCabang();
    this.loadShift();
  },
  computed: {
    listCabangForm() {
      return this.listCabang;
    },
  },
  methods: {
    loadShift() {
      let url = this.baseUrl + '/api/shift';
      if (this.filterBranchId) {
        url += '?branch_id=' + this.filterBranchId;
      }
      axios.get(url, { headers: { Authorization: 'Bearer ' + this.token } })
        .then(res => { this.listShift = res.data; })
        .catch(() => { this.tampilAlert(false, 'Gagal memuat data shift.'); });
    },
    loadCabang() {
      axios.get(this.baseUrl + '/api/cabang', { headers: { Authorization: 'Bearer ' + this.token } })
        .then(res => { this.listCabang = res.data; })
        .catch(() => {});
    },
    openModalTambah() {
      this.isEdit = false;
      this.errors = {};
      this.form = {
        id: null,
        nama_shift: '',
        jam_masuk: '',
        jam_keluar: '',
        toleransi_menit: 15,
        id_cabang: this.filterBranchId || this.branchId,
        for_role: '',
      };
      $('#modal-shift').modal('show');
    },
    openModalEdit(item) {
      this.isEdit = true;
      this.errors = {};
      this.form = {
        id:              item.id,
        nama_shift:      item.nama_shift,
        jam_masuk:       item.jam_masuk,
        jam_keluar:      item.jam_keluar,
        toleransi_menit: item.toleransi_menit,
        id_cabang:       item.branch_id,
        for_role:        item.for_role || '',
      };
      $('#modal-shift').modal('show');
    },
    simpanShift() {
      this.errors = {};
      if (!this.form.id_cabang)   this.errors.id_cabang  = 'Pilih cabang terlebih dahulu.';
      if (!this.form.nama_shift)  this.errors.nama_shift  = 'Nama shift wajib diisi.';
      if (!this.form.jam_masuk)   this.errors.jam_masuk   = 'Jam masuk wajib diisi.';
      if (!this.form.jam_keluar)  this.errors.jam_keluar  = 'Jam keluar wajib diisi.';
      if (Object.keys(this.errors).length > 0) return;

      this.loadingSimpan = true;
      const url     = this.baseUrl + '/api/shift';
      const headers = { Authorization: 'Bearer ' + this.token };
      const req     = this.isEdit
        ? axios.put(url, this.form, { headers })
        : axios.post(url, this.form, { headers });

      req.then(res => {
        $('#modal-shift').modal('hide');
        this.tampilAlert(true, res.data.message);
        this.loadShift();
      }).catch(err => {
        const msg = err.response?.data?.errors?.join(', ') || 'Terjadi kesalahan.';
        this.tampilAlert(false, msg);
      }).finally(() => { this.loadingSimpan = false; });
    },
    toggleStatus(item) {
      const label = item.status == 1 ? 'nonaktifkan' : 'aktifkan';
      if (!confirm('Apakah anda yakin ingin ' + label + ' shift "' + item.nama_shift + '"?')) return;
      axios.put(this.baseUrl + '/api/shift/toggle-status', { id: item.id }, { headers: { Authorization: 'Bearer ' + this.token } })
        .then(res => { this.tampilAlert(true, res.data.message); this.loadShift(); })
        .catch(() => { this.tampilAlert(false, 'Gagal mengubah status.'); });
    },
    hapusShift(item) {
      if (!confirm('Hapus shift "' + item.nama_shift + '"? Data yang sudah terpakai di absensi tidak akan terhapus.')) return;
      axios.delete(this.baseUrl + '/api/shift', {
        headers: { Authorization: 'Bearer ' + this.token },
        data: { id: item.id },
      }).then(res => {
        this.tampilAlert(true, res.data.message);
        this.loadShift();
      }).catch(err => {
        const msg = err.response?.data?.errors?.[0] || 'Gagal menghapus shift.';
        this.tampilAlert(false, msg);
      });
    },
    roleLabel(role) {
      const map = { admin: 'Admin', dokter: 'Dokter', resepsionis: 'Resepsionis', paramedis: 'Paramedis' };
      return map[role] || 'Semua Role';
    },
    tampilAlert(success, msg) {
      this.isSuccess = success;
      this.message   = msg;
      this.showAlert = true;
      setTimeout(() => { this.showAlert = false; }, 3000);
    },
  },
});
