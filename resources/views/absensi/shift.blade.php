@extends('layout.master')

@section('css-content')
<style>
  @media (max-width: 767px) {
    /* header filter + button stack vertically on mobile */
    .inner-box-title { flex-wrap: wrap; gap: 8px; }
    .section-left-box-title { width: 100%; }
    .section-left-box-title select { min-width: 0 !important; width: 100%; }
    /* shift modal time inputs side by side even on mobile */
    .col-xs-6 { width: 50%; float: left; padding: 0 8px; }
  }
</style>
@endsection

@section('content')
<div class="box box-info" id="shift-app">
  <div class="box-header">
    <h3 class="box-title">Master Shift</h3>
    <div class="inner-box-title">
      <div class="section-left-box-title">
        <!-- Filter cabang untuk admin -->
        <div style="display:flex; align-items:center; gap:8px;">
          <label style="margin:0; white-space:nowrap;">Filter Cabang:</label>
          <select class="form-control input-sm" v-model="filterBranchId" @change="loadShift" style="min-width:200px;">
            <option value="">Semua Cabang</option>
            <option v-for="c in listCabang" :key="c.id" :value="c.id">@{{ c.branch_name }}</option>
          </select>
        </div>
      </div>
      <div class="section-right-box-title">
        <button type="button" class="btn btn-primary btn-sm" @click="openModalTambah">
          <i class="fa fa-plus"></i> Tambah Shift
        </button>
      </div>
    </div>
  </div>

  <div class="box-body">
    <div v-if="showAlert" class="alert alert-dismissible"
      :class="{ 'alert-success': isSuccess, 'alert-danger': !isSuccess }">
      <button type="button" @click="showAlert = false" class="close">&times;</button>
      @{{ message }}
    </div>

    <div class="table-responsive">
      <table class="table table-striped text-nowrap">
        <thead>
          <tr>
            <th>No</th>
            <th>Nama Shift</th>
            <th>Cabang</th>
            <th>Jam Masuk</th>
            <th>Jam Keluar</th>
            <th>Toleransi</th>
            <th>Untuk Role</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="listShift.length === 0" class="text-center">
            <td colspan="9">Tidak ada data.</td>
          </tr>
          <tr v-for="(item, idx) in listShift" :key="item.id">
            <td>@{{ idx + 1 }}</td>
            <td>@{{ item.nama_shift }}</td>
            <td>@{{ item.branch_name }}</td>
            <td>@{{ item.jam_masuk }}</td>
            <td>@{{ item.jam_keluar }}</td>
            <td>@{{ item.toleransi_menit }} menit</td>
            <td>@{{ roleLabel(item.for_role) }}</td>
            <td>
              <span class="label" :class="item.status == 1 ? 'label-success' : 'label-danger'">
                @{{ item.status == 1 ? 'Aktif' : 'Nonaktif' }}
              </span>
            </td>
            <td>
              <button class="btn btn-xs btn-warning" @click="openModalEdit(item)" title="Edit">
                <i class="fa fa-pencil"></i>
              </button>
              <button class="btn btn-xs" :class="item.status == 1 ? 'btn-default' : 'btn-success'"
                @click="toggleStatus(item)" :title="item.status == 1 ? 'Nonaktifkan' : 'Aktifkan'">
                <i class="fa" :class="item.status == 1 ? 'fa-ban' : 'fa-check'"></i>
              </button>
              <button class="btn btn-xs btn-danger" @click="hapusShift(item)" title="Hapus">
                <i class="fa fa-trash"></i>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Modal Tambah/Edit Shift -->
  <div class="modal fade" id="modal-shift" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">@{{ isEdit ? 'Edit Shift' : 'Tambah Shift' }}</h4>
        </div>
        <div class="modal-body">

          <div class="form-group" :class="{ 'has-error': errors.id_cabang }">
            <label>Cabang <span class="text-danger">*</span></label>
            <select class="form-control" v-model="form.id_cabang">
              <option value="">-- Pilih Cabang --</option>
              <option v-for="c in listCabangForm" :key="c.id" :value="c.id">@{{ c.branch_name }}</option>
            </select>
            <span class="help-block text-danger" v-if="errors.id_cabang">@{{ errors.id_cabang }}</span>
          </div>

          <div class="form-group" :class="{ 'has-error': errors.nama_shift }">
            <label>Nama Shift <span class="text-danger">*</span></label>
            <input type="text" class="form-control" v-model="form.nama_shift" placeholder="contoh: Shift Pagi">
            <span class="help-block" v-if="errors.nama_shift">@{{ errors.nama_shift }}</span>
          </div>

          <div class="row">
            <div class="col-xs-6 col-md-6">
              <div class="form-group" :class="{ 'has-error': errors.jam_masuk }">
                <label>Jam Masuk <span class="text-danger">*</span></label>
                <input type="time" class="form-control" v-model="form.jam_masuk">
                <span class="help-block" v-if="errors.jam_masuk">@{{ errors.jam_masuk }}</span>
              </div>
            </div>
            <div class="col-xs-6 col-md-6">
              <div class="form-group" :class="{ 'has-error': errors.jam_keluar }">
                <label>Jam Keluar <span class="text-danger">*</span></label>
                <input type="time" class="form-control" v-model="form.jam_keluar">
                <span class="help-block" v-if="errors.jam_keluar">@{{ errors.jam_keluar }}</span>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label>Toleransi Keterlambatan (Menit) <span class="text-danger">*</span></label>
            <input type="number" class="form-control" v-model="form.toleransi_menit" min="0" placeholder="15">
            <span class="help-block">Batas menit setelah jam masuk yang masih dianggap tepat waktu.</span>
          </div>

          <div class="form-group">
            <label>Untuk Role</label>
            <select class="form-control" v-model="form.for_role">
              <option value="">Semua Role</option>
              <option value="admin">Admin</option>
              <option value="dokter">Dokter</option>
              <option value="resepsionis">Resepsionis</option>
              <option value="paramedis">Paramedis</option>
            </select>
            <span class="help-block">Kosongkan jika shift berlaku untuk semua role.</span>
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
          <button type="button" class="btn btn-primary" @click="simpanShift" :disabled="loadingSimpan">
            <i class="fa fa-save"></i> @{{ loadingSimpan ? 'Menyimpan...' : 'Simpan' }}
          </button>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection

@section('vue-content')
<script src="{{ asset('main/js/absensi/shift-vue.js') }}"></script>
@endsection
