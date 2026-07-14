const nomorWaApp = new Vue({
  el: '#nomor-wa-app',
  data: {
    list: [],
    loading: false,
    editId: null,
    editWa: '',
    editToken: '',
    showToken: {},
    loadingSimpan: false,
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
    this.load();
  },
  methods: {
    load() {
      this.loading = true;
      axios.get(this.baseUrl + '/api/pengabaran/nomor-wa', {
        headers: { Authorization: 'Bearer ' + this.token },
      }).then(res => {
        this.list = res.data;
      }).catch(() => {
        this.tampilAlert(false, 'Gagal memuat data cabang.');
      }).finally(() => { this.loading = false; });
    },
    startEdit(item) {
      this.editId    = item.id;
      this.editWa    = item.whatsapp_number || '';
      this.editToken = item.fonnte_token || '';
      Vue.set(this.showToken, item.id, false);
    },
    batalEdit() {
      this.editId    = null;
      this.editWa    = '';
      this.editToken = '';
    },
    toggleShow(id) {
      Vue.set(this.showToken, id, !this.showToken[id]);
    },
    simpan(item) {
      this.loadingSimpan = true;
      axios.put(this.baseUrl + '/api/pengabaran/nomor-wa', {
        id:              item.id,
        whatsapp_number: this.editWa,
        fonnte_token:    this.editToken,
      }, {
        headers: { Authorization: 'Bearer ' + this.token },
      }).then(res => {
        item.whatsapp_number = this.editWa || null;
        item.fonnte_token    = this.editToken || null;
        item.has_token       = !!this.editToken;
        this.editId          = null;
        this.editWa          = '';
        this.editToken       = '';
        this.tampilAlert(true, res.data.message);
      }).catch(err => {
        const msg = err.response?.data?.errors?.join(', ') || 'Gagal menyimpan.';
        this.tampilAlert(false, msg);
      }).finally(() => { this.loadingSimpan = false; });
    },
    maskToken(token) {
      if (!token) return '';
      if (token.length <= 6) return '••••••';
      return '••••' + token.slice(-6);
    },
    tampilAlert(success, msg) {
      this.isSuccess = success;
      this.message   = msg;
      this.showAlert = true;
      setTimeout(() => { this.showAlert = false; }, 4000);
    },
  },
});
