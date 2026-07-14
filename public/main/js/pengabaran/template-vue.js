const templateApp = new Vue({
  el: '#template-app',
  data: {
    templates: [],
    branches: [],
    selectedBranchId: '',
    loading: false,
    showAlert: false,
    isSuccess: false,
    message: '',
    token: '',
    baseUrl: '',
  },
  mounted() {
    const stored = localStorage.getItem('vet-clinic');
    if (!stored) return;
    const auth   = JSON.parse(stored);
    this.token   = auth.token;
    this.baseUrl = document.querySelector('.baseUrl').value;
    this.loadBranches();
  },
  methods: {
    loadBranches() {
      axios.get(this.baseUrl + '/api/pengabaran/nomor-wa', {
        headers: { Authorization: 'Bearer ' + this.token },
      }).then(res => {
        this.branches = res.data;
        if (this.branches.length > 0) {
          this.selectedBranchId = this.branches[0].id;
          this.load();
        }
      }).catch(() => {});
    },
    load() {
      if (!this.selectedBranchId) return;
      this.loading = true;
      axios.get(this.baseUrl + '/api/pengabaran/template?branch_id=' + this.selectedBranchId, {
        headers: { Authorization: 'Bearer ' + this.token },
      }).then(res => {
        this.templates = res.data.map(t => ({ ...t, loading: false, loadingHapus: false }));
      }).catch(() => {
        this.tampilAlert(false, 'Gagal memuat template pesan.');
      }).finally(() => { this.loading = false; });
    },
    simpan(item) {
      if (!item.message || item.message.trim() === '') {
        this.tampilAlert(false, 'Template untuk "' + item.complaint_name + '" tidak boleh kosong.');
        return;
      }
      if (!item.followup_days || item.followup_days < 1) {
        this.tampilAlert(false, 'Jumlah hari follow up untuk "' + item.complaint_name + '" minimal 1.');
        return;
      }
      item.loading = true;
      axios.put(this.baseUrl + '/api/pengabaran/template', {
        branch_id:     this.selectedBranchId,
        complaint_id:  item.complaint_id,
        message:       item.message,
        followup_days: item.followup_days,
      }, {
        headers: { Authorization: 'Bearer ' + this.token },
      }).then(res => {
        item.has_custom = true;
        this.tampilAlert(true, res.data.message + ' (' + item.complaint_name + ')');
      }).catch(err => {
        const msg = err.response?.data?.errors?.join(', ') || 'Gagal menyimpan template.';
        this.tampilAlert(false, msg);
      }).finally(() => { item.loading = false; });
    },
    hapus(item) {
      if (!confirm('Hapus template untuk "' + item.complaint_name + '"?')) return;
      item.loadingHapus = true;
      axios.delete(this.baseUrl + '/api/pengabaran/template', {
        headers: { Authorization: 'Bearer ' + this.token },
        data: { branch_id: this.selectedBranchId, complaint_id: item.complaint_id },
      }).then(res => {
        item.has_custom = false;
        item.message    = '';
        this.tampilAlert(true, res.data.message);
      }).catch(err => {
        const msg = err.response?.data?.errors?.join(', ') || 'Gagal menghapus template.';
        this.tampilAlert(false, msg);
      }).finally(() => { item.loadingHapus = false; });
    },
    tampilAlert(success, msg) {
      this.isSuccess = success;
      this.message   = msg;
      this.showAlert = true;
      setTimeout(() => { this.showAlert = false; }, 4000);
    },
  },
});
