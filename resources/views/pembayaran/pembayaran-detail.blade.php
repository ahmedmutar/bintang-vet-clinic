@extends('layout.master')

@section('content')
<div class="box box-info" id="detail-pembayaran-app">
  <div class="box-header ">
    <h3 class="box-title">Detail Pembayaran</h3>
  </div>

  <div class="box-body">
    <div class="btn-back-to-list">
      <span class="fa fa-arrow-left"></span> <span class="text">List Pembayaran</span>
    </div>
    <div class="m-t-25px">
      <div class="col-md-6">
        <div class="d-flex m-b-10px">
          <div class="label-detail-div">Nomor Registrasi</div>
          <div id="nomorRegistrasiTxt" class="p-left-10px"></div>
        </div>
        <div class="d-flex m-b-10px">
          <div class="label-detail-div">Nomor Pasien</div>
          <div id="nomorPasienTxt" class="p-left-10px"></div>
        </div>
        <div class="d-flex m-b-10px">
          <div class="label-detail-div">Jenis Hewan</div>
          <div id="jenisHewanTxt" class="p-left-10px"></div>
        </div>
        <div class="d-flex m-b-10px">
          <div class="label-detail-div">Nama Hewan</div>
          <div id="namaHewanTxt" class="p-left-10px"></div>
        </div>
        <div class="d-flex m-b-10px">
          <div class="label-detail-div">Jenis Kelamin</div>
          <div id="jenisKelaminTxt" class="p-left-10px"></div>
        </div>
        <div class="d-flex m-b-10px">
          <div class="label-detail-div">Nama Pendaftar</div>
          <div id="namaPendaftarTxt" class="p-left-10px"></div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="d-flex m-b-10px">
          <div class="label-detail-div">Usia Hewan</div>
          <div class="p-left-10px">
            <span id="usiaHewanTahunTxt"></span>
            <span id="usiaHewanBulanTxt"></span>
            <span id="usiaHewanHariTxt"></span>
          </div>
        </div>
        <div class="d-flex m-b-10px">
          <div class="label-detail-div">Nama Pemilik</div>
          <div id="namaPemilikTxt" class="p-left-10px"></div>
        </div>
        <div class="d-flex m-b-10px">
          <div class="label-detail-div">Alamat Pemilik</div>
          <div id="alamatPemilikTxt" class="p-left-10px"></div>
        </div>
        <div class="d-flex m-b-10px">
          <div class="label-detail-div">Nomor HP Pemilik</div>
          <div id="nomorHpPemilikTxt" class="p-left-10px" style="width: 75%"></div>
        </div>
        <div class="d-flex m-b-10px">
          <div class="label-detail-div">Keluhan</div>
          <div id="keluhanTxt" class="p-left-10px" style="width: 75%"></div>
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
                <th>Status</th>
              </tr>
            </thead>
            <tbody id="list-selected-jasa"></tbody>
          </table>
        </div>
      </div>
      <div class="col-md-12 m-b-10px">
        <div class="label-detail-div m-b-10px">Barang</div>
        <div class="table-responsive">
          <table class="table table-striped text-nowrap">
            <thead>
              <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Dibuat Oleh</th>
                <th>Kelompok Obat</th>
                <th>Jumlah</th>
                <th>Harga Satuan</th>
                <th>Harga Keseluruhan</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody id="list-selected-barang"></tbody>
          </table>
        </div>
      </div>
      <div class="col-md-6">
        <div class="d-flex m-b-10px">
          <div class="label-detail-div">Rawat Inap</div>
          <div id="rawatInapTxt" class="p-left-10px"></div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="d-flex">
          <div class="label-detail-div">Status Pemeriksaan</div>
          <div id="statusPemeriksaanTxt" class="p-left-10px"></div>
        </div>
      </div>
    </div>
    <div class="col-md-12 m-t-25px">
      <div style="font-weight: 700">Detail Tagihan</div>
    </div>
    <div class="col-md-12 indented-detail-tagihan">
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
              <th>Diskon</th>
              <th>Harga Keseluruhan</th>
              <th>Pembayaran</th>
            </tr>
          </thead>
          <tbody id="list-tagihan-jasa">
            <tr class="text-center"><td colspan="8">Tidak ada data.</td></tr>
          </tbody>
        </table>
      </div>
    </div>
    <div class="col-md-12 indented-detail-tagihan">
      <div class="label-detail-div m-b-10px">Barang</div>
      <div class="table-responsive">
        <table class="table table-striped text-nowrap">
          <thead>
            <tr>
              <th>No</th>
              <th>Tanggal</th>
              <th>Dibuat Oleh</th>
              <th>Kelompok Obat</th>
              <th>Jumlah</th>
              <th>Harga Satuan</th>
              <th>Diskon</th>
              <th>Harga Keseluruhan</th>
              <th>Pembayaran</th>
            </tr>
          </thead>
          <tbody id="list-tagihan-barang">
            <tr class="text-center"><td colspan="9">Tidak ada data.</td></tr>
          </tbody>
        </table>
      </div>
    </div>
    <div class="col-md-12 indented-detail-tagihan">
      <div class="label-detail-div m-b-10px">Pet Shop</div>
      <div class="table-responsive">
        <table class="table table-striped text-nowrap">
          <thead>
            <tr>
              <th>No</th>
              <th>Tanggal</th>
              <th>Dibuat Oleh</th>
              <th>Nama Barang</th>
              <th>Jumlah</th>
              <th>Harga Satuan</th>
              <th>Harga Keseluruhan</th>
              <th>Pembayaran</th>
            </tr>
          </thead>
          <tbody id="list-tagihan-pet-shop">
            <tr class="text-center"><td colspan="8">Tidak ada data.</td></tr>
          </tbody>
        </table>
      </div>
    </div>
    <div class="col-md-12 indented-detail-tagihan">
      <div class="d-flex m-b-10px">
        <div style="font-weight: 700" id="label-tagihan"></div>
        <div id="totalBayarTxt" class="p-left-10px" style="word-break: break-all;"></div>
      </div>
    </div>

    <div class="col-md-12 m-t-25px">
      <button id="btnKembali" type="button" class="btn btn-default pull-right m-r-10px">Kembali</button>
    </div>
  </div>
  
</div>

@endsection
@section('script-content')
  <script src="{{ asset('main/js/pembayaran/pembayaran-detail.js') }}"></script>  
@endsection
@section('css-content')
  <link rel="stylesheet" type='text/css' href="{{ asset('main/css/pembayaran.css') }}">
@endsection
@section('vue-content')@endsection