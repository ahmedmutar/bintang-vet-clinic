<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice Penagihan - {{ $order->nomor_order }}</title>
    <style>
        @page {
            margin: 20px 25px;
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
            margin-bottom: 12px;
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
        .invoice-title {
            text-align: right;
            vertical-align: middle;
        }
        .invoice-title h3 {
            margin: 0;
            font-size: 16px;
            color: #b91c1c;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .invoice-title .order-no {
            font-size: 12px;
            color: #1a6596;
            font-weight: bold;
            margin-top: 3px;
        }
        .billing-banner {
            width: 100%;
            background-color: #fef2f2;
            border: 1.5px solid #f87171;
            border-radius: 4px;
            padding: 10px 14px;
            margin-bottom: 14px;
        }
        .billing-banner table {
            width: 100%;
            border-collapse: collapse;
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
            margin: 0 0 5px 0;
            font-size: 11px;
            color: #1a6596;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 3px;
            text-transform: uppercase;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 10.5px;
        }
        .items-table th {
            background-color: #1a6596;
            color: #ffffff;
            padding: 6px 8px;
            text-align: left;
            font-weight: bold;
        }
        .items-table td {
            padding: 5px 8px;
            border-bottom: 1px solid #e2e8f0;
        }
        .items-table tr.even td {
            background-color: #f8fafc;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }

        .payment-summary-wrap {
            width: 100%;
            margin-bottom: 14px;
        }
        .history-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-top: 4px;
        }
        .history-table th {
            background-color: #e2e8f0;
            color: #334155;
            padding: 4px 6px;
            text-align: left;
        }
        .history-table td {
            padding: 4px 6px;
            border-bottom: 1px solid #e2e8f0;
        }
        .total-card-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        .total-card-table td {
            padding: 4px 6px;
        }
        .grand-highlight-row td {
            border-top: 2px solid #b91c1c;
            border-bottom: 2px solid #b91c1c;
            font-size: 13px;
            font-weight: bold;
            color: #b91c1c;
            padding: 6px;
            background-color: #fef2f2;
        }
        .payment-instructions {
            background: #f0fdf4;
            border: 1px solid #86efac;
            border-left: 4px solid #16a34a;
            padding: 9px 12px;
            font-size: 10.5px;
            border-radius: 4px;
            margin-bottom: 15px;
            line-height: 1.5;
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

    {{-- KOP SURAT --}}
    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="clinic-brand" style="vertical-align: middle;">
                <h2>MITRA VET</h2>
                <p>Divisi Mitra Vet | Layanan Distribusi & Kebutuhan Hewan</p>
            </td>
            <td class="invoice-title">
                <h3>INVOICE PENAGIHAN</h3>
                <div class="order-no">No: INV-{{ $order->nomor_order }}</div>
            </td>
        </tr>
    </table>

    {{-- BANNER SISA TAGIHAN & JATUH TEMPO --}}
    <div class="billing-banner">
        <table>
            <tr>
                <td style="width: 55%; vertical-align: middle;">
                    <span style="font-size: 11px; color: #991b1b; text-transform: uppercase; font-weight: bold;">
                        Total Sisa Tagihan yang Harus Dibayar:
                    </span><br>
                    <span style="font-size: 20px; font-weight: bold; color: #b91c1c;">
                        Rp {{ number_format($order->sisa_tagihan, 0, ',', '.') }}
                    </span>
                </td>
                <td style="width: 45%; vertical-align: middle; text-align: right; line-height: 1.5;">
                    <span style="font-size: 11px; color: #555;">Batas Jatuh Tempo:</span><br>
                    <strong style="font-size: 13px; color: #b91c1c;">
                        {{ $order->tanggal_jatuh_tempo ? date('d F Y', strtotime($order->tanggal_jatuh_tempo)) : '-' }}
                    </strong><br>
                    <span style="font-size: 10px; color: #666;">
                        Rencana Cicilan: <strong>{{ $order->jumlah_termin }}x Pembayaran</strong>
                        @if($order->status_pembayaran === 'lunas')
                            &bull; <span style="color:#16a34a; font-weight:bold;">LUNAS</span>
                        @else
                            &bull; <span style="color:#d97706; font-weight:bold;">BELUM LUNAS</span>
                        @endif
                    </span>
                </td>
            </tr>
        </table>
    </div>

    {{-- INFO MITRA & TANGGAL --}}
    <table class="info-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 48%; vertical-align: top;">
                <div class="info-box">
                    <h4>Ditujukan Kepada (Mitra / Pelanggan)</h4>
                    <strong>Nama Mitra:</strong> {{ $order->nama_pembeli }}<br>
                    <strong>No. Handphone:</strong> {{ $order->no_hp ?: '-' }}<br>
                    <strong>Alamat:</strong> {{ $order->alamat ?: '-' }}
                </div>
            </td>
            <td style="width: 4%;"></td>
            <td style="width: 48%; vertical-align: top;">
                <div class="info-box">
                    <h4>Informasi Transaksi</h4>
                    <strong>No. Order Terkait:</strong> {{ $order->nomor_order }}<br>
                    <strong>Tanggal Order:</strong> {{ date('d F Y, H:i', strtotime($order->created_at)) }}<br>
                    <strong>Tanggal Terbit Tagihan:</strong> {{ date('d F Y') }}<br>
                    <strong>Petugas / Kasir:</strong> {{ $order->creator ? $order->creator->fullname : '-' }}
                </div>
            </td>
        </tr>
    </table>

    {{-- RINCIAN BARANG --}}
    <div style="margin-bottom: 6px;">
        <strong style="font-size: 11px; color: #1a6596; text-transform: uppercase;">
            Rincian Pembelian Barang / Produk:
        </strong>
    </div>
    <table class="items-table" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th style="width: 25px;" class="text-center">No</th>
                <th>Nama Produk</th>
                <th class="text-center" style="width: 60px;">Satuan</th>
                <th class="text-right" style="width: 100px;">Harga Satuan</th>
                <th class="text-center" style="width: 45px;">Qty</th>
                <th class="text-right" style="width: 110px;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $idx => $item)
            <tr class="{{ $idx % 2 == 1 ? 'even' : '' }}">
                <td class="text-center">{{ $idx + 1 }}</td>
                <td>{{ $item->item ? $item->item->nama_barang : 'Barang telah dihapus' }}</td>
                <td class="text-center">{{ $item->item ? $item->item->satuan : '-' }}</td>
                <td class="text-right">Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                <td class="text-center">{{ $item->quantity }}</td>
                <td class="text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- REKAP TAGIHAN & RIWAYAT PEMBAYARAN CICILAN --}}
    <table class="payment-summary-wrap" cellpadding="0" cellspacing="0">
        <tr>
            {{-- Kolom Kiri: Riwayat Pembayaran yang Sudah Masuk --}}
            <td style="width: 52%; vertical-align: top; padding-right: 15px;">
                <div style="border: 1px solid #cbd5e1; border-radius: 4px; padding: 8px 10px; background-color: #fafbfc;">
                    <strong style="font-size: 10.5px; color: #1a6596; text-transform: uppercase;">
                        Riwayat Pembayaran yang Telah Diterima:
                    </strong>
                    <table class="history-table" cellpadding="0" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Keterangan</th>
                                <th>Tanggal</th>
                                <th class="text-right">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($order->payments as $p)
                            <tr>
                                <td>
                                    <strong>{{ $p->cicilan_ke === 0 ? 'Uang Muka (DP)' : 'Cicilan ke-' . $p->cicilan_ke }}</strong>
                                    @if($p->metode_pembayaran)
                                        <span style="color:#666; font-size:9px;">({{ ucfirst($p->metode_pembayaran) }})</span>
                                    @endif
                                </td>
                                <td>{{ date('d/m/Y', strtotime($p->tanggal_bayar)) }}</td>
                                <td class="text-right">Rp {{ number_format($p->jumlah_bayar, 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center" style="color:#888; font-style:italic;">
                                    Belum ada catatan pembayaran yang diterima.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </td>

            {{-- Kolom Kanan: Rincian Total & Sisa Tagihan --}}
            <td style="width: 48%; vertical-align: top;">
                <table class="total-card-table" cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="text-right">Total Produk:</td>
                        <td class="text-right" style="width: 110px;">Rp {{ number_format($order->total_harga, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="text-right">Biaya Ongkir:</td>
                        <td class="text-right">Rp {{ number_format($order->biaya_ongkir, 0, ',', '.') }}</td>
                    </tr>
                    <tr style="border-top: 1px solid #cbd5e1; font-weight: bold;">
                        <td class="text-right">Grand Total Tagihan:</td>
                        <td class="text-right">Rp {{ number_format($order->grand_total, 0, ',', '.') }}</td>
                    </tr>
                    <tr style="color: #16a34a;">
                        <td class="text-right">Total Telah Dibayar:</td>
                        <td class="text-right">(-) Rp {{ number_format($order->total_terbayar, 0, ',', '.') }}</td>
                    </tr>
                    <tr class="grand-highlight-row">
                        <td class="text-right">SISA TAGIHAN:</td>
                        <td class="text-right">Rp {{ number_format($order->sisa_tagihan, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- INSTRUKSI PEMBAYARAN BANK --}}
    <div class="payment-instructions">
        <strong><i class="fa fa-info-circle"></i> Instruksi Pembayaran Transfer Rekening:</strong><br>
        Silakan melakukan transfer pelunasan/cicilan ke salah satu rekening resmi klinik:<br>
        &bull; <strong>Bank BCA:</strong> <code>123-456-7890</code> a.n. <strong>MITRA VET</strong><br>
        &bull; <strong>Bank Mandiri:</strong> <code>142-00-9876543-2</code> a.n. <strong>MITRA VET</strong><br>
        Mohon cantumkan keterangan <strong>{{ $order->nomor_order }}</strong> pada saat transfer dan kirimkan bukti bayar ke Admin WhatsApp Keuangan.
    </div>

    {{-- FOOTER --}}
    <div class="footer-note">
        Invoice penagihan ini diterbitkan secara otomatis dan resmi oleh sistem Mitra Vet.<br>
        Terima kasih atas kerja sama dan kemitraan baik Anda bersama Mitra Vet.
    </div>

</body>
</html>
