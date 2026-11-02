<div class="modal fade" id="modal-riwayat-cicilan">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">
                    <i class="fa fa-history text-primary"></i> Riwayat Pembayaran & Cicilan Order
                </h4>
            </div>
            <div class="modal-body">
                <div class="callout callout-info" style="font-size:13px; margin-bottom:15px;">
                    <div class="row">
                        <div class="col-sm-6">
                            <strong>No. Order:</strong> <span id="riwayat-no-order" style="font-weight:bold;">-</span><br>
                            <strong>Klien:</strong> <span id="riwayat-klien">-</span><br>
                            <strong>Status:</strong> <span id="riwayat-status">-</span>
                        </div>
                        <div class="col-sm-6 text-right">
                            Total Tagihan: <strong id="riwayat-total-tagihan">Rp 0</strong><br>
                            Total Terbayar: <strong id="riwayat-total-terbayar" class="text-green">Rp 0</strong><br>
                            Sisa Piutang: <strong id="riwayat-sisa-piutang" class="text-danger" style="font-size:15px;">Rp 0</strong>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-sm text-nowrap">
                        <thead>
                            <tr class="bg-gray">
                                <th style="width:30px;">No</th>
                                <th>No. Kuitansi</th>
                                <th>Keterangan / Termin</th>
                                <th>Tanggal Bayar</th>
                                <th>Metode</th>
                                <th class="text-right">Jumlah Bayar</th>
                                <th>Catatan</th>
                                <th>Dicatat Oleh</th>
                            </tr>
                        </thead>
                        <tbody id="list-riwayat-pembayaran">
                            <tr class="text-center">
                                <td colspan="8">Memuat riwayat...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
