@extends('layout.master')

@section('content')
<div class="box box-info" id="user-app">
  <div class="box-header ">
    <h3 class="box-title">User Management</h3>
    <div class="inner-box-title">
      <div class="section-left-box-title">
        <button class="btn btn-info openFormAdd">Tambah</button>
      </div>
      <div class="section-right-box-title">
        <div class="input-search-section m-r-10px">
          <input type="text" class="form-control" placeholder="cari..">
          <i class="fa fa-search" aria-hidden="true"></i>
        </div>
        <select id="filterCabang" style="width: 50%"></select>
      </div>
    </div>
  </div>
  <!-- /.box-header -->

  <div class="box-body">
    <div class="table-responsive">
      <table class="table table-striped text-nowrap">
        <thead>
          <tr>
            <th>No</th>
            <th class="onOrdering" data='staffing_number' orderby="none">No. Kepegawaian <span class="fa fa-sort"></span></th>
            <th class="onOrdering" data='username' orderby="none">Username <span class="fa fa-sort"></span></th>
            <th class="onOrdering" data='fullname' orderby="none">Nama Lengkap <span class="fa fa-sort"></span></th>
            <th class="onOrdering" data='email' orderby="none">Email <span class="fa fa-sort"></span></th>
            <th class="onOrdering" data='role' orderby="none">Role User <span class="fa fa-sort"></span></th>
            <th class="onOrdering" data='branch_name' orderby="none">Cabang <span class="fa fa-sort"></span></th>
            <th class="onOrdering" data='status' orderby="none">status <span class="fa fa-sort"></span></th>
            <th class="onOrdering" data='created_by' orderby="none">Dibuat oleh <span class="fa fa-sort"></span></th>
            <th class="onOrdering" data='created_at' orderby="none">Tanggal dibuat <span class="fa fa-sort"></span></th>
            <th class="columnAction">Aksi</th>
          </tr>
        </thead>
        <tbody id="list-user">
          <tr class="text-center"><td colspan="11">Tidak ada data.</td></tr>
        </tbody>
      </table>
    </div>
  </div>
  <!-- /.box-body -->

  @component('user.modal-user') @endcomponent
  @component('user.modal-reset-password') @endcomponent
  @component('layout.modal-confirmation') @endcomponent
  @component('layout.message-box') @endcomponent
</div>
@endsection

@section('script-content')
  <script src="{{ asset('main/js/user/user.js') }}"></script>
@endsection
@section('css-content')
  <link rel="stylesheet" type='text/css' href="{{ asset('main/css/user.css') }}">
@endsection
@section('vue-content')@endsection
