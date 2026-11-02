<div class="modal fade" id="modal-upload-excel">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Upload Excel Produk</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <strong><i class="fa fa-info-circle"></i> Petunjuk:</strong>
                    <ul style="margin-top:6px; margin-bottom:0;">
                        <li>Download template Excel terlebih dahulu.</li>
                        <li>Isi data sesuai kolom: <code>nama_barang</code>, <code>satuan</code>, <code>harga_modal</code>, <code>harga_jual</code>, <code>stok</code>.</li>
                        <li><strong>Validasi harga:</strong> <code>harga_jual</code> harus lebih besar dari <code>harga_modal</code>.</li>
                        <li>Jika nama barang sudah ada, stok akan <strong>ditambahkan</strong> (tidak duplikat).</li>
                        <li>File maksimal 5MB, format <code>.xlsx</code> atau <code>.xls</code>.</li>
                    </ul>
                </div>

                {{-- Preview validasi hasil upload --}}
                <div id="upload-validation-result" style="display:none; margin-bottom:10px;"></div>

                <div class="form-group">
                    <label>Pilih File Excel <span class="text-danger">*</span></label>
                    <input type="file" id="input-file-excel" accept=".xls,.xlsx" class="form-control">
                    <small class="text-muted">Format: .xls atau .xlsx | Maks: 5MB</small>
                    <div id="err-upload-file" class="validate-error"></div>
                </div>

                {{-- Progress bar --}}
                <div id="upload-progress-wrap" style="display:none;">
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped active" role="progressbar"
                            aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"
                            style="width:100%">Sedang memproses...
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" id="btnDownloadTemplateModal">
                    <i class="fa fa-download"></i> Download Template
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-warning" id="btnProseUpload">
                    <i class="fa fa-upload"></i> Upload & Proses
                </button>
            </div>
        </div>
    </div>
</div>
