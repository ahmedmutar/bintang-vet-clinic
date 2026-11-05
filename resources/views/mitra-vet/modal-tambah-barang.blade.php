<div class="modal fade" id="modal-tambah-barang">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="modal-tambah-barang-title">Tambah Barang</h4>
            </div>
            <div class="modal-body">
                <form id="form-tambah-barang">
                    <div class="form-group">
                        <label>Nama Barang <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="input-nama-barang" placeholder="Masukkan nama barang">
                        <div id="err-nama-barang" class="validate-error"></div>
                    </div>
                    <div class="form-group">
                        <label>Satuan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="input-satuan" placeholder="contoh: Botol, Pcs, Strip, Kg">
                        <div id="err-satuan" class="validate-error"></div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Harga Modal (Rp) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="input-harga-modal" placeholder="contoh: 30000">
                                <div id="err-harga-modal" class="validate-error"></div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Harga Jual (Rp) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="input-harga-jual" placeholder="contoh: 50000">
                                <div id="err-harga-jual" class="validate-error"></div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Estimasi Profit (Rp)</label>
                        <input type="text" class="form-control" id="input-profit" placeholder="Rp 0" readonly style="background-color:#eef2f7; font-weight:bold; color:#1a6596;">
                        <small class="text-muted"><i class="fa fa-info-circle"></i> Harga Jual harus lebih besar dari Harga Modal.</small>
                    </div>
                    <div class="form-group" id="wrap-stok-awal">
                        <label id="label-stok-barang">Stok Awal <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="input-stok-awal" placeholder="Masukkan jumlah stok awal" min="0">
                        <small id="help-stok-barang" class="text-muted" style="display:none; margin-top:3px;"></small>
                        <div id="err-stok-awal" class="validate-error"></div>
                    </div>
                    <div id="err-global-barang" class="validate-error"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btnSimpanBarang">Simpan</button>
            </div>
        </div>
    </div>
</div>
