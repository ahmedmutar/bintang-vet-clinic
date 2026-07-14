@extends('layout.master')

@section('content')
<div class="box box-info" id="radius-exception-app">
  <div class="box-header">
    <h3 class="box-title">Pengecualian Radius Absensi</h3>
    <div class="inner-box-title">
      <div class="section-left-box-title">
        <small class="text-muted">User dalam daftar ini tidak dikenakan validasi radius 500 meter saat absensi.</small>
      </div>
    </div>
  </div>

  <div class="box-body">
    <div v-if="showAlert" class="alert alert-dismissible"
      :class="{ 'alert-success': isSuccess, 'alert-danger': !isSuccess }">
      <button type="button" @click="showAlert = false" class="close">&times;</button>
      @{{ message }}
    </div>

    <!-- Form tambah -->
    <div class="form-inline m-b-15px">
      <div class="form-group m-r-10px" :class="{ 'has-error': inputError }">
        <select id="select-username" class="form-control" style="width:320px;">
        </select>
        <span class="help-block" v-if="inputError">@{{ inputError }}</span>
      </div>
      <button class="btn btn-primary" @click="tambahException" :disabled="loadingTambah">
        <i class="fa fa-plus"></i> @{{ loadingTambah ? 'Menyimpan...' : 'Tambah' }}
      </button>
    </div>

    <div class="table-responsive">
      <table class="table table-striped text-nowrap">
        <thead>
          <tr>
            <th>No</th>
            <th>Username</th>
            <th>Ditambahkan Oleh</th>
            <th>Tanggal</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="list.length === 0" class="text-center">
            <td colspan="5">@{{ loading ? 'Memuat data...' : 'Belum ada username yang dikecualikan.' }}</td>
          </tr>
          <tr v-for="(item, idx) in list" :key="item.id">
            <td>@{{ idx + 1 }}</td>
            <td><strong>@{{ item.username }}</strong></td>
            <td>@{{ item.created_by || '-' }}</td>
            <td>@{{ formatTanggal(item.created_at) }}</td>
            <td>
              <button class="btn btn-xs btn-danger" @click="hapus(item)" title="Hapus">
                <i class="fa fa-trash"></i> Hapus
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@section('vue-content')
<script src="{{ asset('main/js/absensi/radius-exception-vue.js') }}"></script>
@endsection
