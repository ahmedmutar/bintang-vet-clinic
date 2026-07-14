<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Slip Gaji - {{ $data_user[0]->fullname }}</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: Arial, Helvetica, sans-serif;
      font-size: 12px;
      color: #222;
      background: #fff;
    }

    /* ── Header banner ── */
    .header-wrap {
      background-color: #1a5276;
      padding: 16px 30px 12px;
      color: #fff;
    }
    .header-clinic { font-size: 22px; font-weight: bold; letter-spacing: 1px; }
    .header-sub    { font-size: 11px; margin-top: 2px; color: #aed6f1; }
    .header-divider { border: none; border-top: 3px solid #2ecc71; margin: 0; }

    .header-meta {
      background: #f4f6f7;
      padding: 8px 30px;
      border-bottom: 1px solid #d5d8dc;
    }
    .header-meta table { width: 100%; border-collapse: collapse; }
    .header-meta td   { padding: 3px 0; font-size: 12px; vertical-align: top; }
    .header-meta .lbl { color: #555; width: 110px; }
    .header-meta .val { font-weight: bold; color: #1a5276; }
    .header-meta .doc-title {
      text-align: right;
      font-size: 16px;
      font-weight: bold;
      color: #1a5276;
    }

    /* ── Content ── */
    .content { padding: 18px 30px; }

    /* ── Employee info box ── */
    .emp-box {
      border: 1px solid #d5d8dc;
      margin-bottom: 16px;
    }
    .emp-box-title {
      background: #2c6e49;
      color: #fff;
      font-size: 11px;
      font-weight: bold;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      padding: 4px 10px;
    }
    .emp-grid { width: 100%; border-collapse: collapse; }
    .emp-grid td {
      padding: 5px 10px;
      font-size: 12px;
      vertical-align: top;
      border-bottom: 1px solid #eaecee;
    }
    .emp-grid tr:last-child td { border-bottom: none; }
    .emp-grid .lbl { width: 30%; color: #555; background: #fafafa; border-right: 1px solid #eaecee; }
    .emp-grid .val { width: 20%; color: #222; }

    /* ── Salary table ── */
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
    .salary-table {
      width: 100%;
      border-collapse: collapse;
      border: 1px solid #d5d8dc;
      border-top: none;
      margin-bottom: 16px;
    }
    .salary-table thead tr {
      background: #eaf4fb;
    }
    .salary-table thead th {
      padding: 7px 10px;
      font-size: 11px;
      font-weight: bold;
      color: #1a5276;
      text-align: left;
      border-bottom: 2px solid #aed6f1;
    }
    .salary-table thead th.center { text-align: center; }
    .salary-table tbody tr { border-bottom: 1px solid #eaecee; }
    .salary-table tbody tr:last-child { border-bottom: none; }
    .salary-table tbody td {
      padding: 6px 10px;
      font-size: 12px;
      vertical-align: middle;
    }
    .salary-table tbody td.no { text-align: center; width: 8%; color: #888; }
    .salary-table tfoot td {
      padding: 7px 10px;
      font-size: 12px;
      border-top: 2px solid #d5d8dc;
    }
    .terbilang {
      background: #f4f6f7;
      border: 1px solid #d5d8dc;
      border-top: none;
      padding: 6px 10px;
      font-size: 11px;
      color: #555;
      margin-bottom: 16px;
    }
    .total-val { font-weight: bold; font-size: 13px; color: #1a5276; }

    /* ── Signature ── */
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

    /* ── Footer ── */
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
    <div class="header-clinic">{{ $data_user[0]->branch_name }}</div>
    <div class="header-sub">{{ $data_user[0]->branch_address }}</div>
  </div>
  <hr class="header-divider">

  <div class="header-meta">
    <table>
      <tr>
        <td>
          <table>
            <tr>
              <td class="lbl">Periode</td>
              <td class="val">: {{ $month_period }}</td>
            </tr>
            <tr>
              <td class="lbl">Tanggal Bayar</td>
              <td class="val">: {{ $data_user[0]->date_payed }}</td>
            </tr>
            <tr>
              <td class="lbl">Kode Karyawan</td>
              <td class="val">: {{ $data_user[0]->staffing_number }}</td>
            </tr>
          </table>
        </td>
        <td class="doc-title">SLIP GAJI<br>{{ $month_period }}</td>
      </tr>
    </table>
  </div>

  <div class="content">

    {{-- ── Data Karyawan ── --}}
    <div class="emp-box">
      <div class="emp-box-title">Data Karyawan</div>
      <table class="emp-grid">
        <tr>
          <td class="lbl">Nama</td>
          <td class="val">{{ $data_user[0]->fullname }}</td>
          <td class="lbl">Alamat</td>
          <td class="val">{{ $data_user[0]->address }}</td>
        </tr>
        <tr>
          <td class="lbl">Jabatan</td>
          <td class="val">{{ ucfirst($data_user[0]->role) }}</td>
          <td class="lbl">No. Telepon</td>
          <td class="val">{{ $data_user[0]->phone_number }}</td>
        </tr>
        <tr>
          <td class="lbl">Masa Kerja</td>
          <td class="val" colspan="3">{{ $month_period }}</td>
        </tr>
      </table>
    </div>

    {{-- ── Rincian Gaji ── --}}
    <div class="section-title">Rincian Gaji</div>
    <table class="salary-table">
      <thead>
        <tr>
          <th class="center" style="width:8%">No</th>
          <th>Keterangan</th>
          <th>Jumlah</th>
        </tr>
      </thead>
      <tbody>
        @php $num = 1; @endphp

        @if ($data_user[0]->basic_sallary > 0)
        <tr>
          <td class="no">{{ $num++ }}</td>
          <td>Gaji Pokok</td>
          <td>Rp {{ number_format($data_user[0]->basic_sallary) }}</td>
        </tr>
        @endif

        @if ($data_user[0]->accomodation > 0)
        <tr>
          <td class="no">{{ $num++ }}</td>
          <td>Akomodasi</td>
          <td>Rp {{ number_format($data_user[0]->accomodation) }}</td>
        </tr>
        @endif

        @if ($data_user[0]->eat > 0)
        <tr>
          <td class="no">{{ $num++ }}</td>
          <td>Uang Makan</td>
          <td>Rp {{ number_format($data_user[0]->eat) }}</td>
        </tr>
        @endif

        @if ($data_user[0]->total_turnover > 0)
        <tr>
          <td class="no">{{ $num++ }}</td>
          <td>Bonus Omzet</td>
          <td>Rp {{ number_format($data_user[0]->total_turnover) }}</td>
        </tr>
        @endif

        @if ($data_user[0]->total_inpatient > 0)
        <tr>
          <td class="no">{{ $num++ }}</td>
          <td>Bonus Rawat Inap</td>
          <td>Rp {{ number_format($data_user[0]->total_inpatient) }}</td>
        </tr>
        @endif

        @if ($data_user[0]->total_surgery > 0)
        <tr>
          <td class="no">{{ $num++ }}</td>
          <td>Bonus Operasi</td>
          <td>Rp {{ number_format($data_user[0]->total_surgery) }}</td>
        </tr>
        @endif

        @if ($data_user[0]->total_grooming > 0)
        <tr>
          <td class="no">{{ $num++ }}</td>
          <td>Bonus Grooming</td>
          <td>Rp {{ number_format($data_user[0]->total_grooming) }}</td>
        </tr>
        @endif

        @if ($data_user[0]->fine > 0)
        <tr>
          <td class="no">{{ $num++ }}</td>
          <td>Denda</td>
          <td>Rp {{ number_format($data_user[0]->fine) }}</td>
        </tr>
        @endif
      </tbody>
      <tfoot>
        <tr>
          <td colspan="2" style="text-align:right; font-weight:bold; color:#333;">
            Total Diterima
          </td>
          <td class="total-val">
            Rp {{ number_format($data_user[0]->total_overall) }}
          </td>
        </tr>
      </tfoot>
    </table>

    <div class="terbilang">
      <strong>Terbilang:</strong> {{ $terbilang }} rupiah
    </div>

    {{-- ── Tanda Tangan ── --}}
    <table class="sign-table">
      <tr>
        <td>
          <div class="sign-role">Penerima</div>
          <div class="sign-line">{{ $data_user[0]->fullname }}</div>
        </td>
        <td>
          <div class="sign-role">Penanggung Jawab</div>
          <div class="sign-line">Pribadi M.Y</div>
        </td>
      </tr>
    </table>

    <div class="page-footer">
      Dokumen ini diterbitkan secara otomatis oleh sistem {{ $data_user[0]->branch_name }} &bull; {{ $data_user[0]->date_payed }}
    </div>

  </div>

</body>
</html>
