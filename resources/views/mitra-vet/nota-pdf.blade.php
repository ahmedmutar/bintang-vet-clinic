<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Nota Mitra Vet - {{ $order->nomor_order }}</title>
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
            padding: 10px;
            font-size: 12px;
            line-height: 1.4;
            background: #fff;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #1a6596;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }
        .clinic-brand h2 {
            margin: 0;
            color: #1a6596;
            font-size: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .clinic-brand p {
            margin: 3px 0 0;
            font-size: 11px;
            color: #555;
        }
        .invoice-title {
            text-align: right;
        }
        .invoice-title h3 {
            margin: 0;
            font-size: 18px;
            color: #222;
        }
        .invoice-title .order-no {
            font-size: 13px;
            color: #1a6596;
            font-weight: bold;
            margin-top: 4px;
        }
        .info-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .info-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 10px 12px;
            vertical-align: top;
            font-size: 11px;
            line-height: 1.5;
        }
        .info-box h4 {
            margin: 0 0 6px 0;
            font-size: 12px;
            color: #1a6596;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
            text-transform: uppercase;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 11px;
        }
        .items-table th {
            background-color: #1a6596;
            color: #ffffff;
            padding: 8px 10px;
            text-align: left;
            font-weight: bold;
            font-size: 11px;
        }
        .items-table td {
            padding: 7px 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        .items-table tr.even td {
            background-color: #f8fafc;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .total-wrapper {
            width: 100%;
            margin-bottom: 15px;
        }
        .total-table {
            width: 320px;
            border-collapse: collapse;
            font-size: 11px;
            float: right;
        }
        .total-table td {
            padding: 4px 8px;
            border: none;
        }
        .total-table tr.grand-total td {
            border-top: 2px solid #1a6596;
            font-size: 13px;
            font-weight: bold;
            color: #1a6596;
            padding-top: 6px;
        }
        .notes-box {
            background: #f1f5f9;
            border-left: 3px solid #1a6596;
            padding: 8px 12px;
            font-size: 11px;
            color: #475569;
            margin-top: 15px;
            clear: both;
        }
        .footer-text {
            margin-top: 35px;
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
            border-top: 1px dashed #cbd5e1;
            padding-top: 10px;
            clear: both;
        }
    </style>
</head>
<body>

    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="clinic-brand" style="vertical-align: middle;">
                <h2>MITRA VET</h2>
            </td>
            <td class="invoice-title" style="vertical-align: middle;">
                <h3>NOTA PENJUALAN</h3>
                <div class="order-no">{{ $order->nomor_order }}</div>
            </td>
        </tr>
    </table>

    <table class="info-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 48%; vertical-align: top;">
                <div class="info-box">
                    <h4>Tujuan Pengiriman</h4>
                    <strong>Nama Pembeli:</strong> {{ $order->nama_pembeli }}<br>
                    <strong>No. Handphone:</strong> {{ $order->no_hp }}<br>
                    <strong>Alamat:</strong> {{ $order->alamat ?: '-' }}
                </div>
            </td>
            <td style="width: 4%;"></td>
            <td style="width: 48%; vertical-align: top;">
                <div class="info-box">
                    <h4>Informasi Order</h4>
                    <strong>Tanggal Order:</strong> {{ date('d F Y, H:i', strtotime($order->created_at)) }}<br>
                    <strong>Dibuat Oleh:</strong> {{ $order->creator ? $order->creator->fullname : '-' }}<br>
                    <strong>Status:</strong> Selesai / Terkirim
                </div>
            </td>
        </tr>
    </table>

    <table class="items-table" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th style="width: 30px;" class="text-center">No</th>
                <th>Nama Produk</th>
                <th class="text-center" style="width: 70px;">Satuan</th>
                <th class="text-right" style="width: 110px;">Harga Satuan</th>
                <th class="text-center" style="width: 50px;">Qty</th>
                <th class="text-right" style="width: 120px;">Subtotal</th>
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

    <div class="total-wrapper">
        <table class="total-table" cellpadding="0" cellspacing="0">
            <tr>
                <td class="text-right">Total Produk:</td>
                <td class="text-right" style="width: 130px;">Rp {{ number_format($order->total_harga, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="text-right">Biaya Ongkir:</td>
                <td class="text-right">Rp {{ number_format($order->biaya_ongkir, 0, ',', '.') }}</td>
            </tr>
            <tr class="grand-total">
                <td class="text-right">Grand Total:</td>
                <td class="text-right">Rp {{ number_format($order->grand_total, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    @if(!empty($order->catatan))
    <div class="notes-box">
        <strong>Catatan:</strong><br>
        {{ $order->catatan }}
    </div>
    @endif

    <div class="footer-text">
        Terima kasih atas kepercayaan Anda bermitra dengan Mitra Vet.<br>
        Dokumen ini dibuat otomatis oleh sistem dan sah tanpa tanda tangan basah.
    </div>

</body>
</html>
