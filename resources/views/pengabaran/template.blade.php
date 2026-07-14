@extends('layout.master')

@section('css-content')
<style>
  .template-card {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 16px;
    margin-bottom: 16px;
    background: #fff;
  }
  .template-card.has-custom {
    border-left: 4px solid #3c8dbc;
  }
  .template-card .complaint-label {
    font-weight: 700;
    font-size: 14px;
    margin-bottom: 8px;
    color: #333;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .template-card textarea {
    width: 100%;
    resize: vertical;
    min-height: 100px;
  }
  .template-card .action-row {
    margin-top: 8px;
    display: flex;
    gap: 8px;
    align-items: center;
  }
  .badge-custom {
    font-size: 11px;
    background: #3c8dbc;
    color: #fff;
    padding: 2px 8px;
    border-radius: 10px;
  }
  .placeholder-info {
    font-size: 12px;
    color: #999;
    margin-bottom: 4px;
  }
</style>
@endsection

@section('content')
<div class="box box-info" id="template-app">
  <div class="box-header">
    <h3 class="box-title">Template Pesan Pengabaran</h3>
  </div>

  <div class="box-body">
    <div v-if="showAlert" class="alert alert-dismissible"
      :class="{ 'alert-success': isSuccess, 'alert-danger': !isSuccess }">
      <button type="button" @click="showAlert = false" class="close">&times;</button>
      @{{ message }}
    </div>

    <div class="row" style="margin-bottom: 16px;">
      <div class="col-md-4">
        <label>Pilih Cabang</label>
        <select class="form-control" v-model="selectedBranchId" @change="load">
          <option value="">-- Pilih Cabang --</option>
          <option v-for="b in branches" :key="b.id" :value="b.id">@{{ b.branch_name }}</option>
        </select>
      </div>
    </div>

    <div v-if="loading" class="text-center" style="padding: 40px 0;">
      <i class="fa fa-spinner fa-spin fa-2x"></i>
      <p class="text-muted">Memuat template...</p>
    </div>

    <div v-else>
      <div
        v-for="item in templates"
        :key="item.complaint_id"
        class="template-card"
        :class="{ 'has-custom': item.has_custom }"
      >
        <div class="complaint-label">
          <span><i class="fa fa-comment-o"></i> @{{ item.complaint_name }}</span>
          <span v-if="item.has_custom && selectedBranchId > 0" class="badge-custom">Kustom</span>
        </div>

        <textarea
          class="form-control"
          v-model="item.message"
          :placeholder="'Isi template pesan untuk keluhan ' + item.complaint_name"
        ></textarea>

        <div class="action-row" style="flex-wrap: wrap; gap: 8px; margin-top: 10px; align-items: center;">
          <div class="input-group" style="max-width: 240px;">
            <span class="input-group-addon"><i class="fa fa-clock-o"></i> Kirim setelah</span>
            <input
              type="number"
              class="form-control"
              v-model.number="item.followup_days"
              min="1"
              max="365"
              style="max-width: 65px;"
            >
            <span class="input-group-addon">hari</span>
          </div>
          <button
            class="btn btn-primary btn-sm"
            @click="simpan(item)"
            :disabled="item.loading"
          >
            <i class="fa fa-save"></i> @{{ item.loading ? 'Menyimpan...' : 'Simpan' }}
          </button>
          <button
            v-if="item.has_custom && selectedBranchId > 0"
            class="btn btn-default btn-sm"
            @click="hapus(item)"
            :disabled="item.loadingHapus"
            title="Hapus template kustom, kembali ke global"
          >
            <i class="fa fa-undo"></i> @{{ item.loadingHapus ? 'Menghapus...' : 'Reset ke Global' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('vue-content')
<script src="{{ asset('main/js/pengabaran/template-vue.js') }}"></script>
@endsection
