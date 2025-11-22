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
    <title>{{ $registration_number }}</title>
</head>


<body>
    <table>
        <tr>
            <td class="center-content">
                <img src="{{ public_path('assets/image/logo-stella-vet.jpeg') }}" width="100" height="100"
                    class="img-style">
            </td>
        </tr>
        <tr>
            <td class="center-content">
                <label class="boldtext">Stella Vet Clinic</label>
            </td>
        </tr>
        <tr>
            <td class="center-content">
                <label class="boldtext">{{ $address }}</label>
            </td>
        </tr>
        <tr>
            <td>
                <label class="boldtext">------------------------------</label>
            </td>
        </tr>
        <tr>
            <td>
                <label class="boldtext">Data Pasien</label>
            </td>
        </tr>
        <tr>
            <td>
                <label class="boldtext">No. Pasien:</label>
            </td>
        </tr>
        <tr>
            <td>
                {{ $id_patient }}
            </td>
        </tr>
        <tr>
            <td>
                <label class="boldtext">Nama Hewan:</label>
            </td>
        </tr>
        <tr>
            <td>
                {{ $pet_name }}
            </td>
        </tr>
        <tr>
            <td>
                <label class="boldtext">Nama Pemilik:</label>
            </td>
        </tr>
        <tr>
            <td>
                {{ $owner_name }}
            </td>
        </tr>
        <tr>
            <td>
                <label class="boldtext">------------------------------</label>
            </td>
        </tr>
        <tr>
            <td>
                <label class="boldtext">No. Berobat:</label>
            </td>
        </tr>
        <tr>
            <td>
                {{ $registration_number }}
            </td>
        </tr>
        <tr>
            <td>
                <label class="boldtext">Dicetak oleh:</label>
            </td>
        </tr>
        <tr>
            <td>
                {{ $cashier_name }}
            </td>
        </tr>
        <tr>
            <td>
                <label class="boldtext">Tanggal:</label>
            </td>
        </tr>
        <tr>
            <td>
                {{ $time }}
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
            @if ($data_item)
                @foreach ($data_item as $item)
                    <tr>
                        <td>{{ $item->group_name }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ number_format($item->each_price) }}</td>
                        <td>{{ number_format($item->price_overall) }}</td>
                    </tr>
                    @if ($item->amount_discount != 0)
                        <tr>
                            <td colspan="3">
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Diskon:({{ number_format($item->amount_discount) }})
                            </td>
                        </tr>
                    @endif
                @endforeach
            @endif

            @if ($data_service)
                @foreach ($data_service as $service)
                    <tr>
                        <td>{{ $service->item_name }}</td>
                        <td>{{ $service->quantity }}</td>
                        <td>{{ number_format($service->selling_price) }}</td>
                        <td>{{ number_format($service->price_overall) }}</td>
                    </tr>
                    @if ($service->amount_discount != 0)
                        <tr>
                            <td colspan="3">
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Diskon:({{ number_format($service->amount_discount) }})
                            </td>
                        </tr>
                    @endif
                @endforeach
            @endif

            @if($data_petshop)
                @foreach ($data_petshop as $petshop)
                    <tr>
                        <td>{{ $petshop->item_name }}</td>
                        <td>{{ $petshop->quantity }}</td>
                        <td>{{ number_format($petshop->selling_price) }}</td>
                        <td>{{ number_format($petshop->price_overall) }}</td>
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
                <label class="boldtext">Total-item: Rp. {{ number_format($price_overall, 2) }},-</label>
            </td>
        </tr>
    </table>
</body>

</html>
