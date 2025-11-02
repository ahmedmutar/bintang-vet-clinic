@extends('layout.master')

@section('content')
<div class="box box-info" id="cabang-app">
  <div class="box-header ">
    <h3 class="box-title">Cabang</h3>
    <div class="inner-box-title">
      <button class="btn btn-info" @click="openFormAdd">Tambah</button>
      <div class="input-search-section">
        <input type="text" class="form-control" placeholder="cari.." v-model="searchTxt" @keydown.enter="onSearch">
        <i class="fa fa-search" aria-hidden="true" @click="onSearch"></i>
      </div>
    </div>
  </div>
  <!-- /.box-header -->
  <!-- form start -->
  <div class="box-body">
    <div class="table-responsive">
      <table class="table table-striped text-nowrap">
        <thead>
          <tr>
            <th>No</th>
            <th @click="onOrdering('branch_code')">Kode Cabang
              <span v-if="columnStatus.branch_code == 'desc'" class="fa fa-sort-desc"></span>
              <span v-if="columnStatus.branch_code == 'asc'" class="fa fa-sort-asc"></span>
              <span v-if="columnStatus.branch_code == 'none'" class="fa fa-sort"></span>
            </th>
            <th @click="onOrdering('branch_name')">Cabang
              <span v-if="columnStatus.branch_name == 'desc'" class="fa fa-sort-desc"></span>
              <span v-if="columnStatus.branch_name == 'asc'" class="fa fa-sort-asc"></span>
              <span v-if="columnStatus.branch_name == 'none'" class="fa fa-sort"></span>
            </th>
            <th @click="onOrdering('address')">Alamat
              <span v-if="columnStatus.address == 'desc'" class="fa fa-sort-desc"></span>
              <span v-if="columnStatus.address == 'asc'" class="fa fa-sort-asc"></span>
              <span v-if="columnStatus.address == 'none'" class="fa fa-sort"></span>
            </th>
            <th @click="onOrdering('payment_instruction')">Instruksi Pembayaran
              <span v-if="columnStatus.payment_instruction == 'desc'" class="fa fa-sort-desc"></span>
              <span v-if="columnStatus.payment_instruction == 'asc'" class="fa fa-sort-asc"></span>
              <span v-if="columnStatus.payment_instruction == 'none'" class="fa fa-sort"></span>
            </th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item, index) in listCabang">
            <td>@{{ index + 1 }}</td>
            <td>@{{ item.branch_code }}</td>
            <td>@{{ item.branch_name }}</td>
            <td>@{{ item.address }}</td>
            <td>@{{ item.payment_instruction }}</td>
            <td>
              <button type="button" class="btn btn-warning" @click="openFormUpdate(item)"><i class="fa fa-pencil" aria-hidden="true"></i></button>
              <button type="button" class="btn btn-danger" @click="openFormDelete(item)"><i class="fa fa-trash-o" aria-hidden="true"></i></button>
            </td>
          </tr>
          <tr class="text-center" v-if="!listCabang.length">
            <td colspan="6">Tidak ada data.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
  <!-- /.box-body -->

  <div class="modal fade" id="modal-cabang">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title">@{{titleModal}}</h4>
        </div>
        <div class="modal-body">
          <form>
            <div class="box-body">
              <div class="form-group">
                <label for="kodeCabang">Kode Cabang</label>
                <input type="text" class="form-control" @keyup="kodeCabangKeyup" v-model="kodeCabang" placeholder="Masukan kode cabang" :disabled="stateModal === 'edit'">
                <div class="validate-error" v-if="kdCabangErr1">Kode Cabang harus di isi</div>
                <div class="validate-error" v-if="kdCabangErr2">Kode Cabang harus huruf besar dan tidak ada spasi</div>
              </div>
              <div class="form-group">
                <label for="cabang">Cabang</label>
                <input type="text" class="form-control" @keyup="namaCabangKeyup" v-model="namaCabang" placeholder="Masukan nama cabang">
                <div class="validate-error" v-if="namaCabangErr">Nama cabang harus di isi</div>
              </div>
              <div class="form-group">
                <label for="alamat">Alamat</label>
                <input type="text" class="form-control" @keyup="alamatCabangKeyup" v-model="alamatCabang" placeholder="Masukan alamat cabang">
                <div class="validate-error" v-if="alamatErr">Alamat minimal 5 karakter</div>
              </div>
              <div class="form-group">
                <label for="instruksiPembayaran">Instruksi Pembayaran</label>
                <input type="text" class="form-control" @keyup="instruksiPembayaranKeyup" v-model="instruksiPembayaran" placeholder="Masukan instruksi pembayaran">
                <div class="validate-error" v-if="instruksiPembayaranErr">Instruksi Pembayaran minimal 5 karakter</div>
              </div>
              <div class="form-group">
                <div class="validate-error" v-if="beErr" v-html="msgBeErr"></div>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary" :disabled="validateSimpanCabang || !touchedForm" @click="submitCabang">Simpan</button>
        </div>
      </div>
      <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
  </div>

  @component('layout.modal-confirmation') @endcomponent

  @component('layout.message-box') @endcomponent

</div>
@endsection

@section('script-content')
<script>
  // $('#table-cabang').DataTable({
  //   'paging'     : false,
  //   'searching'  : false,
  //   "info"       : false,
  //   "columnDefs" : [{ "orderable": false, "targets": 3 }],
  //   responsive: true
  // });
</script>
@endsection

@section('vue-content')
  <script src="{{ asset('main/js/cabang/cabang-vue.js') }}"></script>
@endsection
