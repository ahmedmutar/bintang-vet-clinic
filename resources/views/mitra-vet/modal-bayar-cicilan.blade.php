<div class="modal fade" id="modal-bayar-cicilan" data-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">
                    <i class="fa fa-money text-success"></i> Input Pembayaran Cicilan Order
                </h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="bayar-order-id">

                {{-- Summary Order Info Box --}}
                <div class="callout callout-info" style="font-size:12.5px; margin-bottom:15px; padding: 10px 14px;">
                    <table style="width: 100%;">
                        <tr>
                            <td style="width: 45%;">
                                <strong>No. Order:</strong> <span id="bayar-display-no" style="font-weight:bold;">-</span><br>
                                <strong>Klien:</strong> <span id="bayar-display-klien">-</span>
                            </td>
                            <td style="width: 55%; text-align: right;">
                                Total Tagihan: <strong id="bayar-display-total">Rp 0</strong><br>
                                Sisa Piutang: <strong id="bayar-display-sisa" style="color:#d9534f; font-size:14px;">Rp 0</strong>
                            </td>
                        </tr>
                    </table>
                </div>

                <form id="form-bayar-cicilan">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Pembayaran Termin Ke-</label>
                                <input type="text" class="form-control" id="bayar-display-cicilan-ke" readonly style="background:#eef2f7; font-weight:bold;">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Rekomendasi Angsuran</label>
                                <input type="text" class="form-control" id="bayar-display-rekomendasi" readonly style="background:#eef2f7; color:#1a6596; font-weight:bold;">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Nominal Pembayaran (Rp) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control input-lg" id="bayar-nominal" placeholder="contoh: 500000" style="font-weight:bold; color:#16a34a;">
                        <small class="text-muted">Masukkan nominal yang dibayar klien (bisa pas atau sebagian).</small>
                        <div id="err-bayar-nominal" class="validate-error"></div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Tanggal Pembayaran <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="bayar-tanggal" value="{{ date('Y-m-d') }}">
                                <div id="err-bayar-tanggal" class="validate-error"></div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Metode Pembayaran <span class="text-danger">*</span></label>
                                <select class="form-control" id="bayar-metode">
                                    <option value="transfer">Transfer Bank</option>
                                    <option value="tunai">Tunai / Cash</option>
                                    <option value="qris">QRIS / E-Wallet</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Catatan / Keterangan Transfer</label>
                        <input type="text" class="form-control" id="bayar-catatan" placeholder="contoh: Transfer via BCA ref: 123456">
                    </div>

                    <div id="err-bayar-global" class="validate-error"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-success" id="btnSimpanBayarCicilan">
                    <i class="fa fa-save"></i> Simpan Pembayaran
                </button>
            </div>
        </div>
    </div>
</div>
