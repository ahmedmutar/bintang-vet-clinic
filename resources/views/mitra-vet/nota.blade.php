<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota Mitra Vet - {{ $order->nomor_order }}</title>
    <link rel="stylesheet" href="{{ asset('bower_components/font-awesome/css/font-awesome.min.css') }}">
    <style>
        * {
            box-sizing: border-box;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
        }
        body {
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
        }
        .invoice-card {
            background: #fff;
            max-width: 800px;
            margin: 0 auto;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #1a6596;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .clinic-brand h2 {
            margin: 0;
            color: #1a6596;
            font-size: 24px;
            text-transform: uppercase;
        }
        .clinic-brand p {
            margin: 4px 0 0;
            font-size: 13px;
            color: #666;
        }
        .invoice-title {
            text-align: right;
        }
        .invoice-title h3 {
            margin: 0;
            font-size: 20px;
            color: #444;
        }
        .invoice-title .order-no {
            font-size: 14px;
            color: #1a6596;
            font-weight: bold;
            margin-top: 5px;
        }
        .info-grid {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
            font-size: 13px;
            line-height: 1.6;
        }
        .info-col {
            width: 48%;
        }
        .info-col h4 {
            margin: 0 0 8px;
            font-size: 14px;
            color: #1a6596;
            border-bottom: 1px solid #ddd;
            padding-bottom: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 13px;
        }
        table th {
            background-color: #1a6596;
            color: #fff;
            padding: 10px 12px;
            text-align: left;
        }
        table td {
            padding: 10px 12px;
            border-bottom: 1px solid #eee;
        }
        table tr:nth-child(even) td {
            background-color: #fdfdfd;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .total-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 30px;
        }
        .total-table {
            width: 320px;
            margin-bottom: 0;
        }
        .total-table td {
            padding: 6px 12px;
            border: none;
        }
        .total-table tr.grand-total td {
            border-top: 2px solid #1a6596;
            font-size: 16px;
            font-weight: bold;
            color: #1a6596;
        }
        .notes {
            background: #f9fbfd;
            border-left: 4px solid #1a6596;
            padding: 10px 15px;
            font-size: 12px;
            color: #555;
            margin-bottom: 30px;
        }
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-top: 20px;
        }
        .btn {
            padding: 10px 20px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-print {
            background-color: #1a6596;
            color: white;
        }
        .btn-print:hover {
            background-color: #134d73;
        }
        .btn-close {
            background-color: #6c757d;
            color: white;
        }
        .btn-close:hover {
            background-color: #565e64;
        }
        @media (max-width: 600px) {
            body {
                padding: 10px;
            }
            .invoice-card {
                padding: 15px;
            }
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .invoice-title {
                text-align: left;
            }
            .info-grid {
                flex-direction: column;
                gap: 15px;
            }
            .info-col {
                width: 100%;
            }
            .total-section {
                justify-content: stretch;
            }
            .total-table {
                width: 100%;
            }
            .action-buttons {
                flex-direction: column;
            }
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .invoice-card {
                box-shadow: none;
                padding: 0;
                max-width: 100%;
            }
            .action-buttons {
                display: none;
            }
        }
    </style>
</head>
<body>

<div class="invoice-card">
    <div class="header">
        <div class="clinic-brand">
            <h2>Mitra Vet</h2>
        </div>
        <div class="invoice-title">
            <h3>NOTA PENJUALAN</h3>
            <div class="order-no">{{ $order->nomor_order }}</div>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-col">
            <h4>Tujuan Pengiriman</h4>
            <strong>Nama Pembeli:</strong> {{ $order->nama_pembeli }}<br>
            <strong>No. Handphone:</strong> {{ $order->no_hp }}<br>
            <strong>Alamat:</strong> {{ $order->alamat }}
        </div>
        <div class="info-col">
            <h4>Informasi Order</h4>
            <strong>Tanggal Order:</strong> {{ date('d F Y, H:i', strtotime($order->created_at)) }}<br>
            <strong>Dibuat Oleh:</strong> {{ $order->creator ? $order->creator->fullname : '-' }}<br>
            <strong>Status:</strong> Selesai / Terkirim
        </div>
    </div>

    <div style="overflow-x: auto; margin-bottom: 20px;">
        <table>
        <thead>
            <tr>
                <th style="width: 40px;" class="text-center">No</th>
                <th>Nama Produk</th>
                <th class="text-center" style="width: 80px;">Satuan</th>
                <th class="text-right" style="width: 120px;">Harga</th>
                <th class="text-center" style="width: 70px;">Qty</th>
                <th class="text-right" style="width: 140px;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $idx => $item)
            <tr>
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
    </div>

    <div class="total-section">
        <table class="total-table">
            <tr>
                <td class="text-right">Total Produk:</td>
                <td class="text-right">Rp {{ number_format($order->total_harga, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="text-right">Biaya Ongkos Kirim:</td>
                <td class="text-right">Rp {{ number_format($order->biaya_ongkir, 0, ',', '.') }}</td>
            </tr>
            <tr class="grand-total">
                <td class="text-right">Grand Total:</td>
                <td class="text-right">Rp {{ number_format($order->grand_total, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    @if(!empty($order->catatan))
    <div class="notes">
        <strong>Catatan:</strong><br>
        {{ $order->catatan }}
    </div>
    @endif

    <div class="action-buttons">
        <a href="{{ url('/mitra-vet/nota/' . $order->id . '/download') }}" class="btn btn-print">
            <i class="fa fa-download"></i> Cetak / Download Nota
        </a>
        <button class="btn btn-close" onclick="window.close()">
            <i class="fa fa-times"></i> Tutup
        </button>
    </div>
</div>

</body>
</html>
