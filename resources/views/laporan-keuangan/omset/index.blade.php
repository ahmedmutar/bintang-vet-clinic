@extends('layout.master')

@section('content')
<div class="box box-info" id="laporan-keuangan-omset-app">
  <div class="box-header">
    <h3 class="box-title">Rekapitulasi Omset</h3>
    <div class="inner-box-title">

      <div class="section-left-box-title">
        <div class="btn-group" id="periode-omset-group">
          <button class="btn btn-sm btn-default" data-periode="mingguan">Mingguan</button>
          <button class="btn btn-sm btn-default" data-periode="bulanan">Bulanan</button>
          <button class="btn btn-sm btn-default" data-periode="tahunan">Tahunan</button>
          <button class="btn btn-sm btn-default" data-periode="sejak_dibuka">Sejak Dibuka</button>
        </div>

        <div id="filter-mingguan" class="filter-periode-input" style="display:none;">
          <div class="input-group date omset-range-group">
            <div class="input-group-addon" style="cursor:pointer;">
              <i class="fa fa-calendar"></i>
            </div>
            <input type="text" class="form-control omset-range-input" id="datepicker-omset-range"
                   placeholder="yyyy-mm-dd - yyyy-mm-dd" autocomplete="off" readonly>
          </div>
        </div>

        <div id="filter-bulanan" class="filter-periode-input" style="display:none;">
          <div class="input-group input-group-sm filter-bulanan-group">
            <input type="text" id="inputBulanFrom" class="form-control" placeholder="mm-yyyy" readonly style="cursor:pointer;">
            <span class="input-group-addon" id="addonBulanFrom" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
          </div>
          <span class="filter-sep">s/d</span>
          <div class="input-group input-group-sm filter-bulanan-group">
            <input type="text" id="inputBulanTo" class="form-control" placeholder="mm-yyyy" readonly style="cursor:pointer;">
            <span class="input-group-addon" id="addonBulanTo" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
          </div>
        </div>

        <div id="filter-tahunan" class="filter-periode-input" style="display:none;">
          <select id="selectTahun" class="form-control input-sm filter-tahun-select"></select>
        </div>
      </div>

      <div class="section-right-box-title">
        <button type="button" id="btn-export-omset" class="btn btn-success btn-sm" title="Generate Excel" style="display:none;">
          <i class="fa fa-file-excel-o" aria-hidden="true"></i>&nbsp;&nbsp;Generate Excel
        </button>
        <div id="cabang-filter-wrapper">
          <select id="filterCabang" class="filter-branch"></select>
        </div>
      </div>
    </div>
  </div>

  <div class="box-body">
    <div class="box-body">
      <div id="rekapWidgetOmset" style="width:100%; height:100%"></div>
    </div>
  </div>

  <div class="box-body">
    <div class="table-responsive">
      <table class="table table-striped text-nowrap">
        <thead id="head-laporan-keuangan-omset"></thead>
        <tbody id="list-laporan-keuangan-omset"></tbody>
      </table>
    </div>
  </div>
</div>

@component('layout.message-box')
@endcomponent
@endsection
@section('script-content')
<script src="{{ asset('plugins/highcharts/highstock.js') }}"></script>
<script src="{{ asset('bower_components/moment/min/moment.min.js') }}"></script>
<script src="{{ asset('bower_components/bootstrap-daterangepicker/daterangepicker.js') }}"></script>
<script src="{{ asset('bower_components/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js') }}"></script>
<script src="{{ asset('main/js/laporan-keuangan/omset/omset.js') }}"></script>
@endsection
@section('css-content')
<link rel="stylesheet" type='text/css' href="{{ asset('main/css/rekap.css') }}">
<link rel="stylesheet" href="{{ asset('bower_components/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css') }}">
<link rel="stylesheet" href="{{ asset('bower_components/bootstrap-daterangepicker/daterangepicker.css') }}">
@endsection
@section('vue-content')
@endsection
