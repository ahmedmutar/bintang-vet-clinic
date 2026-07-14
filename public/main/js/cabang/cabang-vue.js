$(document).ready(function() {

	const cabangApp = new Vue({
		el: '#cabang-app',
		data: {
			searchTxt: '',
			idCabang: null,
			kodeCabang: '',
			namaCabang: '',
      alamatCabang: '',
      instruksiPembayaran: '',
      latitudeCabang: '',
      longitudeCabang: '',
			titleModal: '',
			stateModal: '',
			msgContent: '',
			confirmContent: '',
			kdCabangErr1: false,
			kdCabangErr2: false,
			namaCabangErr: false,
      alamatErr: false,
      instruksiPembayaranErr: false,
			beErr: false,
      touchedForm: false,
			msgBeErr: '',
			listCabang: [],
      _map: null,
      _marker: null,
			columnStatus: {
				branch_code: 'none',
				branch_name: 'none',
        address: 'none',
			},
			paramUrlSetup: {
				orderby:'',
				column: '',
				keyword: ''
			}
		},
		mounted() {
			if (role.toLowerCase() !== 'admin') {
				window.location.href = $('.baseUrl').val() + `/unauthorized`;
			}
			this.getData();
		},
		computed: {
			validateSimpanCabang: function() {
				return this.kdCabangErr1 || this.kdCabangErr2 || this.beErr || this.namaCabangErr || this.alamatErr || this.instruksiPembayaranErr;
			}
		},
		methods: {
			openFormAdd: function() {
				this.stateModal = 'add';
				this.titleModal = 'Tambah Cabang';
				this.refreshVariable();
				$('#modal-cabang').one('shown.bs.modal', () => {
					this.initMap();
					this._map.invalidateSize();
				});
				$('#modal-cabang').modal('show');
			},
			openFormUpdate: function(item) {
				this.stateModal = 'edit';
				this.titleModal = 'Ubah Cabang';
				this.refreshVariable();

				this.idCabang            = item.id;
				this.kodeCabang          = item.branch_code;
				this.namaCabang          = item.branch_name;
        this.alamatCabang        = item.address;
        this.instruksiPembayaran = item.payment_instruction;
        this.latitudeCabang      = item.latitude  || '';
        this.longitudeCabang     = item.longitude || '';
				$('#modal-cabang').one('shown.bs.modal', () => {
					this.initMap(item.latitude, item.longitude);
					this._map.invalidateSize();
				});
				$('#modal-cabang').modal('show');
			},
			openFormDelete: function(item) {
				this.stateModal = 'delete';
				this.idCabang = item.id;
				this.confirmContent = 'Menghapus cabang akan mempengaruhi keseluruhan data';
				$('#modal-confirmation').modal('show');
			},
			initMap: function(lat, lng) {
				const defaultLat = lat ? parseFloat(lat) : -6.2088;
				const defaultLng = lng ? parseFloat(lng) : 106.8456;

				if (this._map) { this._map.remove(); this._map = null; this._marker = null; }

				this._map = L.map('map-cabang').setView([defaultLat, defaultLng], lat ? 15 : 12);
				L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
					attribution: '© OpenStreetMap'
				}).addTo(this._map);

				if (lat && lng) {
					this._marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(this._map);
					this._marker.on('dragend', (e) => {
						const p = e.target.getLatLng();
						this.latitudeCabang  = p.lat.toFixed(8);
						this.longitudeCabang = p.lng.toFixed(8);
					});
				}

				this._map.on('click', (e) => {
					this.latitudeCabang  = e.latlng.lat.toFixed(8);
					this.longitudeCabang = e.latlng.lng.toFixed(8);
					this.setMapMarker(e.latlng.lat, e.latlng.lng);
				});
			},
			setMapMarker: function(lat, lng) {
				if (!this._map) return;
				if (this._marker) {
					this._marker.setLatLng([lat, lng]);
				} else {
					this._marker = L.marker([lat, lng], { draggable: true }).addTo(this._map);
					this._marker.on('dragend', (e) => {
						const p = e.target.getLatLng();
						this.latitudeCabang  = p.lat.toFixed(8);
						this.longitudeCabang = p.lng.toFixed(8);
					});
				}
				this._map.setView([lat, lng], 15);
			},
			gunakanLokasiSaya: function() {
				if (!navigator.geolocation) { alert('Geolocation tidak didukung browser ini.'); return; }
				navigator.geolocation.getCurrentPosition(pos => {
					this.latitudeCabang  = pos.coords.latitude;
					this.longitudeCabang = pos.coords.longitude;
					this.setMapMarker(pos.coords.latitude, pos.coords.longitude);
				}, () => { alert('Gagal mendapatkan lokasi. Pastikan izin lokasi diaktifkan.'); });
			},
			kodeCabangKeyup: function(e) {
				const regexp = /^[^a-z ]*$/;
				this.kdCabangErr2 = (!regexp.test(this.kodeCabang) && this.stateModal == 'edit') ? true : false;

				this.validationForm();
			},
			namaCabangKeyup: function(e) {
				this.validationForm();
			},
      alamatCabangKeyup: function(e) {
        this.validationForm();
      },
      instruksiPembayaranKeyup: function(e) {
        this.validationForm();
      },
			onOrdering: function(e) {

				this.columnStatus[e] = (this.columnStatus[e] == 'asc') ? 'desc' : 'asc';
				if (e === 'branch_code') {
					this.columnStatus['branch_name'] = 'none';
          this.columnStatus['address'] = 'none';
				} else if (e === 'branch_name') {
					this.columnStatus['branch_code'] = 'none';
          this.columnStatus['address'] = 'none';
				} else {
          this.columnStatus['branch_code'] = 'none';
          this.columnStatus['branch_name'] = 'none';
        }

				this.paramUrlSetup.orderby = this.columnStatus[e];
				this.paramUrlSetup.column = e;
				this.getData();
			},
			getData: function() {
				$('#loading-screen').show();
				axios.get($('.baseUrl').val() + '/api/cabang', {  params: this.paramUrlSetup, headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` }})
					.then(resp => {
						this.listCabang = resp.data;
					})
					.catch(err => {
						if (err.response.status === 401) {
							localStorage.removeItem('vet-clinic');
							location.href = $('.baseUrl').val() + '/masuk';
						}
					})
					.finally(() => {
						$('#loading-screen').hide();
					});
			},
			submitCabang: function() {

				if (this.stateModal === 'add') {
					const form_data = new FormData();
					form_data.append('KodeCabang', this.kodeCabang);
					form_data.append('NamaCabang', this.namaCabang);
          form_data.append('Alamat', this.alamatCabang);
          form_data.append('InstruksiPembayaran', this.instruksiPembayaran);
          if (this.latitudeCabang)  form_data.append('Latitude',  this.latitudeCabang);
          if (this.longitudeCabang) form_data.append('Longitude', this.longitudeCabang);

					this.processSave(form_data);
				} else {

					$('#modal-confirmation').modal('show');
					this.confirmContent = 'Perubahan cabang akan mempengaruhi keseluruhan data';
				}
			},
			submitConfirm: function() {

				if (this.stateModal === 'edit') {
          const request = {
            id: this.idCabang,
            NamaCabang: this.namaCabang,
            Alamat: this.alamatCabang,
            InstruksiPembayaran: this.instruksiPembayaran,
            Latitude:  this.latitudeCabang  || null,
            Longitude: this.longitudeCabang || null,
          };
					this.processEdit(request);
				} else {
					this.processDelete({ id: this.idCabang });
				}
			},
			processDelete: function(form_data) {
				axios.delete($('.baseUrl').val() + '/api/cabang', { params: form_data, headers: { 'Authorization': `Bearer ${token}` } })
				.then(resp => {
					if (resp.status == 200) {
						$('#modal-confirmation').modal('toggle');

						this.msgContent = 'Berhasil menghapus cabang';
						$('#msg-box').modal('show');
						this.getData();
					}
				})
				.catch(err => {
					if (err.response.status === 401) {
						localStorage.removeItem('vet-clinic');
	          location.href = $('.baseUrl').val() + '/masuk';
					}
				})
			},
			processEdit: function(form_data) {
				axios.put($('.baseUrl').val() + '/api/cabang', form_data, { headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` }})
				.then(resp => {
					if (resp.status == 200) {
						$('#modal-confirmation').modal('toggle');

						this.msgContent = 'Berhasil Mengubah Cabang';
						$('#msg-box').modal('show');

						setTimeout(() => {
							$('#modal-cabang').modal('toggle');
							this.refreshVariable();
							this.getData();
						}, 1000);
					}
				})
				.catch(err => {
					if (err.response.status === 401) {
						localStorage.removeItem('vet-clinic');
	          location.href = $('.baseUrl').val() + '/masuk';
					}
				})
			},
			onSearch: function() {
				this.paramUrlSetup.keyword =  this.searchTxt;
				this.getData();
			},
			processSave: function(form_data) {
				$('#loading-screen').show();
				axios.post($('.baseUrl').val() + '/api/cabang', form_data, { headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` }})
				.then(resp => {
					if(resp.status == 200) {
						this.msgContent = 'Berhasil Menambah Cabang';
						$('#msg-box').modal('show');

						setTimeout(() => {
							$('#modal-cabang').modal('toggle');
							this.refreshVariable();
							this.getData();
						}, 1000);
					}
				})
				.catch(err => {
					if (err.response.status === 422) {
						this.msgBeErr = '',
						err.response.data.errors.forEach((element, idx) => {
							this.msgBeErr += element + ((idx !== err.response.data.errors.length - 1) ? '<br/>' : '');
						});
						this.beErr = true;

					} else if (err.response.status === 401) {
						localStorage.removeItem('vet-clinic');
	          location.href = $('.baseUrl').val() + '/masuk';
					}
				})
				.finally(() => {
					$('#loading-screen').hide();
				});
			},
			validationForm: function() {
				this.touchedForm = true;
				this.kdCabangErr1 = (!this.kodeCabang && this.stateModal == 'edit') ? true : false;
				this.namaCabangErr = (!this.namaCabang) ? true : false;
        this.alamatErr = (this.alamatCabang.length < 5) ? true : false;
        this.instruksiPembayaranErr = (this.instruksiPembayaran.length < 5) ? true : false;
				this.beErr = false;
			},
			refreshVariable: function() {
				this.kodeCabang = ''; this.namaCabang = '';
				this.kdCabangErr1 = false; this.kdCabangErr2 = false;
				this.namaCabangErr = false; this.touchedForm = false;
        this.alamatCabang = ''; this.alamatErr = false;
        this.instruksiPembayaran = ''; this.instruksiPembayaranErr = false;
        this.latitudeCabang = ''; this.longitudeCabang = '';
				this.beErr = false;
			}
		}
	});

});
