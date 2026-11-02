<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Order Pelanggan - {{ $customer->nama }} ({{ $summary->periode }})</title>
    <style>
        @page {
            margin: 18px 22px;
            size: A4 portrait;
        }
        * {
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
            color: #333;
        }
        body {
            margin: 0;
            padding: 5px;
            font-size: 11px;
            line-height: 1.4;
            background: #fff;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #1a6596;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .clinic-brand h2 {
            margin: 0;
            color: #1a6596;
            font-size: 19px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .clinic-brand p {
            margin: 2px 0 0;
            font-size: 10.5px;
            color: #555;
        }
        .doc-title {
            text-align: right;
            vertical-align: middle;
        }
        .doc-title h3 {
            margin: 0;
            font-size: 15px;
            color: #222;
            text-transform: uppercase;
        }
        .doc-title .periode-badge {
            font-size: 11.5px;
            color: #1a6596;
            font-weight: bold;
            margin-top: 3px;
        }
        .info-table {
            width: 100%;
            margin-bottom: 14px;
        }
        .info-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 8px 10px;
            font-size: 10.5px;
            line-height: 1.5;
        }
        .info-box h4 {
            margin: 0 0 4px 0;
            font-size: 11px;
            color: #1a6596;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 3px;
            text-transform: uppercase;
        }
        .order-card {
            margin-bottom: 12px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            page-break-inside: avoid;
        }
        .order-header-table {
            width: 100%;
            background-color: #f1f5f9;
            border-bottom: 1px solid #cbd5e1;
            padding: 5px 8px;
            font-size: 10.5px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }
        .items-table th {
            background-color: #2b7bb3;
            color: #ffffff;
            padding: 5px 8px;
            text-align: left;
            font-weight: bold;
        }
        .items-table td {
            padding: 5px 8px;
            border-bottom: 1px solid #e2e8f0;
        }
        .items-table tr.even td {
            background-color: #fafbfc;
        }
        .order-footer-table {
            width: 100%;
            background-color: #fcfcfc;
            padding: 5px 8px;
            font-size: 10.5px;
            border-top: 1px solid #e2e8f0;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .grand-summary-box {
            margin-top: 15px;
            border: 2px solid #1a6596;
            background-color: #f8fafc;
            border-radius: 4px;
            padding: 10px 14px;
            page-break-inside: avoid;
        }
        .grand-summary-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11.5px;
        }
        .grand-summary-table td {
            padding: 4px 6px;
        }
        .grand-total-row td {
            border-top: 2px solid #1a6596;
            font-size: 13px;
            font-weight: bold;
            color: #1a6596;
            padding-top: 6px;
        }
        .footer-note {
            margin-top: 25px;
            text-align: center;
            font-size: 9.5px;
            color: #94a3b8;
            border-top: 1px dashed #cbd5e1;
            padding-top: 8px;
        }
    </style>
</head>
<body>

    {{-- HEADER KLINIK --}}
    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="clinic-brand" style="vertical-align: middle;">
                <h2>MITRA VET</h2>
            </td>
            <td class="doc-title">
                <h3>REKAP STRUK ORDER MITRA</h3>
                <div class="periode-badge">{{ $summary->periode }}</div>
            </td>
        </tr>
    </table>

    {{-- INFO PELANGGAN & PERIODE --}}
    <table class="info-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 55%; vertical-align: top;">
                <div class="info-box">
                    <h4>Data Pelanggan / Mitra</h4>
                    <table style="width: 100%; font-size: 10.5px; border-collapse: collapse;">
                        <tr>
                            <td style="width: 100px; color:#555;"><strong>Nama Mitra:</strong></td>
                            <td><strong>{{ $customer->nama }}</strong></td>
                        </tr>
                        <tr>
                            <td style="color:#555;"><strong>No. Handphone:</strong></td>
                            <td>{{ $customer->no_hp ?: '-' }}</td>
                        </tr>
                        <tr>
                            <td style="color:#555; vertical-align: top;"><strong>Alamat:</strong></td>
                            <td>{{ $customer->alamat ?: '-' }}</td>
                        </tr>
                    </table>
                </div>
            </td>
            <td style="width: 3%;"></td>
            <td style="width: 42%; vertical-align: top;">
                <div class="info-box">
                    <h4>Ringkasan Periode</h4>
                    <table style="width: 100%; font-size: 10.5px; border-collapse: collapse;">
                        <tr>
                            <td style="width: 105px; color:#555;"><strong>Periode Data:</strong></td>
                            <td><strong style="color: #1a6596;">{{ $summary->periode }}</strong></td>
                        </tr>
                        <tr>
                            <td style="color:#555;"><strong>Total Transaksi:</strong></td>
                            <td><strong>{{ $summary->total_orders }} Transaksi Order</strong></td>
                        </tr>
                        <tr>
                            <td style="color:#555;"><strong>Dicetak Pada:</strong></td>
                            <td>{{ date('d F Y, H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    {{-- DAFTAR TRANSAKSI ORDER (DIURUTKAN TANGGAL ASC) --}}
    <div style="margin-bottom: 8px;">
        <strong style="font-size: 11.5px; color: #1a6596; text-transform: uppercase;">
            <i class="fa fa-list"></i> Rincian Transaksi Order (Urut Berdasarkan Tanggal):
        </strong>
    </div>

    @foreach($orders as $idx => $order)
    <div class="order-card">
        {{-- Header Order --}}
        <table class="order-header-table" cellpadding="0" cellspacing="0">
            <tr>
                <td style="width: 40%;">
                    <strong>Order #{{ $idx + 1 }}:</strong>
                    <span style="color:#1a6596; font-weight:bold;">{{ $order->nomor_order }}</span>
                </td>
                <td style="width: 35%; text-align: center;">
                    <strong>Tanggal:</strong> {{ date('d F Y, H:i', strtotime($order->created_at)) }}
                </td>
                <td style="width: 25%; text-align: right;">
                    <strong>Oleh:</strong> {{ $order->creator ? $order->creator->fullname : '-' }}
                </td>
            </tr>
        </table>

        {{-- Detail Barang yang Diorder --}}
        <table class="items-table" cellpadding="0" cellspacing="0">
            <thead>
                <tr>
                    <th style="width: 25px;" class="text-center">No</th>
                    <th>Nama Barang / Produk</th>
                    <th class="text-center" style="width: 60px;">Satuan</th>
                    <th class="text-right" style="width: 95px;">Harga Satuan</th>
                    <th class="text-center" style="width: 45px;">Qty</th>
                    <th class="text-right" style="width: 105px;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $iIdx => $it)
                <tr class="{{ $iIdx % 2 == 1 ? 'even' : '' }}">
                    <td class="text-center">{{ $iIdx + 1 }}</td>
                    <td>{{ $it->item ? $it->item->nama_barang : 'Barang telah dihapus' }}</td>
                    <td class="text-center">{{ $it->item ? $it->item->satuan : '-' }}</td>
                    <td class="text-right">Rp {{ number_format($it->harga_satuan, 0, ',', '.') }}</td>
                    <td class="text-center"><strong>{{ $it->quantity }}</strong></td>
                    <td class="text-right">Rp {{ number_format($it->subtotal, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Footer Order (Total, Ongkir, Catatan) --}}
        <table class="order-footer-table" cellpadding="0" cellspacing="0">
            <tr>
                <td style="vertical-align: middle;">
                    @if(!empty($order->catatan))
                        <span style="color:#666; font-style:italic;">Catatan: {{ $order->catatan }}</span>
                    @endif
                </td>
                <td style="text-align: right; vertical-align: middle; width: 340px;">
                    Produk: <strong>Rp {{ number_format($order->total_harga, 0, ',', '.') }}</strong>
                    @if($order->biaya_ongkir > 0)
                        &nbsp;|&nbsp; Ongkir: <strong>Rp {{ number_format($order->biaya_ongkir, 0, ',', '.') }}</strong>
                    @endif
                    &nbsp;|&nbsp; Total: <strong style="color: #1a6596; font-size:11px;">Rp {{ number_format($order->grand_total, 0, ',', '.') }}</strong>
                </td>
            </tr>
        </table>
    </div>
    @endforeach

    {{-- GRAND SUMMARY BOX --}}
    <div class="grand-summary-box">
        <table class="grand-summary-table" cellpadding="0" cellspacing="0">
            <tr>
                <td style="width: 60%; vertical-align: top;">
                    <strong style="color: #1a6596; font-size: 12px; text-transform: uppercase;">
                        Rekapitulasi Total Orderan {{ $customer->nama }}
                    </strong><br>
                    <span style="font-size: 10.5px; color: #555;">
                        Total transaksi: <strong>{{ $summary->total_orders }} kali order</strong> &bull; Total kuantiti: <strong>{{ $summary->total_qty }} unit/pcs</strong>
                    </span>
                </td>
                <td style="width: 40%; vertical-align: top;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
                        <tr>
                            <td class="text-right">Total Belanja Produk:</td>
                            <td class="text-right" style="width: 120px;"><strong>Rp {{ number_format($summary->total_produk, 0, ',', '.') }}</strong></td>
                        </tr>
                        <tr>
                            <td class="text-right">Total Biaya Ongkos Kirim:</td>
                            <td class="text-right"><strong>Rp {{ number_format($summary->total_ongkir, 0, ',', '.') }}</strong></td>
                        </tr>
                        <tr class="grand-total-row">
                            <td class="text-right">GRAND TOTAL:</td>
                            <td class="text-right">Rp {{ number_format($summary->grand_total, 0, ',', '.') }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    {{-- FOOTER NOTE --}}
    <div class="footer-note">
        Dokumen ini diterbitkan secara otomatis oleh sistem Mitra Vet.<br>
        Dicetak pada: {{ date('d F Y, H:i') }} &bull; Riwayat struk sah tanpa tanda tangan basah.
    </div>

</body>
</html>
