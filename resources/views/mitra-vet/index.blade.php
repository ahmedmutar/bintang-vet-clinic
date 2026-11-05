@extends('layout.master')

@section('content')
<div class="box box-info" id="mitra-vet-app">
    <div class="box-header">
        <h3 class="box-title">Mitra Vet</h3>
        <div class="inner-box-title">
            <div class="section-left-box-title">
                <div class="nav-tabs-custom" style="margin-bottom:0;">
                    <ul class="nav nav-tabs" id="tab-mitra-vet">
                        <li class="active"><a href="#tab-stok" data-toggle="tab"><i class="fa fa-cubes"></i> Stok Barang</a></li>
                        <li><a href="#tab-tambah-barang" data-toggle="tab"><i class="fa fa-plus-square"></i> Tambah Barang</a></li>
                        <li><a href="#tab-orderan" data-toggle="tab"><i class="fa fa-shopping-cart"></i> Orderan</a></li>
                        <li><a href="#tab-penagihan" data-toggle="tab"><i class="fa fa-money"></i> Penagihan & Piutang</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="box-body">
        <div class="tab-content">

            {{-- ======================================= TAB 1: STOK BARANG ======================================= --}}
            <div class="tab-pane fade in active" id="tab-stok">

                {{-- Info Total Aset & Profit --}}
                <div class="row mitra-vet-summary" style="margin-bottom:16px;">
                    <div class="col-md-3 col-sm-6 col-xs-12">
                        <div class="info-box bg-aqua">
                            <span class="info-box-icon"><i class="fa fa-cube"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Jenis Barang</span>
                                <span class="info-box-number" id="total-jenis-barang">-</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-xs-12">
                        <div class="info-box bg-blue">
                            <span class="info-box-icon"><i class="fa fa-money"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Nilai Aset</span>
                                <span class="info-box-number" id="total-nilai-aset">Rp. -</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-xs-12">
                        <div class="info-box bg-yellow">
                            <span class="info-box-icon"><i class="fa fa-line-chart"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Profit Bulan Ini</span>
                                <span class="info-box-number" id="total-profit-bulan">Rp. -</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-xs-12">
                        <div class="info-box bg-green">
                            <span class="info-box-icon"><i class="fa fa-trophy"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Profit Keseluruhan</span>
                                <span class="info-box-number" id="total-profit-keseluruhan">Rp. -</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Grafik Rekap --}}
                <div class="box box-default" style="margin-bottom:16px;">
                    <div class="box-header with-border">
                        <h3 class="box-title">Grafik Aset Masuk & Keluar</h3>
                        <div class="box-tools pull-right" style="display:flex; align-items:center; gap:8px;">
                            <select id="filter-chart-month" class="form-control input-sm" style="width:auto;">
                                <option value="1">Januari</option>
                                <option value="2">Februari</option>
                                <option value="3">Maret</option>
                                <option value="4">April</option>
                                <option value="5">Mei</option>
                                <option value="6">Juni</option>
                                <option value="7">Juli</option>
                                <option value="8">Agustus</option>
                                <option value="9">September</option>
                                <option value="10">Oktober</option>
                                <option value="11">November</option>
                                <option value="12">Desember</option>
                            </select>
                            <select id="filter-chart-year" class="form-control input-sm" style="width:auto;"></select>
                        </div>
                    </div>
                    <div class="box-body">
                        <div id="chart-mitra-vet" style="width:100%; min-height:300px;"></div>
                        <div class="row" style="margin-top:12px;">
                            <div class="col-sm-6">
                                <div class="callout callout-success">
                                    <h4>Total Masuk Bulan Ini</h4>
                                    <p>Qty: <strong id="summary-masuk-qty">-</strong> &nbsp;|&nbsp; Nilai: <strong id="summary-masuk-nilai">Rp. -</strong></p>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="callout callout-danger">
                                    <h4>Total Keluar Bulan Ini</h4>
                                    <p>Qty: <strong id="summary-keluar-qty">-</strong> &nbsp;|&nbsp; Nilai: <strong id="summary-keluar-nilai">Rp. -</strong></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Toolbar Tabel Stok --}}
                <div class="inner-box-title" style="margin-bottom:10px;">
                    <div class="section-left-box-title">
                        <button id="btnExportStockLog" class="btn btn-success btn-sm" title="Download Excel Log Mutasi Stok (Masuk, Keluar, dan Total Sisa Stok)">
                            <i class="fa fa-file-excel-o"></i> Download Log Mutasi Stok
                        </button>
                    </div>
                    <div class="section-right-box-title">
                        <div class="input-search-section">
                            <input type="text" class="form-control" id="search-stok" placeholder="cari nama barang..">
                            <i class="fa fa-search" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>

                {{-- Tabel Stok --}}
                <div class="table-responsive">
                    <table class="table table-striped text-nowrap">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Barang</th>
                                <th>Satuan</th>
                                <th>Stok Real</th>
                                <th>Harga Modal</th>
                                <th>Harga Jual</th>
                                <th>Profit / Item</th>
                                <th>Nilai Aset</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="list-stok-mitra-vet">
                            <tr class="text-center">
                                <td colspan="9">Memuat data...</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="7" class="text-right"><strong>Total Nilai Aset:</strong></td>
                                <td><strong id="tfoot-total-aset">Rp. -</strong></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                    <ul class="pagination pagination-sm m-t-10px pull-left" id="pagination-stok"></ul>
                </div>
            </div>

            {{-- ======================================= TAB 2: TAMBAH BARANG ======================================= --}}
            <div class="tab-pane fade" id="tab-tambah-barang">
                <div class="row">
                    {{-- Form Tambah Satuan / Manual --}}
                    <div class="col-md-6 col-sm-12">
                        <div class="box box-primary">
                            <div class="box-header with-border">
                                <h4 class="box-title"><i class="fa fa-pencil-square-o"></i> Input Barang Baru (Manual)</h4>
                            </div>
                            <div class="box-body">
                                <form id="form-tab-tambah-barang">
                                    <div class="form-group">
                                        <label>Nama Barang <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="tab-nama-barang" placeholder="Masukkan nama barang lengkap">
                                        <div id="err-tab-nama" class="validate-error"></div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>Satuan <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="tab-satuan" placeholder="contoh: Botol, Pcs, Strip">
                                                <div id="err-tab-satuan" class="validate-error"></div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>Stok Awal <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control" id="tab-stok-awal" placeholder="0" min="0" value="0">
                                                <div id="err-tab-stok" class="validate-error"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>Harga Modal (Rp) <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="tab-harga-modal" placeholder="contoh: 30000">
                                                <div id="err-tab-modal" class="validate-error"></div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>Harga Jual (Rp) <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="tab-harga-jual" placeholder="contoh: 50000">
                                                <div id="err-tab-jual" class="validate-error"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Estimasi Profit / Keuntungan (Rp)</label>
                                        <input type="text" class="form-control" id="tab-profit" placeholder="Rp 0" readonly style="background-color:#eef2f7; font-weight:bold; color:#1a6596;">
                                        <small class="text-muted"><i class="fa fa-info-circle"></i> Dihitung otomatis: <code>Harga Jual - Harga Modal</code>. Harga Jual harus lebih besar dari Harga Modal.</small>
                                    </div>
                                    <div id="err-tab-global" class="validate-error" style="margin-bottom:10px;"></div>
                                    <button type="button" class="btn btn-primary" id="btnSimpanTabBarang">
                                        <i class="fa fa-save"></i> Simpan Barang
                                    </button>
                                    <button type="reset" class="btn btn-default" id="btnResetTabBarang" style="margin-left:5px;">
                                        Reset
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- Form Upload Excel Massal --}}
                    <div class="col-md-6 col-sm-12">
                        <div class="box box-warning">
                            <div class="box-header with-border">
                                <h4 class="box-title"><i class="fa fa-file-excel-o"></i> Input Multiple Produk (Upload Excel)</h4>
                            </div>
                            <div class="box-body">
                                <div class="alert alert-info" style="font-size:13px;">
                                    <strong><i class="fa fa-info-circle"></i> Petunjuk Pengisian:</strong>
                                    <ol style="margin-top:6px; margin-bottom:0; padding-left:18px;">
                                        <li>Unduh template Excel resmi terlebih dahulu.</li>
                                        <li>Isi kolom: <code>nama_barang</code>, <code>satuan</code>, <code>harga_modal</code>, <code>harga_jual</code>, <code>stok</code>.</li>
                                        <li><strong>Validasi harga:</strong> <code>harga_jual</code> harus lebih besar dari <code>harga_modal</code>.</li>
                                        <li>Jika nama barang sudah ada, sistem akan <strong>menambahkan stoknya</strong> dan memperbarui harga modal & jual.</li>
                                        <li>Maksimal ukuran file 5MB (format <code>.xlsx</code> atau <code>.xls</code>).</li>
                                    </ol>
                                </div>

                                <div style="margin-bottom:15px;">
                                    <button type="button" class="btn btn-default btn-download-template-act">
                                        <i class="fa fa-download text-green"></i> <strong>Download Template Excel</strong>
                                    </button>
                                </div>

                                <div id="tab-upload-result" style="display:none; margin-bottom:12px;"></div>

                                <div class="form-group">
                                    <label>Pilih File Excel Hasil Pengisian <span class="text-danger">*</span></label>
                                    <input type="file" id="tab-file-excel" accept=".xls,.xlsx" class="form-control">
                                    <div id="err-tab-upload-file" class="validate-error"></div>
                                </div>

                                <div id="tab-upload-progress" style="display:none; margin-bottom:10px;">
                                    <div class="progress">
                                        <div class="progress-bar progress-bar-striped active" role="progressbar" style="width:100%">
                                            Memvalidasi & memproses data...
                                        </div>
                                    </div>
                                </div>

                                <button type="button" class="btn btn-warning" id="btnTabProsesUpload">
                                    <i class="fa fa-upload"></i> Upload & Proses Data
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ======================================= TAB 3: ORDERAN ======================================= --}}
            <div class="tab-pane fade" id="tab-orderan">

                <div class="inner-box-title" style="margin-bottom:10px;">
                    <div class="section-left-box-title">
                        <button id="btnBuatOrder" class="btn btn-primary btn-sm">
                            <i class="fa fa-shopping-cart"></i> Buat Orderan
                        </button>
                        <button id="btnExportOrderExcel" class="btn btn-success btn-sm" title="Download Excel Orderan (Disort berdasarkan Nama Pembeli)">
                            <i class="fa fa-file-excel-o"></i> Download Excel Orderan
                        </button>
                        <button id="btnModalStrukPelanggan" class="btn btn-info btn-sm" title="Download Struk / Rekap PDF Riwayat Order per Pelanggan (Bulanan atau Tahunan)">
                            <i class="fa fa-file-pdf-o"></i> Download Struk Pelanggan (PDF)
                        </button>
                    </div>
                    <div class="section-right-box-title" style="display:flex; align-items:center; gap:8px;">
                        <select id="filter-order-month" class="form-control input-sm" style="width:auto;">
                            <option value="">-- Semua Bulan --</option>
                            <option value="1">Januari</option>
                            <option value="2">Februari</option>
                            <option value="3">Maret</option>
                            <option value="4">April</option>
                            <option value="5">Mei</option>
                            <option value="6">Juni</option>
                            <option value="7">Juli</option>
                            <option value="8">Agustus</option>
                            <option value="9">September</option>
                            <option value="10">Oktober</option>
                            <option value="11">November</option>
                            <option value="12">Desember</option>
                        </select>
                        <select id="filter-order-year" class="form-control input-sm" style="width:auto;"></select>
                        <div class="input-search-section">
                            <input type="text" class="form-control" id="search-order" placeholder="cari nama / no order..">
                            <i class="fa fa-search" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped text-nowrap">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>No. Order</th>
                                <th>Nama Pembeli</th>
                                <th>No HP</th>
                                <th>Detail Barang</th>
                                <th>Total Harga</th>
                                <th>Ongkir</th>
                                <th>Grand Total</th>
                                <th>Dibuat Oleh</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="list-orderan-mitra-vet">
                            <tr class="text-center">
                                <td colspan="11">Memuat data...</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr style="background-color: #f8fafc; font-size: 12.5px; border-top: 2px solid #cbd5e1;">
                                <td colspan="5" class="text-right" style="vertical-align: middle;"><strong>Grand Total Keseluruhan:</strong></td>
                                <td style="vertical-align: middle;"><strong id="tfoot-total-harga-order">Rp 0</strong></td>
                                <td style="vertical-align: middle;"><strong id="tfoot-total-ongkir-order">Rp 0</strong></td>
                                <td style="vertical-align: middle;"><strong class="text-primary" id="tfoot-grand-total-order" style="font-size: 14px;">Rp 0</strong></td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                    <ul class="pagination pagination-sm m-t-10px pull-left" id="pagination-orderan"></ul>
                </div>
            </div>

            {{-- ======================================= TAB 4: PENAGIHAN & PIUTANG ======================================= --}}
            <div class="tab-pane fade" id="tab-penagihan">

                {{-- Summary Cards Piutang --}}
                <div class="row" style="margin-bottom:16px;">
                    <div class="col-md-4 col-sm-6 col-xs-12">
                        <div class="info-box bg-red">
                            <span class="info-box-icon"><i class="fa fa-exclamation-circle"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Sisa Piutang Belum Lunas</span>
                                <span class="info-box-number" id="piutang-total-sisa">Rp 0</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-6 col-xs-12">
                        <div class="info-box bg-yellow">
                            <span class="info-box-icon"><i class="fa fa-calendar-times-o"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Jatuh Tempo Bulan Ini</span>
                                <span class="info-box-number" id="piutang-total-tempo">Rp 0</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-6 col-xs-12">
                        <div class="info-box bg-green">
                            <span class="info-box-icon"><i class="fa fa-check-circle"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Pembayaran Tertagih</span>
                                <span class="info-box-number" id="piutang-total-terbayar">Rp 0</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Toolbar Penagihan --}}
                <div class="inner-box-title" style="margin-bottom:10px;">
                    <div class="section-left-box-title" style="display:flex; align-items:center; gap:8px;">
                        <select id="filter-piutang-status" class="form-control input-sm" style="width:auto;">
                            <option value="">-- Semua Status --</option>
                            <option value="belum_lunas" selected>Belum Lunas / Cicilan</option>
                            <option value="jatuh_tempo">Jatuh Tempo</option>
                            <option value="lunas">Lunas</option>
                        </select>
                        <select id="filter-piutang-customer" class="form-control input-sm" style="width:auto; min-width:200px;">
                            <option value="">-- Semua Pelanggan --</option>
                        </select>
                    </div>
                    <div class="section-right-box-title">
                        <div class="input-search-section">
                            <input type="text" class="form-control" id="search-piutang" placeholder="cari no order / pelanggan..">
                            <i class="fa fa-search" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>

                {{-- Tabel Penagihan & Piutang --}}
                <div class="table-responsive">
                    <table class="table table-striped text-nowrap">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>No. Order</th>
                                <th>Pelanggan / Mitra</th>
                                <th>Tgl Order</th>
                                <th>Jatuh Tempo</th>
                                <th>Total Tagihan</th>
                                <th>Terbayar</th>
                                <th>Sisa Piutang</th>
                                <th>Termin</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="list-penagihan-mitra-vet">
                            <tr class="text-center">
                                <td colspan="11">Memuat data piutang...</td>
                            </tr>
                        </tbody>
                    </table>
                    <ul class="pagination pagination-sm m-t-10px pull-left" id="pagination-penagihan"></ul>
                </div>
            </div>

        </div>
    </div>

    {{-- Modals --}}
    @component('mitra-vet.modal-tambah-barang')
    @endcomponent

    @component('mitra-vet.modal-tambah-stok')
    @endcomponent

    @component('mitra-vet.modal-order')
    @endcomponent

    @component('mitra-vet.modal-struk-pelanggan')
    @endcomponent

    @component('mitra-vet.modal-bayar-cicilan')
    @endcomponent

    @component('mitra-vet.modal-riwayat-cicilan')
    @endcomponent

    @component('layout.modal-confirmation')
    @endcomponent

    @component('layout.message-box')
    @endcomponent

</div>
@endsection

@section('css-content')
<link rel="stylesheet" type='text/css' href="{{ asset('main/css/mitra-vet.css') }}">
@endsection

@section('script-content')
<script src="{{ asset('plugins/highcharts/highstock.js') }}"></script>
<script src="{{ asset('main/js/mitra-vet/mitra-vet.js') }}"></script>
@endsection

@section('vue-content')
@endsection
