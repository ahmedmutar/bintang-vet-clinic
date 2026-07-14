@extends('layout.master')

@section('content')
<div class="box box-info" id="form-hasil-pemeriksaan-app">
  <div class="box-header">
    <h3 class="box-title">Detail Hasil Pemeriksaan</h3>
  </div>

  <div class="box-body">
    <div class="btn-back-to-list">
      <span class="fa fa-arrow-left"></span> <span class="text">Daftar Hasil Pemeriksaan</span>
    </div>

    <div class="nav-tabs-custom m-t-25px">
      <ul class="nav nav-tabs">
        <li class="active"><a href="#general" data-toggle="tab">Utama</a></li>
        <li><a href="#kelompok_obat" data-toggle="tab">Obat</a></li>
      </ul>

      <div id="tab-content" class="tab-content">
        <div class="tab-pane fade in active" id="general">
          <div class="row">
            <div class="col-md-6">
              <div class="d-flex m-b-10px">
                <div class="label-detail-div">Nomor Registrasi</div>
                <div id="nomorRegistrasiDetailTxt" class="p-left-10px"></div>
              </div>
              <div class="d-flex m-b-10px">
                <div class="label-detail-div">Nomor Pasien</div>
                <div id="nomorPasienDetailTxt" class="p-left-10px"></div>
              </div>
              <div class="d-flex m-b-10px">
                <div class="label-detail-div">Jenis Hewan</div>
                <div id="jenisHewanDetailTxt" class="p-left-10px"></div>
              </div>
              <div class="d-flex m-b-10px">
                <div class="label-detail-div">Nama Hewan</div>
                <div id="namaHewanDetailTxt" class="p-left-10px"></div>
              </div>
              <div class="d-flex m-b-10px">
                <div class="label-detail-div">Jenis Kelamin</div>
                <div id="jenisKelaminDetailTxt" class="p-left-10px"></div>
              </div>
              <div class="d-flex m-b-10px">
                <div class="label-detail-div">Usia Hewan</div>
                <div class="p-left-10px">
                  <span id="usiaHewanTahunDetailTxt"></span>&nbsp;&nbsp;
                  <span id="usiaHewanBulanDetailTxt"></span>&nbsp;&nbsp;
                  <span id="usiaHewanHariDetailTxt"></span>
                </div>
              </div>
              <div class="d-flex m-b-10px">
                <div class="label-detail-div">Keluhan</div>
                <div id="keluhanDetailTxt" class="p-left-10px value-detail-div"></div>
              </div>
            </div>
            <div class="col-md-6">

              <div class="d-flex m-b-10px">
                <div class="label-detail-div">Nama Pendaftar</div>
                <div id="namaPendaftarDetailTxt" class="p-left-10px"></div>
              </div>
              <div class="d-flex m-b-10px">
                <div class="label-detail-div">Nama Pemilik</div>
                <div id="namaPemilikDetailTxt" class="p-left-10px"></div>
              </div>
              <div class="d-flex m-b-10px">
                <div class="label-detail-div">Alamat Pemilik</div>
                <div id="alamatPemilikDetailTxt" class="p-left-10px value-detail-div"></div>
              </div>
              <div class="d-flex m-b-10px">
                <div class="label-detail-div">Nomor HP Pemilik</div>
                <div id="nomorHpPemilikDetailTxt" class="p-left-10px value-detail-div"></div>
              </div>
              <div class="d-flex m-b-10px m-t-50px">
                <div class="label-detail-div">Anamnesa</div>
                <div id="anamnesaDetailTxt" class="p-left-10px value-detail-div"></div>
              </div>
              <div class="d-flex m-b-10px">
                <div class="label-detail-div">Sign</div>
                <div id="signDetailTxt" class="p-left-10px value-detail-div"></div>
              </div>
              <div class="d-flex m-b-10px">
                <div class="label-detail-div">Diagnosa</div>
                <div id="diagnosaDetailTxt" class="p-left-10px value-detail-div"></div>
              </div>
            </div>

            <div class="col-md-12 m-b-10px">
              <div class="label-detail-div m-b-10px">Jasa</div>
              <div class="table-responsive">
                <table class="table table-striped text-nowrap">
                  <thead>
                    <tr>
                      <th>No</th>
                      <th>Tanggal</th>
                      <th>Dibuat Oleh</th>
                      <th>Jenis Layanan</th>
                      <th>Kategori Jasa</th>
                      <th>Jumlah</th>
                      <th>Harga Satuan</th>
                      <th>Harga Keseluruhan</th>
                    </tr>
                  </thead>
                  <tbody id="detail-list-jasa">
                    <tr><td colspan="8" class="text-center">Tidak ada data.</td></tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div class="col-md-6 m-b-10px">
              <div class="d-flex">
                <div class="label-detail-div">Rawat Inap</div>
                <div id="rawatInapDetailTxt" class="value-detail-div p-left-10px"></div>
              </div>
            </div>

            <div class="col-md-12 m-b-10px">
              <div class="m-b-10px" style="font-weight: 700;">Deskripsi Kondisi Pasien</div>
              <div class="table-responsive">
                <table class="table table-striped text-nowrap">
                  <thead>
                    <tr>
                      <th>No</th>
                      <th>Tanggal</th>
                      <th>Dibuat Oleh</th>
                      <th>Deskripsi</th>
                    </tr>
                  </thead>
                  <tbody id="detail-list-inpatient">
                    <tr><td colspan="8" class="text-center">Tidak ada data.</td></tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div class="col-md-6 m-b-10px">
              <div class="d-flex">
                <div class="label-detail-div">Status Pemeriksaan</div>
                <div id="statusPemeriksaanDetailTxt" class="value-detail-div p-left-10px"></div>
              </div>
            </div>

            <div class="col-md-12">
              <div class="label-detail-div m-b-10px">Foto Kondisi Pasien</div>
              <div id="section-foto-kondisi-pasien"></div>
            </div>

          </div>
        </div>
        <div class="tab-pane fade" id="kelompok_obat">
          <div class="row">
            <div class="col-md-12 m-b-10px" id="locateDrawKelompokBarang">
              <div class="target">
                Tidak ada kelompok obat.
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>
@endsection

@section('script-content')
  <!-- Magnific Popup core JS file -->
  <script src="{{ asset('plugins/magnific-popup/jquery.magnific-popup.js') }}"></script>
  <script src="{{ asset('main/js/hasil-pemeriksaan/detail-hasil-pemeriksaan.js') }}"></script>
@endsection
@section('css-content')
  <link rel="stylesheet" type='text/css' href="{{ asset('main/css/hasil-pemeriksaan.css') }}">
  <link rel="stylesheet" type='text/css' href="{{ asset('plugins/magnific-popup/magnific-popup.css') }}">
@endsection
@section('vue-content')@endsection
