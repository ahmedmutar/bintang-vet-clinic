<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Hasil Pemeriksaan - {{ $registration->registration_number }}</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: Arial, Helvetica, sans-serif;
      font-size: 12px;
      color: #222;
      background: #fff;
    }

    .header-wrap {
      background-color: #1a5276;
      padding: 18px 30px 14px;
      color: #fff;
    }
    .header-clinic { font-size: 22px; font-weight: bold; letter-spacing: 1px; }
    .header-sub    { font-size: 12px; margin-top: 2px; color: #aed6f1; }
    .header-divider { border: none; border-top: 3px solid #2ecc71; margin: 0; }

    .header-meta {
      background: #f4f6f7;
      padding: 8px 30px;
      border-bottom: 1px solid #d5d8dc;
    }
    .header-meta table { width: 100%; border-collapse: collapse; }
    .header-meta td   { padding: 3px 0; font-size: 12px; }
    .header-meta .label { color: #555; width: 140px; }
    .header-meta .value { font-weight: bold; color: #1a5276; }
    .header-meta .doc-title {
      text-align: right;
      font-size: 16px;
      font-weight: bold;
      color: #1a5276;
      vertical-align: top;
    }

    .content { padding: 18px 30px; }

    .section { margin-bottom: 14px; }
    .section-title {
      font-size: 11px;
      font-weight: bold;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: #fff;
      background: #2c6e49;
      padding: 4px 10px;
      margin-bottom: 0;
    }
    .section-body {
      border: 1px solid #d5d8dc;
      border-top: none;
    }

    table.info { width: 100%; border-collapse: collapse; }
    table.info tr { border-bottom: 1px solid #eaecee; }
    table.info tr:last-child { border-bottom: none; }
    table.info td {
      padding: 6px 10px;
      vertical-align: top;
      font-size: 12px;
    }
    table.info td.lbl {
      width: 32%;
      color: #555;
      background: #fafafa;
      border-right: 1px solid #eaecee;
    }
    table.info td.val { color: #222; }

    .data-table {
      width: 100%;
      border-collapse: collapse;
      border: 1px solid #d5d8dc;
      border-top: none;
      margin-bottom: 14px;
    }
    .data-table thead tr { background: #eaf4fb; }
    .data-table thead th {
      padding: 7px 10px;
      font-size: 11px;
      font-weight: bold;
      color: #1a5276;
      text-align: left;
      border-bottom: 2px solid #aed6f1;
    }
    .data-table thead th.center { text-align: center; }
    .data-table thead th.right  { text-align: right; }
    .data-table tbody tr { border-bottom: 1px solid #eaecee; }
    .data-table tbody tr:last-child { border-bottom: none; }
    .data-table tbody td {
      padding: 6px 10px;
      font-size: 12px;
      vertical-align: middle;
    }
    .data-table tbody td.no    { text-align: center; width: 7%;  color: #888; }
    .data-table tbody td.right { text-align: right; }
    .data-table tfoot td {
      padding: 7px 10px;
      font-size: 12px;
      border-top: 2px solid #d5d8dc;
      background: #f4f6f7;
    }
    .total-val { font-weight: bold; font-size: 13px; color: #1a5276; }

    .group-header td {
      background: #eaf4fb;
      font-weight: bold;
      color: #1a5276;
      padding: 5px 10px;
      font-size: 11px;
      border-bottom: 1px solid #d5d8dc;
    }

    .badge {
      display: inline;
      padding: 2px 8px;
      font-size: 11px;
      font-weight: bold;
      border-radius: 3px;
    }
    .badge-success { background: #eafaf1; color: #1e8449; border: 1px solid #a9dfbf; }
    .badge-warning { background: #fef9e7; color: #b7950b; border: 1px solid #f9e79f; }
    .badge-info    { background: #eaf2ff; color: #1a5276; border: 1px solid #aed6f1; }

    .sign-table { width: 100%; border-collapse: collapse; margin-top: 30px; }
    .sign-table td {
      text-align: center;
      width: 50%;
      font-size: 12px;
      color: #444;
      padding: 0 20px;
      vertical-align: top;
    }
    .sign-role { font-weight: bold; color: #1a5276; margin-bottom: 4px; }
    .sign-line {
      border-top: 1px solid #888;
      margin-top: 50px;
      padding-top: 4px;
      font-size: 11px;
      color: #555;
    }

    .page-footer {
      margin-top: 24px;
      border-top: 2px solid #1a5276;
      padding-top: 6px;
      text-align: center;
      font-size: 10px;
      color: #888;
    }
  </style>
</head>
<body>

  <div class="header-wrap">
    <div class="header-clinic">{{ $registration->branch_name }}</div>
    <div class="header-sub">Hasil Pemeriksaan Pasien</div>
  </div>
  <hr class="header-divider">

  <div class="header-meta">
    <table>
      <tr>
        <td>
          <table>
            <tr>
              <td class="label">No. Registrasi</td>
              <td class="value">: {{ $registration->registration_number }}</td>
            </tr>
            <tr>
              <td class="label">Tanggal Periksa</td>
              <td class="value">: {{ $data->created_at }}</td>
            </tr>
          </table>
        </td>
        <td class="doc-title">HASIL PEMERIKSAAN</td>
      </tr>
    </table>
  </div>

  <div class="content">

    {{-- Data Pasien --}}
    <div class="section">
      <div class="section-title">Data Pasien</div>
      <div class="section-body">
        <table class="info">
          <tr>
            <td class="lbl">No. Pasien</td>
            <td class="val">{{ $registration->patient_number }}</td>
          </tr>
          <tr>
            <td class="lbl">Jenis Hewan</td>
            <td class="val">{{ $registration->pet_category }}</td>
          </tr>
          <tr>
            <td class="lbl">Nama Hewan</td>
            <td class="val">{{ $registration->pet_name }}</td>
          </tr>
          <tr>
            <td class="lbl">Jenis Kelamin</td>
            <td class="val">{{ ucfirst($registration->pet_gender) }}</td>
          </tr>
          <tr>
            <td class="lbl">Usia Hewan</td>
            <td class="val">
              {{ $registration->pet_year_age }} Tahun &nbsp;
              {{ $registration->pet_month_age }} Bulan &nbsp;
              {{ $registration->pet_day_age ?? 0 }} Hari
            </td>
          </tr>
          <tr>
            <td class="lbl">Nama Pemilik</td>
            <td class="val">{{ $registration->owner_name }}</td>
          </tr>
          <tr>
            <td class="lbl">Alamat Pemilik</td>
            <td class="val">{{ $registration->owner_address }}</td>
          </tr>
          <tr>
            <td class="lbl">No. HP Pemilik</td>
            <td class="val">{{ $registration->owner_phone_number }}</td>
          </tr>
          <tr>
            <td class="lbl">Keluhan</td>
            <td class="val">{{ $registration->complaint }}</td>
          </tr>
        </table>
      </div>
    </div>

    {{-- Data Pemeriksaan --}}
    <div class="section">
      <div class="section-title">Data Pemeriksaan</div>
      <div class="section-body">
        <table class="info">
          <tr>
            <td class="lbl">Anamnesa</td>
            <td class="val">{{ $data->anamnesa ?: '-' }}</td>
          </tr>
          <tr>
            <td class="lbl">Gejala (Sign)</td>
            <td class="val">{{ $data->sign ?: '-' }}</td>
          </tr>
          <tr>
            <td class="lbl">Diagnosa</td>
            <td class="val">{{ $data->diagnosa ?: '-' }}</td>
          </tr>
          <tr>
            <td class="lbl">Status</td>
            <td class="val">
              @if ($data->status_finish == 1)
                <span class="badge badge-success">Selesai</span>
              @else
                <span class="badge badge-warning">Belum Selesai</span>
              @endif
            </td>
          </tr>
        </table>
      </div>
    </div>

    {{-- Jasa --}}
    @if (count($services) > 0)
    <div class="section-title">Rincian Jasa</div>
    <table class="data-table">
      <thead>
        <tr>
          <th class="center" style="width:7%">No</th>
          <th>Nama Jasa</th>
          <th>Kategori</th>
          <th class="center" style="width:10%">Qty</th>
        </tr>
      </thead>
      <tbody>
        @php $noSvc = 1; @endphp
        @foreach ($services as $svc)
        <tr>
          <td class="no">{{ $noSvc++ }}</td>
          <td>{{ $svc->service_name }}</td>
          <td>{{ $svc->category_name }}</td>
          <td style="text-align:center">{{ $svc->quantity }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
    @endif

    {{-- Obat --}}
    @if (count($item) > 0)
    <div class="section-title">Rincian Obat / Barang</div>
    <table class="data-table">
      <thead>
        <tr>
          <th class="center" style="width:5%">No</th>
          <th style="width:11%">Tanggal</th>
          <th>Nama Barang</th>
          <th style="width:14%">Kategori Barang</th>
          <th style="width:10%">Satuan</th>
          <th class="center" style="width:7%">Jumlah</th>
        </tr>
      </thead>
      <tbody>
        @php $noItem = 1; @endphp
        @foreach ($item as $group)
        @foreach ($group->list_of_medicine as $med)
        <tr>
          <td class="no">{{ $noItem++ }}</td>
          <td>{{ $med->created_at }}</td>
          <td>{{ ($group->remark && (stripos($med->item_name, 'sample') !== false || stripos($med->item_name, 'sampel') !== false)) ? $group->remark : $med->item_name }}</td>
          <td>{{ $med->category_name }}</td>
          <td>{{ $med->unit_name }}</td>
          <td style="text-align:center">{{ $med->quantity }}</td>
        </tr>
        @endforeach
        @endforeach
      </tbody>
    </table>
    @endif


<div class="page-footer">
      {{ $data->created_at }}
    </div>

  </div>

</body>
</html>
