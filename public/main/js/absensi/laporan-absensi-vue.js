const absensiApp = new Vue({
  el: '#absensi-app',
  data: {
    listAbsensi: [],
    listCabang: [],
    listShift: [],
    fotoPreview: '',
    loading: false,
    filter: {
      tanggal_dari: '',
      tanggal_sampai: '',
      branch_id: '',
      shift_id: '',
      status: '',
      keyword: '',
    },
    sort: { column: 'tanggal', order: 'desc' },
    currentPage: 1,
    totalPage: 1,
    baseUrl: '',
    token: '',
    role: '',
    branchId: '',
    isAdmin: false,
  },
  computed: {
    pageNumbers() {
      const delta = 2;
      const left  = Math.max(1, this.currentPage - delta);
      const right = Math.min(this.totalPage, this.currentPage + delta);
      const pages = [];
      for (let i = left; i <= right; i++) pages.push(i);
      return pages;
    },
  },
  mounted() {
    const stored = localStorage.getItem('vet-clinic');
    if (!stored) return;
    const auth = JSON.parse(stored);
    this.token    = auth.token;
    this.role     = auth.role;
    this.branchId = auth.branch_id;
    this.baseUrl  = document.querySelector('.baseUrl').value;
    this.isAdmin  = auth.role === 'admin';

    const now = new Date();
    const y = now.getFullYear();
    const m = String(now.getMonth() + 1).padStart(2, '0');
    this.filter.tanggal_dari   = y + '-' + m + '-01';
    this.filter.tanggal_sampai = y + '-' + m + '-' + String(new Date(y, now.getMonth() + 1, 0).getDate()).padStart(2, '0');

    if (this.isAdmin) this.loadCabang();
    this.loadShift();
    this.loadAbsensi();
  },
  methods: {
    loadAbsensi() {
      this.loading = true;
      const params = Object.entries(this.filter)
        .filter(([, v]) => v !== '')
        .map(([k, v]) => k + '=' + encodeURIComponent(v))
        .join('&');

      const sortParams = 'column=' + this.sort.column + '&orderby=' + this.sort.order + '&page=' + this.currentPage;

      axios.get(this.baseUrl + '/api/absensi?' + params + '&' + sortParams, {
        headers: { Authorization: 'Bearer ' + this.token }
      }).then(res => {
        this.listAbsensi = res.data.data;
        this.totalPage   = res.data.total_paging || 1;
      }).catch(() => {
        this.listAbsensi = [];
        this.totalPage   = 1;
      }).finally(() => { this.loading = false; });
    },
    sortBy(column) {
      if (this.sort.column === column) {
        this.sort.order = this.sort.order === 'asc' ? 'desc' : 'asc';
      } else {
        this.sort.column = column;
        this.sort.order  = 'asc';
      }
      this.currentPage = 1;
      this.loadAbsensi();
    },
    sortIcon(column) {
      if (this.sort.column !== column) return 'fa fa-sort';
      return this.sort.order === 'asc' ? 'fa fa-sort-asc' : 'fa fa-sort-desc';
    },
    goToPage(page) {
      if (page < 1 || page > this.totalPage) return;
      this.currentPage = page;
      this.loadAbsensi();
    },
    onFilter() {
      this.currentPage = 1;
      this.loadAbsensi();
    },
    loadCabang() {
      axios.get(this.baseUrl + '/api/cabang', {
        headers: { Authorization: 'Bearer ' + this.token }
      }).then(res => { this.listCabang = res.data; }).catch(() => {});
    },
    loadShift() {
      axios.get(this.baseUrl + '/api/shift', {
        headers: { Authorization: 'Bearer ' + this.token }
      }).then(res => { this.listShift = res.data; }).catch(() => {});
    },
    formatAlamat(alamat) {
      if (!alamat) return '-';
      return alamat.split(',')[0].trim();
    },
    formatJarak(meter) {
      if (meter === null || meter === undefined) return '-';
      if (meter >= 1000) return (meter / 1000).toFixed(1) + ' km';
      return meter + ' m';
    },
    lihatFoto(url) {
      this.fotoPreview = url;
      $('#modal-foto').modal('show');
    },
    exportExcel() {
      const params = Object.entries(this.filter)
        .filter(([, v]) => v !== '')
        .map(([k, v]) => k + '=' + encodeURIComponent(v))
        .join('&');

      $('#loading-screen').show();

      $.ajax({
        url      : this.baseUrl + '/api/absensi/export?' + params,
        headers  : { 'Authorization': 'Bearer ' + this.token },
        xhrFields: { responseType: 'blob' },
        success  : function (data, status, xhr) {
          const disposition = xhr.getResponseHeader('content-disposition');
          const matches = /"([^"]*)"/.exec(disposition);
          const filename = (matches && matches[1]) ? matches[1] : 'Laporan Absensi.xlsx';
          const blob = new Blob([data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
          const downloadUrl = URL.createObjectURL(blob);
          const a = document.createElement('a');
          a.href = downloadUrl;
          a.download = filename;
          document.body.appendChild(a);
          a.click();
          document.body.removeChild(a);
        },
        complete : function () { $('#loading-screen').hide(); },
        error    : function (err) {
          $('#loading-screen').hide();
          if (err.status === 401) {
            localStorage.removeItem('vet-clinic');
            location.href = location.origin + '/masuk';
          } else {
            alert('Gagal mengunduh file Excel.');
          }
        },
      });
    },
  },
});
