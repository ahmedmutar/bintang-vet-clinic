<div class="modal fade" id="modal-pendaftaran-pasien">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
				<h4 class="modal-title"></h4>
			</div>
			<div class="modal-body">
				<form class="form-pendaftaran-pasien">
					<div class="box-body">
            <div class="form-group">
							<label for="selectedPasien">Cari Pasien</label>
							<select id="selectedPasien" class="form-control" style="width: 100%">
							</select>
							<div id="pasienErr1" class="validate-error"></div>
						</div>
            <table>
              <tr>
                <td class="detail-label">Nomor Pasien</td>
                <td id="nomorPasienTxt" class="detail-value"></td>
              </tr>
              <tr>
                <td class="detail-label">Jenis Hewan</td>
                <td id="jenisHewanTxt" class="detail-value"></td>
              </tr>
              <tr>
                <td class="detail-label">Nama Hewan</td>
                <td id="namaHewanTxt" class="detail-value"></td>
              </tr>
              <tr>
                <td class="detail-label">Jenis Kelamin</td>
                <td id="jenisKelaminTxt" class="detail-value"></td>
              </tr>
              <tr>
                <td class="detail-label">Usia Hewan</td>
                <td class="detail-value">
                  <span id="usiaHewanTahunTxt"></span>
                  <span id="usiaHewanBulanTxt"></span>
                  <span id="usiaHewanHariTxt"></span>
                </td>
              </tr>
              <tr>
                <td class="detail-label">Nama Pemilik</td>
                <td id="namaPemilikTxt" class="detail-value"></td>
              </tr>
              <tr>
                <td class="detail-label">Alamat Pemilik</td>
                <td id="alamatPemilikTxt" class="detail-value"></td>
              </tr>
              <tr>
                <td class="detail-label">Nomor HP Pemilik</td>
                <td id="nomorHpPemilikTxt" class="detail-value"></td>
              </tr>
            </table>
            <div class="form-group">
							<label for="keluhanDropdown">Keluhan</label>
							<select id="keluhanDropdown" class="form-control" style="width:100%">
								<option value="">Pilih Keluhan</option>
							</select>
							<input id="keluhanLainnya" type="text" class="form-control" placeholder="Masukan keluhan lainnya" style="display:none; margin-top:8px;">
							<div id="keluhanErr1" class="validate-error"></div>
						</div>
						<div class="form-group">
							<label for="namaPendaftar">Nama Pendaftar</label>
							<input id="namaPendaftar" type="text" class="form-control" placeholder="Masukan Nama Pendaftar">
							<div id="namaPendaftarErr1" class="validate-error"></div>
						</div>
						<div class="form-group">
							<label for="selectedDokter">Dokter yang menangani</label>
							<select id="selectedDokter" class="form-control" style="width: 100%">
							</select>
							<div id="dokterErr1" class="validate-error"></div>
						</div>
						<div class="form-group">
							<div id="beErr" class="validate-error"></div>
						</div>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-primary" id="btnSubmitPendaftaranPasien">Simpan</button>
			</div>
		</div>
		<!-- /.modal-content -->
	</div>
	<!-- /.modal-dialog -->
</div>
