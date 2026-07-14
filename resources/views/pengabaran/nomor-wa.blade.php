@extends('layout.master')

@section('content')
<div class="box box-info" id="nomor-wa-app">
  <div class="box-header">
    <h3 class="box-title">Nomor WhatsApp &amp; Token per Cabang</h3>
  </div>

  <div class="box-body">
    <div v-if="showAlert" class="alert alert-dismissible"
      :class="{ 'alert-success': isSuccess, 'alert-danger': !isSuccess }">
      <button type="button" @click="showAlert = false" class="close">&times;</button>
      @{{ message }}
    </div>

    <p class="text-muted">
      Setiap cabang dapat menggunakan token sendiri sehingga pesan pengabaran dikirim dari nomor WhatsApp cabang masing-masing.
      Jika token cabang kosong, sistem akan menggunakan token global dari <strong>Pengaturan Token</strong>.
    </p>

    <div class="table-responsive">
      <table class="table table-striped table-bordered">
        <thead>
          <tr>
            <th style="width:40px;">No</th>
            <th>Nama Cabang</th>
            <th>Nomor WhatsApp</th>
            <th>Token WA Cabang</th>
            <th style="width:100px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="list.length === 0" class="text-center">
            <td colspan="5">@{{ loading ? 'Memuat data...' : 'Tidak ada data cabang.' }}</td>
          </tr>
          <tr v-for="(item, idx) in list" :key="item.id">
            <td>@{{ idx + 1 }}</td>
            <td>@{{ item.branch_name }}</td>

            {{-- Nomor WA --}}
            <td>
              <span v-if="editId !== item.id">@{{ item.whatsapp_number || '-' }}</span>
              <input v-else type="text" class="form-control input-sm"
                v-model="editWa" placeholder="contoh: 628123456789"
                style="max-width:200px;">
            </td>

            {{-- Token WA --}}
            <td>
              <span v-if="editId !== item.id">
                <span v-if="item.has_token" class="text-success">
                  <i class="fa fa-check-circle"></i> @{{ maskToken(item.fonnte_token) }}
                </span>
                <span v-else class="text-muted"><i class="fa fa-times-circle"></i> Belum diset</span>
              </span>
              <div v-else>
                <div class="input-group" style="max-width:280px;">
                  <input :type="showToken[item.id] ? 'text' : 'password'"
                    class="form-control input-sm"
                    v-model="editToken"
                    placeholder="Kosongkan untuk hapus token">
                  <span class="input-group-btn">
                    <button class="btn btn-default btn-sm" type="button"
                      @click="toggleShow(item.id)">
                      <i class="fa" :class="showToken[item.id] ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                  </span>
                </div>
                <small class="text-muted">Kosongkan untuk menghapus token cabang</small>
              </div>
            </td>

            {{-- Aksi --}}
            <td>
              <template v-if="editId !== item.id">
                <button class="btn btn-xs btn-warning" @click="startEdit(item)" title="Edit">
                  <i class="fa fa-pencil"></i>
                </button>
              </template>
              <template v-else>
                <button class="btn btn-xs btn-success" @click="simpan(item)" :disabled="loadingSimpan" title="Simpan">
                  <i class="fa fa-check"></i>
                </button>
                <button class="btn btn-xs btn-default" @click="batalEdit" title="Batal">
                  <i class="fa fa-times"></i>
                </button>
              </template>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@section('vue-content')
<script src="{{ asset('main/js/pengabaran/nomor-wa-vue.js') }}"></script>
@endsection
