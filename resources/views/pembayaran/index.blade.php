@extends('layout.master')

@section('content')
<div class="box box-info" id="pembayaran-app">
  <div class="box-header">
    <h3 class="box-title">Pembayaran</h3>
    <div class="nav-tabs-custom m-t-25px">
    <ul class="nav nav-tabs">
      <li class="active"><a href="#clinic" data-toggle="tab">Klinik</a></li>
      <li><a href="#pet_shop" data-toggle="tab">Pet Shop</a></li>
    </ul>
          <div id="tab-content" class="tab-content">
            <div class="tab-pane fade in active" id="clinic">
              <div class="row">
                  <div class="inner-box-title">
                      <div class="section-left-box-title">
                        <button class="btn btn-info openFormAdd">Tambah Pembayaran Klinik</button>
                      </div>
                      <div class="section-right-box-title">
                        <div class="input-search-section m-r-10px">
                          <input type="text" class="form-control" placeholder="cari..">
                          <i class="fa fa-search" aria-hidden="true"></i>
                        </div>
                        <select id="filterCabang" style="width: 100%"></select>
                      </div>
                    </div>

                    <div class="box-body">
                      <div class="table-responsive">
                        <table class="table table-striped text-nowrap">
                          <thead>
                            <tr>
                              <th>No</th>
                              <th class="onOrdering" data='registration_number' orderby="none">No. Registrasi <span class="fa fa-sort"></span></th>
                              <th class="onOrdering" data='created_at' orderby="none">Tanggal dibuat <span class="fa fa-sort"></span></th>
                              <th class="onOrdering" data='patient_number' orderby="none">No. Pasien <span class="fa fa-sort"></span></th>
                              <th class="onOrdering" data='pet_category' orderby="none">Jenis Hewan <span class="fa fa-sort"></span></th>
                              <th class="onOrdering" data='pet_name' orderby="none">Nama Hewan <span class="fa fa-sort"></span></th>
                              <th class="onOrdering" data='complaint' orderby="none">Keluhan <span class="fa fa-sort"></span></th>
                              <th class="onOrdering" data='status_outpatient_inpatient' orderby="none">Perawatan <span class="fa fa-sort"></span></th>
                              <th>Status</th>
                              <th class="onOrdering" data='created_by' orderby="none">Dibuat Oleh <span class="fa fa-sort"></span></th>
                              <th>Aksi</th>
                            </tr>
                          </thead>
                          <tbody id="list-pembayaran">
                            <tr class="text-center"><td colspan="11">Tidak ada data.</td></tr>
                          </tbody>
                        </table>

                        <ul class="pagination pagination-sm m-t-10px pull-left"></ul>
                      </div>
                    </div>
              </div>
            </div>
            <div class="tab-pane fade" id="pet_shop">
                <div class="row">
                  <div class="inner-box-title">
                    <div class="section-left-box-title">
                      <button class="btn btn-info openFormAddPetShop">Tambah Pembayaran Petshop</button>
                    </div>
                    <div class="section-right-box-title">
                      <div class="input-search-section-petshop m-r-10px">
                        <input type="text" class="form-control" placeholder="cari..">
                        <i class="fa fa-search" aria-hidden="true"></i>
                      </div>
                      <select id="filterCabangPet" style="width: 100%"></select>
                    </div>
                  </div>
                    <div class="box-body">
                        <div class="table-responsive">
                          <table class="table table-striped text-nowrap">
                            <thead>
                              <tr>
                                <th>No</th>
                                <th class="onOrderingPetShop" data='created_at' orderby="none">Tanggal dibuat <span class="fa fa-sort"></span></th>
                                <th class="onOrderingPetShop" data='branch_name' orderby="none">Cabang <span class="fa fa-sort"></span></th>
                                <th class="onOrderingPetShop" data='payment_number' orderby="none">No.Pembayaran <span class="fa fa-sort"></span></th>
                                <th class="onOrderingPetShop" data='item_name' orderby="none">Nama Barang <span class="fa fa-sort"></span></th>
                                <th class="onOrderingPetShop" data='total_item' orderby="none">Jumlah <span class="fa fa-sort"></span></th>
                                <th class="onOrderingPetShop" data='each_price' orderby="none">Harga Satuan <span class="fa fa-sort"></span></th>
                                <th class="onOrderingPetShop" data='overall_price' orderby="none">Harga Keseluruhan <span class="fa fa-sort"></span></th>
                                <th class="onOrderingPetShop" data='created_by' orderby="none">Dibuat Oleh <span class="fa fa-sort"></span></th>
                                <th>Aksi</th>
                              </tr>
                            </thead>
                            <tbody id="list-pembayaran-petshop">
                              <tr class="text-center"><td colspan="9">Tidak ada data.</td></tr>
                            </tbody>
                          </table>

                          <ul class="pagination pagination-sm m-t-10px pull-left"></ul>
                        </div>
                      </div>
                </div>
            </div>
          </div>
    </div>
    
  </div>

</div>

@component('pembayaran.pembayaran-petshop-tambah') @endcomponent
@component('layout.modal-confirmation') @endcomponent
@component('layout.modal-confirmation-pet-shop') @endcomponent
@component('layout.message-box') @endcomponent

@endsection
@section('script-content')
  <script src="{{ asset('main/js/pembayaran/pembayaran.js') }}"></script>
@endsection
@section('css-content')
  <link rel="stylesheet" type='text/css' href="{{ asset('main/css/pembayaran.css') }}">
@endsection
@section('vue-content')@endsection
