<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>INVOICE - {{ $invoiceData['no_invoice'] ?? 'N/A' }}</title>
  <style>
    /* CSS UNTUK ORIENTASI LANDSCAPE (PENTING UNTUK CETAK/PDF) */
    @page {
      size: A4 landscape;
      margin: 10mm;
    }

    /* CSS Dasar untuk Penataan */
    body {
      font-family: 'Arial', sans-serif;
      font-size: 12px;
      margin: 0;
      padding: 20px;
      width: 1000px;
      /* Lebar disesuaikan untuk A4 landscape */
    }

    .container {
      width: 100%;
      margin: 0 auto;
      border: 1px solid #ccc;
      padding: 20px;
    }

    /* Header dan Status */
    .header {
      display: flex;
      justify-content: space-between;
      margin-bottom: 30px;
    }

    .header-left h1 {
      font-size: 24px;
      color: #333;
      margin: 0 0 5px 0;
    }

    .header-left p {
      margin: 0;
    }

    .header-right {
      text-align: right;
    }

    .header-right h2 {
      font-size: 28px;
      color: #000;
      margin: 0;
    }

    /* Mengasumsikan "IN" dan "BELUM" berarti "INVOICE" dan "BELUM DIBAYAR" */
    .status {
      font-size: 18px;
      color: red;
      font-weight: bold;
      margin-top: 5px;
    }

    /* Informasi Pelanggan dan Invoice Detail */
    .info-grid {
      display: flex;
      justify-content: space-between;
      margin-bottom: 30px;
    }

    .billing-info,
    .invoice-details {
      width: 48%;
    }

    .billing-info strong {
      font-size: 14px;
    }

    .invoice-details table {
      width: 100%;
      border-collapse: collapse;
    }

    .invoice-details table td {
      padding: 5px 0;
    }

    .invoice-details table td:first-child {
      font-weight: bold;
    }

    /* Tabel Item */
    .item-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 30px;
    }

    .item-table th,
    .item-table td {
      border: 1px solid #ddd;
      padding: 8px;
      text-align: left;
    }

    .item-table th {
      background-color: #f2f2f2;
      text-align: center;
    }

    .item-table td.qty,
    .item-table td.price,
    .item-table td.amount {
      text-align: right;
    }

    /* Ringkasan Total */
    .summary-table {
      width: 300px;
      float: right;
      border-collapse: collapse;
      margin-top: 10px;
    }

    .summary-table td {
      padding: 5px;
      text-align: right;
    }

    .summary-table tr:last-child td {
      font-weight: bold;
      border-top: 2px solid #000;
    }

    .summary-table tr td:first-child {
      text-align: left;
    }

    /* Instruksi Pembayaran */
    .payment-instruction {
      margin-top: 20px;
      clear: both;
    }

    .payment-instruction strong {
      display: block;
      margin-bottom: 5px;
      font-size: 14px;
    }

    .note {
      font-size: 10px;
      font-style: italic;
      color: #666;
    }

    .img-style {
      border-radius: 50%;
    }
  </style>
</head>

<body>

  <div class="container">
    {{-- BAGIAN 1: HEADER & INFO PERUSAHAAN (Stella Vet Clinic) --}}
    <div class="header">
      <div class="header-left">
        <h1>{{ $clinicData['name']}}</h1>
        <p>{{ $clinicData['address']}}</p>
      </div>
      <!-- <div class="header-right">
        <img src="{{ public_path('assets/image/stella.jpeg') }}" width="100" height="100"
          class="img-style">
      </div> -->
    </div>

    <hr>

    {{-- BAGIAN 2: INFO PELANGGAN DAN DETAIL INVOICE --}}
    <div class="info-grid">
      {{-- Kolom Kiri: Invoice Kepada --}}
      <div class="billing-info">
        <strong>Invoice Kepada:</strong>
        <p>{{ $invoiceData['customer_name'] ?? 'Rita' }}</p>
        <p>Telepon Selular: {{ $invoiceData['customer_phone'] ?? '+62 81336538443' }}</p>
      </div>

      {{-- Kolom Kanan: Detail Invoice --}}
      <div class="invoice-details">
        <table>
          <tr>
            <td>No. Invoice:</td>
            <td>{{ $invoiceData['no_invoice'] ?? 'N/A' }}</td>
          </tr>
          <tr>
            <td>Tanggal Invoice:</td>
            <td>{{ $invoiceData['tanggal_invoice'] ?? 'N/A' }}</td>
          </tr>
          <!-- <tr>
            <td>Jatuh Tempo:</td>
            <td>{{ $invoiceData['jatuh_tempo'] ?? 'N/A' }}</td>
          </tr>
          <tr>
            <td>Sisa Tagihan:</td>
            <td>Rp {{ number_format($invoiceData['sisa_tagihan'] ?? 0, 0, ',', '.') }}</td>
          </tr> -->
        </table>
      </div>
    </div>

    {{-- BAGIAN 3: TABEL ITEM --}}
    <table class="item-table">
      <thead>
        <tr>
          <th>Nama</th>
          <th>Kuantitas</th>
          <th>Harga</th>
          <th>Total (Rp)</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($invoiceData['data_item'] ?? [] as $item)
        <tr>
          <td>{{ $item->group_name }}</td>
          <td class="qty">{{ $item->quantity }}</td>
          <td class="price">{{ number_format($item->each_price, 0, ',', '.') }}</td>
          <td class="amount">{{ number_format($item->price_overall, 0, ',', '.') }}</td>
        </tr>
        @empty
        @endforelse

        @forelse ($invoiceData['data_service'] ?? [] as $service)
        <tr>
          <td>{{ $service->item_name }}</td>
          <td class="qty">{{ $service->quantity }}</td>
          <td class="price">{{ number_format($service->selling_price, 0, ',', '.') }}</td>
          <td class="amount">{{ number_format($service->price_overall, 0, ',', '.') }}</td>
        </tr>
        @empty
        @endforelse


        @forelse ($invoiceData['data_petshop'] ?? [] as $petshop)
        <tr>
          <td>{{ $petshop->item_name }}</td>
          <td class="qty">{{ $petshop->quantity }}</td>
          <td class="price">{{ number_format($petshop->selling_price, 0, ',', '.') }}</td>
          <td class="amount">{{ number_format($petshop->price_overall, 0, ',', '.') }}</td>
        </tr>
        @empty
        @endforelse
      </tbody>
    </table>

    {{-- RINGKASAN TOTAL --}}
    <table class="summary-table">
      <!-- <tr>
        <td>Subtotal:</td>
        <td>Rp {{ number_format($invoiceData['subtotal'] ?? 0, 0, ',', '.') }}</td>
      </tr> -->
      <tr>
        <td>Total:</td>
        <td>Rp {{ number_format($invoiceData['total'] ?? 0, 0, ',', '.') }}</td>
      </tr>
    </table>

    {{-- INSTRUKSI PEMBAYARAN --}}
    <div class="payment-instruction">
      <strong>Instruksi Pembayaran</strong>
      {{ $clinicData['payment_instruction'] ?? '' }}
      <!-- <p>Gunakan informasi berikut ini untuk transfer bank, internet banking, deposit, dan buku cek:</p>
      <p><strong>MANDIRI BANK</strong></p>
      <p>Atas nama: {{ $invoiceData['bank']['atas_nama'] ?? 'N/A' }}</p>
      <p>Nomor rekening: {{ $invoiceData['bank']['nomor_rekening'] ?? 'N/A' }}</p>
      <p class="note">**Jika Anda melakukan pembayaran transfer bank, harap menyertakan Quote ID ini pada kolom referensi.</p>
      <br>
      <p>Terima kasih</p> -->
    </div>

  </div>

</body>

</html>
