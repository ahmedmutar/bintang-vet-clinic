@extends('layout.master')

@section('content')
<div class="row">

    <div class="col-md-12 daftar-barang-limit-expired">
        <div class="box box-info" id="daftar-barang-limit-expired-app">
            <div class="box-header ">
                <h3 class="box-title">Daftar barang limit dan expired</h3>
                <div class="nav-tabs-custom m-t-25px">
                    <ul class="nav nav-tabs">
                        <li class="active"><a href="#clinic" data-toggle="tab">Klinik</a></li>
                        <li><a href="#pet_shop" data-toggle="tab">Pet Shop</a></li>
                    </ul>

                    <div id="tab-content" class="tab-content">

                        <div class="tab-pane fade in active" id="clinic">

                            <div class="inner-box-title">
                                <div class="section-left-box-title"></div>
                                <div class="section-right-box-title"></div>
                            </div>

                            <div class="box-body">
                                <div class="table-responsive">
                                    <table class="table table-striped text-nowrap">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th class="onOrdering" data='item_name' orderby="none">Nama Barang <span class="fa fa-sort"></span></th>
                                                <th class="onOrdering" data='total_item' orderby="none">Jumlah <span class="fa fa-sort"></span></th>
                                                <th class="onOrdering" data='unit_name' orderby="none">Satuan <span class="fa fa-sort"></span></th>
                                                <th class="onOrdering" data='category_name' orderby="none">Kategori <span class="fa fa-sort"></span></th>
                                                <th class="onOrdering" data='branch_name' orderby="none">Cabang <span class="fa fa-sort"></span></th>
                                                <th class="onOrdering" data='created_by' orderby="none">Dibuat oleh <span class="fa fa-sort"></span></th>
                                                <th class="onOrdering" data='created_at' orderby="none">Tanggal dibuat <span class="fa fa-sort"></span></th>
                                                <th class="onOrdering" data='expired_date' orderby="none">Tanggal kedaluwarsa <span class="fa fa-sort"></span></th>
                                            </tr>
                                        </thead>
                                        <tbody id="list-daftar-barang-limit">
                                            <tr class="text-center">
                                                <td colspan="9">Tidak ada data.</td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <ul class="pagination pagination-sm m-t-10px pull-left"></ul>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="pet_shop">
                            <div class="inner-box-title">
                                <div class="section-left-box-title"></div>
                                <div class="section-right-box-title-pet"></div>
                            </div>

                            <div class="box-body">
                                <div class="table-responsive">
                                    <table class="table table-striped text-nowrap">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th class="onOrderingPet" data='item_name' orderby="none">Nama Barang <span class="fa fa-sort"></span></th>
                                                <th class="onOrderingPet" data='total_item' orderby="none">Jumlah <span class="fa fa-sort"></span></th>
                                                <th class="onOrderingPet" data='branch_name' orderby="none">Cabang <span class="fa fa-sort"></span></th>
                                                <th class="onOrderingPet" data='created_by' orderby="none">Dibuat oleh <span class="fa fa-sort"></span></th>
                                                <th class="onOrderingPet" data='created_at' orderby="none">Tanggal dibuat <span class="fa fa-sort"></span></th>
                                                <th class="onOrderingPet" data='expired_date' orderby="none">Tanggal kedaluwarsa <span class="fa fa-sort"></span></th>
                                            </tr>
                                        </thead>
                                        <tbody id="list-daftar-barang-limit-pet-shop">
                                            <tr class="text-center">
                                                <td colspan="9">Tidak ada data.</td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <ul class="pagination pagination-sm m-t-10px pull-left"></ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.box-body -->
        </div>

        <div class="col-md-12 pasien">
            <div class="box box-info">
                <div class="box-header with-border">
                    <div class="pasien-header">
                        <div class="box-title" id="jumlah-pasien-title">Jumlah pasien per cabang per bulan</div>
                        <div class="pasien-tools">
                            <div class="btn-group" id="periode-pasien-group">
                                <button type="button" class="btn btn-xs btn-default" data-periode="harian">Harian</button>
                                <button type="button" class="btn btn-xs btn-default" data-periode="mingguan">Mingguan</button>
                                <button type="button" class="btn btn-xs btn-default active" data-periode="bulanan">Bulanan</button>
                            </div>
                            <div id="datepicker-single-wrapper">
                                <div class="input-group date">
                                    <div class="input-group-addon">
                                        <i class="fa fa-calendar"></i>
                                    </div>
                                    <input type="text" class="form-control" id="datepicker-jumlah-pasien" placeholder="mm-yyyy" autocomplete="off">
                                </div>
                            </div>
                            <div id="datepicker-range-wrapper" style="display:none;">
                                <div class="input-group date pasien-range-group" id="pasien-range-group">
                                    <div class="input-group-addon">
                                        <i class="fa fa-calendar"></i>
                                    </div>
                                    <input type="text" class="form-control pasien-range-input" id="datepicker-pasien-range" placeholder="yyyy-mm-dd - yyyy-mm-dd" autocomplete="off" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="box-body">
                    <div id="totalPasienWidget" style="width:100%; height:100%"></div>
                    <hr style="margin:16px 0 12px;">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered" id="tabel-pasien-cabang" style="font-size:13px;">
                            <thead id="tabel-pasien-cabang-head"></thead>
                            <tbody id="tabel-pasien-cabang-body">
                                <tr class="text-center"><td colspan="2">Memuat data...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-12 rawat-inap">
            <div class="box box-info">
                <div class="box-header with-border">
                    <div class="rawat-inap-header">
                        <div class="box-title" id="rawat-inap-title">Rawat Inap per Bulan</div>
                        <div class="rawat-inap-tools">
                            <div class="btn-group" id="periode-rawat-inap-group">
                                <button type="button" class="btn btn-xs btn-default" data-periode="harian">Harian</button>
                                <button type="button" class="btn btn-xs btn-default" data-periode="mingguan">Mingguan</button>
                                <button type="button" class="btn btn-xs btn-default active" data-periode="bulanan">Bulanan</button>
                            </div>
                            <div id="datepicker-single-rawat-inap-wrapper">
                                <div class="input-group date">
                                    <div class="input-group-addon">
                                        <i class="fa fa-calendar"></i>
                                    </div>
                                    <input type="text" class="form-control" id="datepicker-rawat-inap" placeholder="mm-yyyy" autocomplete="off">
                                </div>
                            </div>
                            <div id="datepicker-range-rawat-inap-wrapper" style="display:none;">
                                <div class="input-group date rawat-inap-range-group" id="rawat-inap-range-group">
                                    <div class="input-group-addon">
                                        <i class="fa fa-calendar"></i>
                                    </div>
                                    <input type="text" class="form-control rawat-inap-range-input" id="datepicker-rawat-inap-range" placeholder="yyyy-mm-dd - yyyy-mm-dd" autocomplete="off" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="box-body">
                    <div id="rawatInapWidget" style="width:100%; min-height:300px;"></div>
                </div>
            </div>
        </div>

        {{-- Grafik Pasien Tidak Pengabaran --}}
        <div class="col-md-12 tidak-pengabaran">
            <div class="box box-info">
                <div class="box-header with-border">
                    <div class="tidak-pengabaran-header">
                        <div class="box-title" id="tidak-pengabaran-title">
                            Pasien tidak pengabaran per bulan
                        </div>
                        <div class="tidak-pengabaran-tools">
                            <div class="btn-group" id="periode-tidak-pengabaran-group">
                                <button type="button" class="btn btn-xs btn-default" data-periode="harian">Harian</button>
                                <button type="button" class="btn btn-xs btn-default" data-periode="mingguan">Mingguan</button>
                                <button type="button" class="btn btn-xs btn-default active" data-periode="bulanan">Bulanan</button>
                            </div>
                            <div id="datepicker-single-tidak-pengabaran-wrapper" class="tp-datepicker-wrap">
                                <div class="input-group date">
                                    <div class="input-group-addon"><i class="fa fa-calendar"></i></div>
                                    <input type="text" class="form-control tp-date-input" id="datepicker-tidak-pengabaran" placeholder="mm-yyyy" autocomplete="off">
                                </div>
                            </div>
                            <div id="datepicker-range-tidak-pengabaran-wrapper" class="tp-datepicker-wrap" style="display:none;">
                                <div class="input-group" id="tidak-pengabaran-range-group" style="cursor:pointer;">
                                    <div class="input-group-addon"><i class="fa fa-calendar"></i></div>
                                    <input type="text" class="form-control tp-range-input" id="datepicker-tidak-pengabaran-range"
                                        placeholder="yyyy-mm-dd - yyyy-mm-dd" autocomplete="off" readonly style="cursor:pointer;">
                                </div>
                            </div>
                            <select id="filterCabangTidakPengabaran"></select>
                        </div>
                    </div>
                </div>
                <div class="box-body">
                    <div id="tidakPengabaranChart" style="width:100%; min-height:280px;"></div>
                    <hr style="margin:16px 0 12px;">
                    <h4 style="margin:0 0 10px; font-size:14px; font-weight:600; color:#555;">
                        Daftar Pasien Tidak Dilakukan Pengabaran
                    </h4>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-sm" style="font-size:13px;">
                            <thead>
                                <tr>
                                    <th style="width:40px;">No</th>
                                    <th>Nama Hewan</th>
                                    <th>Nama Pemilik</th>
                                    <th>Cabang</th>
                                    <th>Alasan</th>
                                    <th style="width:110px;">Tanggal</th>
                                </tr>
                            </thead>
                            <tbody id="list-tidak-pengabaran">
                                <tr class="text-center"><td colspan="6">Memuat data...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <ul class="pagination pagination-sm m-t-10px pull-left" id="pagination-tidak-pengabaran"></ul>
                </div>
            </div>
        </div>

    </div>
    @endsection

    @section('script-content')
    <script src="{{ asset('plugins/highcharts/highstock.js') }}"></script>
    <script src="{{ asset('bower_components/moment/min/moment.min.js') }}"></script>
    <script src="{{ asset('bower_components/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js') }}"></script>
    <script src="{{ asset('bower_components/bootstrap-daterangepicker/daterangepicker.js') }}"></script>
    <script src="{{ asset('main/js/index.js') }}"></script>
    @endsection
    @section('css-content')
    <link rel="stylesheet" type='text/css' href="{{ asset('main/css/index.css') }}">
    <link rel="stylesheet" href="{{ asset('bower_components/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css') }}">
    <link rel="stylesheet" href="{{ asset('bower_components/bootstrap-daterangepicker/daterangepicker.css') }}">
    @endsection