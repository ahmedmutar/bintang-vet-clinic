@extends('layout.master')

@section('content')
<div class="box box-info" id="form-hasil-pemeriksaan-app">
  <div class="box-header ">
    <h3 class="box-title title-form-hasil-pemeriksaan"></h3>
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
            <form class="form-pembarayan-tambah">
              <div class="col-md-12 m-b-20px form-cari-pasien">
                <label for="selectedPasien">Cari Pasien</label>
                <select id="selectedPasien" class="form-control" style="width: 100%"></select>
                <div id="pasienErr1" class="validate-error"></div>
              </div>
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
                  <div class="label-detail-div">Usia Hewan</div>
                  <div class="p-left-10px">
                    <span id="usiaHewanTahunTxt"></span>&nbsp;&nbsp;
                    <span id="usiaHewanBulanTxt"></span>&nbsp;&nbsp;
                    <span id="usiaHewanHariTxt"></span>
                  </div>
                </div>
                <div class="d-flex m-b-10px">
                  <div class="label-detail-div">Keluhan</div>
                  <div id="keluhanTxt" class="p-left-10px" style="width: 75%"></div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="d-flex m-b-10px">
                  <div class="label-detail-div">Nama Pendaftar</div>
                  <div id="namaPendaftarTxt" class="p-left-10px"></div>
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
              </div>
              <div class="col-md-12 m-b-10px">
                <div class="label-detail-div">Anamnesa</div>
                <textarea id="anamnesa" class="form-control" placeholder="Masukan Anamnesa"></textarea>
                <div id="anamnesaErr1" class="validate-error"></div>
              </div>
              <div class="col-md-12 m-b-10px">
                <div class="label-detail-div">Sign</div>
                <textarea id="sign" class="form-control" placeholder="Masukan Sign"></textarea>
                <div id="signErr1" class="validate-error"></div>
              </div>
              <div class="col-md-12 m-b-10px">
                <div class="label-detail-div">Diagnosa</div>
                <textarea id="diagnosa" class="form-control" placeholder="Masukan Diagnosa"></textarea>
                <div id="diagnosaErr1" class="validate-error"></div>
              </div>
              <div class="col-md-12 m-b-10px">
                <div class="label-detail-div m-b-10px">Jasa</div>
                <select id="selectedJasa" class="form-control" style="width: 100%; margin-bottom: 10px" multiple="multiple"></select>
                <div class="table-responsive table-list-jasa">
                  <table class="table table-striped text-nowrap">
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

              <div class="col-md-12 m-b-10px">
                <div class="label-detail-div">Rawat Inap</div>
                <div style="display:flex; align-items:center; gap:24px; margin-top:6px;">
                  <label style="margin:0; font-weight:normal; cursor:pointer;">
                    <input type="radio" name="radioRawatInap" value=1 style="margin-right:6px;"> Ya
                  </label>
                  <label style="margin:0; font-weight:normal; cursor:pointer;">
                    <input type="radio" name="radioRawatInap" value=0 style="margin-right:6px;"> Tidak
                  </label>
                </div>
                <div id="rawatInapErr1" class="validate-error"></div>
              </div>

              <div class="col-md-12 m-b-10px form-deskripsi-kondisi-pasien">
                <div class="label-detail-div">Deskripsi Kondisi Pasien</div>
                <textarea id="descriptionCondPasien" class="form-control" placeholder="Masukan Deskripsi Kondisi Pasien"></textarea>
              </div>

              <div class="col-md-12 m-b-10px table-deskripsi-kondisi-pasien">
                <div class="table-responsive form-group">
                  <table class="table table-striped">
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
              </div>

              <div class="col-md-12 m-b-10px">
                <div class="label-detail-div">Pengabaran / Follow Up</div>
                <div style="display:flex; align-items:center; gap:24px; margin-top:6px;">
                  <label style="margin:0; font-weight:normal; cursor:pointer;">
                    <input type="radio" name="radioStatusPengabaran" value=1 style="margin-right:6px;"> Ya
                  </label>
                  <label style="margin:0; font-weight:normal; cursor:pointer;">
                    <input type="radio" name="radioStatusPengabaran" value=0 style="margin-right:6px;"> Tidak
                  </label>
                </div>
                <div id="statusPemeriksaErr1" class="validate-error"></div>
                <div id="alasanTidakPengabaranDiv" style="display:none; margin-top:10px;">
                  <label class="label-detail-div">Alasan Tidak Dilakukan Pengabaran</label>
                  <textarea id="alasanTidakPengabaran" name="alasanTidakPengabaran" class="form-control" rows="3" placeholder="Masukkan alasan..."></textarea>
                  <div id="alasanTidakPengabaranErr" class="validate-error"></div>
                </div>
              </div>

              <div class="col-md-12 m-b-10px">
                <label>Foto Kondisi Pasien</label>
                <div id="section-upload-image">
                  <div class="notes-upload-image">Format gambar .jpg .jpeg .png dan ukuran maksimal 5mb<br>Pilih foto dengan cara pilih satu per satu</div>
                  <div class="box-image-upload" id="box-1">
                    <a class="img-preview-1"><img class="img-preview-1"></a>
                    <span class="icon-plus-upload" id="icon-plus-upload-1">+</span>
                    <input type="file" class="input-file" id="upload-image-1" accept=".png, .jpg, .jpeg">
                    <div class="btn-icon btn-trash-upload-image" noUploadImage='1'><i class="fa fa-trash-o" aria-hidden="true"></i></div>
                  </div>

                  <div class="box-image-upload" id="box-2">
                    <a class="img-preview-2"><img class="img-preview-2"></a>
                    <span class="icon-plus-upload" id="icon-plus-upload-2">+</span>
                    <input type="file" class="input-file" id="upload-image-2" accept=".png, .jpg, .jpeg">
                    <div class="btn-icon btn-trash-upload-image" noUploadImage="2"><i class="fa fa-trash-o" aria-hidden="true"></i></div>
                  </div>

                  <div class="box-image-upload" id="box-3">
                    <a class="img-preview-3"><img class="img-preview-3"></a>
                    <span class="icon-plus-upload" id="icon-plus-upload-3">+</span>
                    <input type="file" class="input-file" id="upload-image-3" accept=".png, .jpg, .jpeg">
                    <div class="btn-icon btn-trash-upload-image" noUploadImage="3"><i class="fa fa-trash-o" aria-hidden="true"></i></div>
                  </div>

                  <div class="box-image-upload" id="box-4">
                    <a class="img-preview-4"><img class="img-preview-4"></a>
                    <span class="icon-plus-upload" id="icon-plus-upload-4">+</span>
                    <input type="file" class="input-file" id="upload-image-4" accept=".png, .jpg, .jpeg">
                    <div class="btn-icon btn-trash-upload-image" noUploadImage="4"><i class="fa fa-trash-o" aria-hidden="true"></i></div>
                  </div>

                  <div class="box-image-upload" id="box-5">
                    <a class="img-preview-5"><img class="img-preview-5"></a>
                    <span class="icon-plus-upload" id="icon-plus-upload-5">+</span>
                    <input type="file" class="input-file" id="upload-image-5" accept=".png, .jpg, .jpeg">
                    <div class="btn-icon btn-trash-upload-image" noUploadImage="5"><i class="fa fa-trash-o" aria-hidden="true"></i></div>
                  </div>
                </div>
                <div id="fotoKondErr1" class="validate-error"></div>
              </div>
            </form>
          </div>
        </div>
        <div class="tab-pane fade" id="kelompok_obat">
          <div class="row">
            <div class="col-md-12 section-add-kelompok-obat text-right">
              <button id="btnTambahKelompokObat" type="button" class="btn btn-primary">Tambah Kelompok Obat</button>
            </div>
            <div class="col-md-12 m-b-10px" id="locateDrawKelompokBarang">
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-12 m-t-25px">
      <div id="customErr1" class="validate-error"></div>
      <div id="beErr" class="validate-error"></div>
    </div>

    <div class="col-md-12 m-t-25px">
      {{-- <button id="testUpload" type="button" class="btn btn-primary pull-right">Upload</button> --}}
      <button id="btnSubmitHasilPemeriksaan" type="button" class="btn btn-primary pull-right">Simpan</button>
      <button id="btnKembali" type="button" class="btn btn-default pull-right m-r-10px">Kembali</button>
    </div>
  </div>

</div>

@component('layout.message-box') @endcomponent
@component('layout.modal-confirmation') @endcomponent

@endsection
@section('script-content')
  {{-- <script src="{{ asset('plugins/dropzone/dropzone.js') }}"></script> --}}
  <script src="{{ asset('plugins/magnific-popup/jquery.magnific-popup.js') }}"></script>
  <script src="{{ asset('main/js/hasil-pemeriksaan/form-hasil-pemeriksaan.js') }}"></script>
  <script src="{{ asset('main/js/hasil-pemeriksaan/kelompok-obat.js') }}"></script>
  <script src="{{ asset('main/js/hasil-pemeriksaan/upload-images.js') }}"></script>
@endsection
@section('css-content')
  <link rel="stylesheet" type='text/css' href="{{ asset('plugins/dropzone/dropzone.css') }}">
  <link rel="stylesheet" type='text/css' href="{{ asset('main/css/hasil-pemeriksaan.css') }}">
  <link rel="stylesheet" type='text/css' href="{{ asset('plugins/magnific-popup/magnific-popup.css') }}">
@endsection
@section('vue-content')@endsection
