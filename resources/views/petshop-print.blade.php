<!DOCTYPE html>
<html>

<head>
    <style>
        @page {
            margin: 0px;
            font-size: 10px;
            /* font-family: Calibri; */
        }

        body {
            margin: 0px;
            font-size: 10px;
            font-family: 'Courier New', monospace;
        }

        table,
        td,
        th {
            border: 0px solid black;
        }

        table {
            border-collapse: collapse;
            width: 2in;
            padding: 0px;
            margin: 0px;
        }

        th {
            height: 10px;
        }

        label.boldtext {
            font-weight: bold;
        }

        .img-style {
            border-radius: 50%;
        }

        .center-content {
            text-align: center;
            vertical-align: middle;
        }

    </style>
    <title>{{ $data_header[0]->payment_number }}</title>
</head>


<body>
    <table>
        <tr>
            <td class="center-content">
                <img src="{{ public_path('assets/image/stella.jpeg') }}" width="100" height="100"
                    class="img-style">
            </td>
        </tr>

        <tr>
          <td class="center-content">
            <br>
          </td>
      </tr>

        <tr>
            <td class="center-content">
                <label class="boldtext">Stella Vet Clinic</label>
            </td>
        </tr>
        <tr>
            <td class="center-content">
                <label class="boldtext">{{ $data_header[0]->address }}</label>
            </td>
        </tr>
        <tr>
            <td>
                <label class="boldtext">------------------------------</label>
            </td>
        </tr>
        <tr>
            <td>
                <label class="boldtext">No. Transaksi:</label>
            </td>
        </tr>
        <tr>
            <td>
                {{ $data_header[0]->payment_number }}
            </td>
        </tr>
        <tr>
            <td>
                <label class="boldtext">Dicetak oleh:</label>
            </td>
        </tr>
        <tr>
            <td>
                {{ $data_header[0]->cashier_name }}
            </td>
        </tr>
        <tr>
            <td>
                <label class="boldtext">Tanggal:</label>
            </td>
        </tr>
        <tr>
            <td>
                {{ $data_header[0]->paid_time }}
            </td>
        </tr>
        <tr>
            <td>
                <label class="boldtext">------------------------------</label>
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <td>Nama</td>
                <td>Qty</td>
                <td>Harga</td>
                <td>Total</td>
            </tr>
            <tr>
                <td colspan="4">
                    <label class="boldtext">------------------------------</label>
                </td>
            </tr>
        </thead>
        <tbody>
            @if ($data_detail)
                @foreach ($data_detail as $item)
                    <tr>
                        <td>{{ $item->item_name }}</td>
                        <td>{{ $item->total_item }}</td>
                        <td>{{ number_format($item->each_price) }}</td>
                        <td>{{ number_format($item->total_price) }}</td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>

    <table>
        <tr>
            <td>
                <label class="boldtext">------------------------------</label>
            </td>
        </tr>
        <tr>
            <td>
                <label class="boldtext">Total-item: Rp. {{ number_format($price_overall->price_overall, 2) }},-</label>
            </td>
        </tr>
    </table>
</body>

</html>
