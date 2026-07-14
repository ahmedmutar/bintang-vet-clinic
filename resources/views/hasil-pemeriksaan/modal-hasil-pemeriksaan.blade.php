<div class="modal fade" id="modal-hasil-pemeriksaan" style="overflow: auto">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
				<h4 class="modal-title"></h4>
			</div>
			<div class="modal-body">
				<form class="form-hasil-pemeriksaan">
					<div class="box-body">
            <div class="form-group form-cari-pasien">
							<label for="selectedPasien">Cari Pasien</label>
							<select id="selectedPasien" class="form-control" style="width: 100%">
							</select>
							<div id="pasienErr1" class="validate-error"></div>
            </div>
            <div class="d-flex m-b-10px">
              <div class="label-detail-div">Nomor Registrasi</div>
              <div id="nomorRegistrasiTxt"></div>
            </div>
            <div class="d-flex m-b-10px">
              <div class="label-detail-div">Nomor Pasien</div>
              <div id="nomorPasienTxt"></div>
            </div>
            <div class="d-flex m-b-10px">
              <div class="label-detail-div">Jenis Hewan</div>
              <div id="jenisHewanTxt"></div>
            </div>
            <div class="d-flex m-b-10px">
              <div class="label-detail-div">Nama Hewan</div>
              <div id="namaHewanTxt"></div>
            </div>
            <div class="d-flex m-b-10px">
              <div class="label-detail-div">Jenis Kelamin</div>
              <div id="jenisKelaminTxt"></div>
            </div>
            <div class="d-flex m-b-10px">
              <div class="label-detail-div">Usia Hewan</div>
              <div>
                <span id="usiaHewanTahunTxt"></span>&nbsp;&nbsp;
                <span id="usiaHewanBulanTxt"></span>&nbsp;&nbsp;
                <span id="usiaHewanHariTxt"></span>
              </div>
            </div>
            <div class="d-flex m-b-10px">
              <div class="label-detail-div">Nama Pemilik</div>
              <div id="namaPemilikTxt"></div>
            </div>
            <div class="d-flex m-b-10px">
              <div class="label-detail-div">Alamat Pemilik</div>
              <div id="alamatPemilikTxt"></div>
            </div>
            <div class="d-flex m-b-10px">
              <div class="label-detail-div">Nomor HP Pemilik</div>
              <div id="nomorHpPemilikTxt"></div>
            </div>
            <div class="d-flex m-b-10px">
              <div class="label-detail-div">Keluhan</div>
              <div id="keluhanTxt"></div>
            </div>
            <div class="d-flex m-b-10px">
              <div class="label-detail-div">Nama Pendaftar</div>
              <div id="namaPendaftarTxt"></div>
            </div>
            <div class="form-group">
							<label for="anamnesa">Anamnesa</label>
              <textarea id="anamnesa" class="form-control" placeholder="Masukan Anamnesa"></textarea>
							<div id="anamnesaErr1" class="validate-error"></div>
            </div>
            <div class="form-group">
							<label for="sign">Sign</label>
              <textarea id="sign" class="form-control" placeholder="Masukan Sign"></textarea>
							<div id="signErr1" class="validate-error"></div>
						</div>
            <div class="form-group">
							<label for="diagnosa">Diagnosa</label>
              <textarea id="diagnosa" class="form-control" placeholder="Masukan Diagnosa"></textarea>
							<div id="diagnosaErr1" class="validate-error"></div>
            </div>
            <div class="form-group">
              <label for="foto">Foto Kondisi Pasien</label>
              <div class="dropzone" id="fotoKondisiPasien">

              </div>
            </div>
            <div class="form-group"> {{-- Jasa Rawat Jalan --}}
              <label for="jasa">Jasa</label>
              <select id="selectedJasa" class="form-control" style="width: 100%; margin-bottom: 10px" multiple="multiple"></select>
              <div class="table-responsive m-t-10px">
                <table class="table table-striped text-nowrap table-list-jasa">
                  <thead>
                    <tr>
                      <th>No</th>
                      <th class="tgl-edit">Tanggal</th>
                      <th class="dibuat-edit">Dibuat Oleh</th>
                      <th>Jenis Layanan</th>
                      <th>Kategori Jasa</th>
                      <th>Jumlah</th>
                      <th>Harga Satuan</th>
                      <th>Harga Keseluruhan</th>
                      <th>Hapus</th>
                    </tr>
                  </thead>
                  <tbody id="list-selected-jasa"></tbody>
                </table>
              </div>
            </div>
            <div class="form-group">{{-- Barang Rawat Jalan --}}
              <label for="barang">Barang</label>
              <select id="selectedBarang" class="form-control" style="width: 100%; margin-bottom: 10px" multiple="multiple"></select>
              <div class="table-responsive m-t-10px">
                <table class="table table-striped text-nowrap table-list-barang">
                  <thead>
                    <tr>
                      <th>No</th>
                      <th class="tgl-edit">Tanggal</th>
                      <th class="dibuat-edit">Dibuat Oleh</th>
                      <th>Nama Barang</th>
                      <th>Kategori Barang</th>
                      <th>Satuan Barang</th>
                      <th>Jumlah</th>
                      <th>Harga Satuan</th>
                      <th>Harga Keseluruhan</th>
                      <th>Hapus</th>
                    </tr>
                  </thead>
                  <tbody id="list-selected-barang"></tbody>
                </table>
              </div>
            </div>

            <div class="form-group">
              <label for="barang" style="margin-right: 93px;">Rawat Inap</label>
              <span style="padding-right: 51px;"><input type="radio" name="radioRawatInap" value=1 /> Ya</span>
              <span><input type="radio" name="radioRawatInap" value=0 /> Tidak</span>
              <div id="rawatInapErr1" class="validate-error"></div>
            </div>

            <div class="form-group form-deskripsi-kondisi-pasien">
              <label for="deskripsiKondisiPasien">Deskripsi Kondisi Pasien</label>
              <textarea id="descriptionCondPasien" class="form-control" placeholder="Masukan Deskripsi Kondisi Pasien"></textarea>
							<div id="descriptionCondPasienErr1" class="validate-error"></div>
            </div>
            <div class="table-responsive form-group">
              <table class="table table-striped table-deskripsi-kondisi-pasien">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Dibuat Oleh</th>
                    <th>Deskripsi</th>
                  </tr>
                </thead>
                <tbody id="list-deskripsi-kondisi-pasien"></tbody>
              </table>
            </div>
            <div class="form-group">
              <label for="barang" style="margin-right: 40px;">Status Pemeriksaan</label>
              <span style="padding-right: 23px;"><input type="radio" name="radioStatusPemeriksa" value=1 /> Selesai</span>
              <span><input type="radio" name="radioStatusPemeriksa" value=0 /> Belum</span>
              <div id="statusPemeriksaErr1" class="validate-error"></div>
            </div>

						<div class="form-group">
							<div id="beErr" class="validate-error"></div>
						</div>
					</div>
				</form>
			</div>
			<div class="modal-footer">
        <button type="button" class="btn btn-primary" id="testUpload">Test Upload</button>
				<button type="button" class="btn btn-primary" id="btnSubmitHasilPemeriksaan">Simpan</button>
			</div>
		</div>
		<!-- /.modal-content -->
	</div>
	<!-- /.modal-dialog -->
</div>
