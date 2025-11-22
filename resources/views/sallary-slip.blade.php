<!DOCTYPE html>
<html>

<head>
    <style>
        @page {
            margin: 0px;
            font-size: 20px;
            /* font-family: Calibri; */
        }

        body {
            margin: 0px;
            font-size: 20px;
            font-family: Arial, Helvetica, sans-serif;
        }

        th.colNumber {
            width: 10%;
            border-bottom: 1px solid black;
            border-collapse: collapse;
        }

        th.colRemark {
            text-align: left;
            border-bottom: 1px solid black;
            border-collapse: collapse;
            width: 90%
        }

        th.colAmount {
            text-align: left;
            border-bottom: 1px solid black;
            border-collapse: collapse;
            width: 50%
        }

        table,
        td,
        th {
            border: 0px solid black;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            padding: 12px;
            margin: 0px;
        }

        th {
            height: 10px;
        }

        label.title {
            font-weight: bold;
            font-size: 25px
        }

        label.titlePetName {
            font-weight: bold;
            font-size: 20px
        }

        label.address {
            font-size: 15px
        }

        td.date {
            text-align: right;
            font-size: 15px;
        }

        td.codeEmployee {
            text-align: right;
            font-size: 15px;
            border-bottom: 1px solid black;
            border-collapse: collapse;
        }

        .img-style {
            border-radius: 50%;
        }

        .center-content {
            text-align: center;
            vertical-align: middle;
        }

        td.underTitle {
            border-bottom: 1px solid black;
            border-collapse: collapse;
        }

    </style>
    {{-- <title>{{ $registration_number }}</title> --}}
</head>


<body>
    <table style="width: 100%">
        <tr>
            <td style="width: 33%">
                <label class="titlePetName">Stella Vet Clinic</label>
            </td>
            <td>

            </td>
            <td>

            </td>
        </tr>
        <tr>
            <td style="width: 33%">
                <label class="address">{{ $data_user[0]->branch_address }}</label>
            </td>
            <td style="text-align: center">
                <label class="title">SLIP GAJI {{ $month_period }}</label>
            </td>
            <td class="date">
                <label>Tanggal: {{ $data_user[0]->date_payed }}</label>
            </td>

        </tr>

        <tr>
            <td class="underTitle">

            </td>
            <td class="underTitle">

            </td>
            <td class="codeEmployee">
                <label>Kode Karyawan:{{ $data_user[0]->staffing_number }}</label>
            </td>

        </tr>
    </table>

    <table style="width: 100%">

        <tr>
            <td style="width:50%">
                <label>Nama: {{ $data_user[0]->fullname }}</label>
            </td>
            <td>
                <label>Alamat: {{ $data_user[0]->address }}</label>
            </td>
            <td>
                <label>&nbsp;</label>
            </td>

        </tr>

        <tr>
            <td>
                <label>Jabatan: {{ ucfirst($data_user[0]->role) }}</label>
            </td>
            <td>
                <label>Telepon: {{ $data_user[0]->phone_number }}</label>
            </td>
            <td>
                <label></label>
            </td>

        </tr>

        <tr>
            <td style="width:50%; border-bottom: 1px solid black; border-collapse: collapse;">
                <label>&nbsp;</label>
            </td>
            <td style="border-bottom: 1px solid black; border-collapse: collapse;">
                <label>Masa Kerja: {{ $month_period }}</label>
            </td>
            <td style="border-bottom: 1px solid black; border-collapse: collapse;">
                <label>&nbsp;</label>
            </td>
        </tr>

    </table>

    <table style="width:100%; border: 1px solid black;border-collapse: collapse;">
        <tr>
            <th class="colNumber">
                <label>No</label>
                </td>
            <th class="colRemark">
                <label>Keterangan</label>
            </th>
            <th class="colAmount">
                <label>Jumlah</label>
            </th>
        </tr>

        @php
            $num = 1;
        @endphp

        @if ($data_user[0]->basic_sallary > 0)
            <tr>
                <td style="text-align: center">
                    <label>{{ $num }}</label>
                </td>
                <td>
                    <label>Gaji Pokok</label>
                </td>
                <td>
                    <label>Rp {{ number_format($data_user[0]->basic_sallary) }}</label>
                </td>
            </tr>
            @php
                $num++;
            @endphp
            <tr>
                <td>
                    &nbsp;
                </td>
                <td>

                </td>
                <td>

                </td>
            </tr>
        @endif

        @if ($data_user[0]->accomodation > 0)
            <tr>
                <td style="text-align: center">
                    <label>{{ $num }}</label>
                </td>
                <td>
                    <label>Akomodasi</label>
                </td>
                <td>
                    <label>Rp {{ number_format($data_user[0]->accomodation) }}</label>
                </td>
            </tr>
            @php
                $num++;
            @endphp
            <tr>
                <td>
                    &nbsp;
                </td>
                <td>

                </td>
                <td>

                </td>
            </tr>
        @endif

        @if ($data_user[0]->total_turnover > 0)
            <tr>
                <td style="text-align: center">
                    <label>{{ $num }}</label>
                </td>
                <td>
                    <label>Bonus Omzet</label>
                </td>
                <td>
                    <label>Rp {{ number_format($data_user[0]->total_turnover) }}</label>
                </td>
            </tr>
            @php
                $num++;
            @endphp
            <tr>
                <td>
                    &nbsp;
                </td>
                <td>

                </td>
                <td>

                </td>
            </tr>
        @endif

        @if ($data_user[0]->total_inpatient > 0)
            <tr>
                <td style="text-align: center">
                    <label>{{ $num }}</label>
                </td>
                <td>
                    <label>Bonus Rawat Inap</label>
                </td>
                <td>
                    <label>Rp {{ number_format($data_user[0]->total_inpatient) }}</label>
                </td>
            </tr>
            @php
                $num++;
            @endphp
            <tr>
                <td>
                    &nbsp;
                </td>
                <td>

                </td>
                <td>

                </td>
            </tr>
        @endif

        @if ($data_user[0]->total_surgery > 0)
            <tr>
                <td style="text-align: center">
                    <label>{{ $num }}</label>
                </td>
                <td>
                    <label>Bonus Operasi</label>
                </td>
                <td>
                    <label>Rp {{ number_format($data_user[0]->total_surgery) }}</label>
                </td>
            </tr>
            @php
                $num++;
            @endphp
            <tr>
                <td>
                    &nbsp;
                </td>
                <td>

                </td>
                <td>

                </td>
            </tr>
        @endif

        @if ($data_user[0]->total_grooming > 0)
            <tr>
                <td style="text-align: center">
                    <label>{{ $num }}</label>
                </td>
                <td>
                    <label>Bonus Grooming</label>
                </td>
                <td>
                    <label>Rp {{ number_format($data_user[0]->total_grooming) }}</label>
                </td>
            </tr>

            <tr>
                <td>
                    &nbsp;
                </td>
                <td>

                </td>
                <td>

                </td>
            </tr>
        @endif


        <tr>
            <td style="border-top: 1px solid black; border-collapse: collapse;">
                <label></label>
            </td>
            <td style="text-align: right; border-top: 1px solid black; border-collapse: collapse;font-size: 20px">
                <label> <b>Total Diterima:</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>
            </td>
            <td style="border-top: 1px solid black; border-collapse: collapse;font-size: 20px">
                <label> <b> Rp {{ number_format($data_user[0]->total_overall) }}</b></label>
            </td>
        </tr>

        <tr>
            <td colspan="3" style="font-size: 20px;border-top: 1px solid black; border-collapse: collapse;">
                <label>Terbilang: {{ $terbilang }} rupiah</label>
            </td>
        </tr>
    </table>

    <table style="width: 100%">
        <tr>
            <td style="width: 9%">
                <label>&nbsp;</label>
            </td>
            <td class="colRemark" style="text-align: center;width: 10%;">
                <label>Penerima,</label>
            </td>
            <td class="colAmount" style="text-align: right">
                <label>Penanggung Jawab</label>
                {{-- {{ $data_user[0]->date_payed_diff_format }} --}}
            </td>
            <td style="width: 5%">
                <label>&nbsp;</label>
            </td>
        </tr>
    </table>
    <br>
    <table>
        <tr>

            <td style="text-align: center; width: 30%">
                <label>{{ $data_user[0]->fullname }}</label>
            </td>
            <td class="colAmount" style="text-align: right">
                <label>&nbsp;</label>
            </td>
            <td style="text-align: center; width: 35%">
                <label>Pribadi M.Y</label>
            </td>
        </tr>
    </table>
</body>

</html>
