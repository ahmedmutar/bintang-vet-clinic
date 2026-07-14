<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Bukti Pendaftaran Pasien - {{ $data->id_number }}</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: Arial, Helvetica, sans-serif;
      font-size: 12px;
      color: #222;
      background: #fff;
      padding: 0;
      margin: 0;
    }

    /* ── Header banner ── */
    .header-wrap {
      background-color: #1a5276;
      padding: 18px 30px 14px;
      color: #fff;
    }
    .header-clinic { font-size: 22px; font-weight: bold; letter-spacing: 1px; }
    .header-sub    { font-size: 12px; margin-top: 2px; color: #aed6f1; }
    .header-divider {
      border: none;
      border-top: 3px solid #2ecc71;
      margin: 0;
    }
    .header-meta {
      background: #f4f6f7;
      padding: 8px 30px;
      border-bottom: 1px solid #d5d8dc;
    }
    .header-meta table { width: 100%; border-collapse: collapse; }
    .header-meta td   { padding: 3px 0; font-size: 12px; }
    .header-meta .label { color: #555; width: 130px; }
    .header-meta .value { font-weight: bold; color: #1a5276; }
    .header-meta .doc-title {
      text-align: right;
      font-size: 16px;
      font-weight: bold;
      color: #1a5276;
      vertical-align: top;
    }

    /* ── Content area ── */
    .content { padding: 18px 30px; }

    /* ── Section ── */
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

    /* ── Info rows ── */
    table.info { width: 100%; border-collapse: collapse; }
    table.info tr { border-bottom: 1px solid #eaecee; }
    table.info tr:last-child { border-bottom: none; }
    table.info td {
      padding: 6px 10px;
      vertical-align: top;
      font-size: 12px;
    }
    table.info td.lbl {
      width: 38%;
      color: #555;
      background: #fafafa;
      border-right: 1px solid #eaecee;
    }
    table.info td.val { color: #222; }

    /* ── Status badge ── */
    .status-badge {
      display: inline;
      padding: 2px 8px;
      font-size: 11px;
      font-weight: bold;
      border-radius: 3px;
    }
    .status-0 { background: #fef9e7; color: #b7950b; border: 1px solid #f9e79f; }
    .status-1 { background: #eafaf1; color: #1e8449; border: 1px solid #a9dfbf; }
    .status-2 { background: #fdedec; color: #c0392b; border: 1px solid #f5b7b1; }
    .status-3 { background: #eaf2ff; color: #1a5276; border: 1px solid #aed6f1; }

    /* ── Signature table ── */
    .sign-table { width: 100%; border-collapse: collapse; margin-top: 36px; }
    .sign-table td {
      text-align: center;
      width: 33%;
      font-size: 12px;
      color: #444;
      padding: 0 10px;
      vertical-align: top;
    }
    .sign-role { font-weight: bold; margin-bottom: 4px; color: #1a5276; }
    .sign-line {
      border-top: 1px solid #888;
      margin-top: 50px;
      padding-top: 4px;
      font-size: 11px;
      color: #555;
    }

    /* ── Footer strip ── */
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

  {{-- ── Header ── --}}
  <div class="header-wrap">
    <div class="header-clinic">{{ $data->branch_name }}</div>
  </div>
  <hr class="header-divider">

  <div class="header-meta">
    <table>
      <tr>
        <td>
          <table>
            <tr>
              <td class="label">No. Registrasi</td>
              <td class="value">: {{ $data->id_number }}</td>
            </tr>
            <tr>
              <td class="label">Tanggal</td>
              <td class="value">: {{ $data->created_at }}</td>
            </tr>
          </table>
        </td>
        <td class="doc-title">BUKTI PENDAFTARAN PASIEN</td>
      </tr>
    </table>
  </div>

  {{-- ── Content ── --}}
  <div class="content">

    {{-- Data Hewan --}}
    <div class="section">
      <div class="section-title">Data Hewan</div>
      <div class="section-body">
        <table class="info">
          <tr>
            <td class="lbl">Nomor Pasien</td>
            <td class="val">{{ $data->id_number_patient }}</td>
          </tr>
          <tr>
            <td class="lbl">Jenis Hewan</td>
            <td class="val">{{ $data->pet_category }}</td>
          </tr>
          <tr>
            <td class="lbl">Nama Hewan</td>
            <td class="val">{{ $data->pet_name }}</td>
          </tr>
          <tr>
            <td class="lbl">Jenis Kelamin</td>
            <td class="val">{{ ucfirst($data->pet_gender) }}</td>
          </tr>
          <tr>
            <td class="lbl">Usia Hewan</td>
            <td class="val">
              {{ $data->pet_year_age }} Tahun &nbsp;
              {{ $data->pet_month_age }} Bulan &nbsp;
              {{ $data->pet_day_age ?? 0 }} Hari
            </td>
          </tr>
        </table>
      </div>
    </div>

    {{-- Data Pemilik --}}
    <div class="section">
      <div class="section-title">Data Pemilik</div>
      <div class="section-body">
        <table class="info">
          <tr>
            <td class="lbl">Nama Pemilik</td>
            <td class="val">{{ $data->owner_name }}</td>
          </tr>
          <tr>
            <td class="lbl">Alamat Pemilik</td>
            <td class="val">{{ $data->owner_address }}</td>
          </tr>
          <tr>
            <td class="lbl">Nomor HP Pemilik</td>
            <td class="val">{{ $data->owner_phone_number }}</td>
          </tr>
        </table>
      </div>
    </div>

    {{-- Data Pendaftaran --}}
    <div class="section">
      <div class="section-title">Data Pendaftaran</div>
      <div class="section-body">
        <table class="info">
          <tr>
            <td class="lbl">Keluhan</td>
            <td class="val">
              {{ $data->complaint_name ?? $data->complaint }}
              @if($data->other_complaint)
                &mdash; {{ $data->other_complaint }}
              @endif
            </td>
          </tr>
          <tr>
            <td class="lbl">Nama Pendaftar</td>
            <td class="val">{{ $data->registrant }}</td>
          </tr>
          <tr>
            <td class="lbl">Status</td>
            <td class="val">
              @php
                $statusMap  = [0 => 'Menunggu Konfirmasi', 1 => 'Diterima', 2 => 'Ditolak', 3 => 'Selesai'];
                $statusClass = [0 => 'status-0', 1 => 'status-1', 2 => 'status-2', 3 => 'status-3'];
                $s = $data->acceptance_status;
              @endphp
              <span class="status-badge {{ $statusClass[$s] ?? '' }}">
                {{ $statusMap[$s] ?? '-' }}
              </span>
            </td>
          </tr>
        </table>
      </div>
    </div>

    <div class="page-footer">
      {{ $data->created_at }}
    </div>

  </div>

</body>
</html>
