@extends('layout.master')

@section('content')
<div class="box box-info" id="laporan-keuangan-rekap-app">
  <div class="box-header">
    <h3 class="box-title">Rekapitulasi</h3>
    <div class="inner-box-title">

      <div class="section-left-box-title" id="dateFilterSection" style="display:none;">
        <label class="label-date" id="dateFilterLabel">Pilih Tanggal</label>
        <div id="monthYearPicker">
          <div class="input-group date" id="datepickerFrom">
            <div class="input-group-addon"><i class="fa fa-calendar"></i></div>
            <input type="text" class="form-control" id="datepickerFromInput" placeholder="Dari mm-yyyy" autocomplete="off">
          </div>
          <span>s/d</span>
          <div class="input-group date" id="datepickerTo">
            <div class="input-group-addon"><i class="fa fa-calendar"></i></div>
            <input type="text" class="form-control" id="datepickerToInput" placeholder="Sampai mm-yyyy" autocomplete="off">
          </div>
        </div>
        <select id="filterYear" class="form-control" style="display:none;"></select>
      </div>

      <div class="section-right-box-title">

        <button type="button" class="btn btn-success btn-download-excel" title="Download Excel">
          <i class="fa fa-file-excel-o" aria-hidden="true"></i>&nbsp;&nbsp;Download Excel
        </button>
        <select id="filterCabang" class="filter-branch"></select>
        <select id="filterPeriode" class="filter-periode"></select>
      </div>
    </div>
  </div>

  <div class="box-body">

    <div class="box-header with-border">
      <div style="display: flex; justify-content: space-between">

      </div>
    </div>
    <div class="box-body">
      <div id="rekapWidget"></div>
    </div>

  </div>

  <div class="box-body">
    <div class="table-responsive">
      <table class="table table-striped text-nowrap">
        <thead>
          <tr>
            <th>No</th>
            <th class="onOrdering" data='dates' orderby="none">Periode <span
                class="fa fa-sort"></span></th>
            <th class="onOrdering" data='total_omset' orderby="none">Total Omset (Rp) <span
                class="fa fa-sort"></span></th>
            <th class="onOrdering" data='discount' orderby="none">Diskon (Rp) <span
                class="fa fa-sort"></span></th>
            <th class="onOrdering" data='expenses' orderby="none">Pengeluaran (Rp) <span
                class="fa fa-sort"></span></th>
            <th class="onOrdering" data='sallary' orderby="none">Penggajian (Rp) <span
                class="fa fa-sort"></span></th>
            <th class="onOrdering" data='netto' orderby="none">Netto (Rp) <span
                class="fa fa-sort"></span></th>
          </tr>
        </thead>
        <tbody id="list-laporan-keuangan-rekap"></tbody>
      </table>

      <ul class="pagination pagination-sm m-t-10px pull-left"></ul>
    </div>
  </div>

</div>

@component('layout.message-box')
@endcomponent
@endsection
@section('script-content')
<script src="{{ asset('plugins/highcharts/highstock.js') }}"></script>
<script src="{{ asset('main/js/laporan-keuangan/rekap/rekap.js') }}"></script>
<script src="{{ asset('bower_components/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js') }}"></script>
@endsection
@section('css-content')
<link rel="stylesheet" type='text/css' href="{{ asset('main/css/rekap.css') }}">
<link rel="stylesheet" href="{{ asset('bower_components/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css') }}">
@endsection
@section('vue-content')
@endsection
