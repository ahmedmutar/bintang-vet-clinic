@extends('layout.master')

@section('content')
<div class="box box-info" id="dokter-rawat-inap-app">
  <div class="box-header ">
    <h3 class="box-title">Pasien Rawat Inap</h3>
    <div class="inner-box-title pull-right">
      <div class="input-search-section">
        <input type="text" class="form-control" placeholder="cari..">
        <i class="fa fa-search" aria-hidden="true"></i>
      </div>
    </div>
  </div>

  <div class="box-body">
    <div class="table-responsive">
      <table class="table table-striped text-nowrap">
        <thead>
          <tr>
            <th>No</th>
            <th class="onOrdering" data='id_register' orderby="none">No. Registrasi <span class="fa fa-sort"></span></th>
            <th class="onOrdering" data='id_member' orderby="none">No. Pasien <span class="fa fa-sort"></span></th>
            <th class="onOrdering" data='pet_category' orderby="none">Jenis Hewan <span class="fa fa-sort"></span></th>
            <th class="onOrdering" data='pet_name' orderby="none">Nama Hewan <span class="fa fa-sort"></span></th>
            <th class="onOrdering" data='complaint' orderby="none">Keluhan <span class="fa fa-sort"></span></th>
            <th class="onOrdering" data='registrant' orderby="none">Nama Pendaftar <span class="fa fa-sort"></span></th>
            <th class="onOrdering" data='created_by' orderby="none">Dibuat Oleh <span class="fa fa-sort"></span></th>
            <th class="onOrdering" data='created_at' orderby="none">Tanggal dibuat <span class="fa fa-sort"></span></th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody id="list-dokter-rawat-inap"></tbody>
      </table>
    </div>
  </div>
  <!-- /.box-body -->

  @component('dokter.rawat-inap.detail-rawat-inap') @endcomponent
  @component('dokter.rawat-inap.modal-tolak-rawat-inap') @endcomponent
  @component('layout.modal-confirmation') @endcomponent
  @component('layout.message-box') @endcomponent
</div>
@endsection
@section('script-content')
  <script src="{{ asset('main/js/dokter/rawat-inap/rawat-inap.js') }}"></script>  
@endsection
@section('vue-content')

@endsection