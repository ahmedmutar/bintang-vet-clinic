<div class="modal fade" id="modal-struk-pelanggan">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">
                    <i class="fa fa-file-pdf-o text-danger"></i> Download Struk / Rekap Order Pelanggan (PDF)
                </h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-info" style="font-size:12.5px; margin-bottom:15px;">
                    <i class="fa fa-info-circle"></i> 
                    Pilih pelanggan/mitra serta periode yang ingin diunduh. PDF akan menampilkan rincian barang apa saja yang pernah diorder, tanggal order, ongkir, serta total biaya belanja yang <strong>diurutkan berdasarkan tanggal</strong>.
                </div>

                <div class="form-group">
                    <label>Pilih Pelanggan / Mitra <span class="text-danger">*</span></label>
                    <select id="struk-customer-id" class="form-control" style="width:100%;">
                        <option value="">-- Cari & Pilih Pelanggan / Mitra --</option>
                    </select>
                    <div id="err-struk-customer" class="validate-error"></div>
                </div>

                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label>Tahun <span class="text-danger">*</span></label>
                            <select id="struk-year" class="form-control input-sm"></select>
                            <div id="err-struk-year" class="validate-error"></div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label>Bulan (Pilihan Periode)</label>
                            <select id="struk-month" class="form-control input-sm">
                                <option value="">-- Semua Bulan (1 Tahun Penuh) --</option>
                                <option value="1">Januari</option>
                                <option value="2">Februari</option>
                                <option value="3">Maret</option>
                                <option value="4">April</option>
                                <option value="5">Mei</option>
                                <option value="6">Juni</option>
                                <option value="7">Juli</option>
                                <option value="8">Agustus</option>
                                <option value="9">September</option>
                                <option value="10">Oktober</option>
                                <option value="11">November</option>
                                <option value="12">Desember</option>
                            </select>
                            <small class="text-muted">Pilih 1 bulan tertentu atau Semua Bulan untuk rekap 1 tahun.</small>
                        </div>
                    </div>
                </div>

                <div id="err-struk-global" class="validate-error" style="margin-top:10px;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btnProsesDownloadStruk">
                    <i class="fa fa-download"></i> Download Struk PDF
                </button>
            </div>
        </div>
    </div>
</div>
