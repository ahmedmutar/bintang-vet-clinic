<?php

use Illuminate\Support\Facades\Route;
use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
	return view('index');
});

Route::get('/masuk', function () {
	return view('auth.login');
});

Route::get('/user', function () {
	return view('user.index');
});

Route::get('/cabang', function () {
	return view('cabang.index');
});

Route::get('/pasien', function () {
	return view('pasien.index');
});

Route::get('/riwayat-pameriksaan/{id}', function () {
	return view('pasien.riwayat-pasien');
});

Route::get('/kategori-barang', function () {
	return view('gudang.kategori-barang.index');
});

Route::get('/satuan-barang', function () {
	return view('gudang.satuan-barang.index');
});

Route::get('/daftar-barang', function () {
	return view('gudang.daftar-barang.index');
});

Route::get('/daftar-barang-petshop', function () {
	return view('gudang.daftar-barang-petshop.index');
});

Route::get('/pembagian-harga-barang', function () {
	return view('gudang.pembagian-harga.index');
});

Route::get('/pembagian-harga-barang-petshop', function () {
	return view('gudang.pembagian-harga-petshop.index');
});

Route::get('/pembagian-harga-kelompok-obat', function () {
	return view('gudang.pembagian-harga-kelompok-obat.index');
});

Route::get('/kategori-jasa', function () {
	return view('layanan.kategori-jasa.index');
});

Route::get('/daftar-jasa', function () {
	return view('layanan.daftar-jasa.index');
});

Route::get('/pembagian-harga-jasa', function () {
	return view('layanan.pembagian-harga.index');
});

Route::get('/metode-pembayaran', function () {
	return view('layanan.metode-pembayaran.index');
});


// Route::get('/rawat-inap', function () {
// 	return view('pendaftaran.rawat-inap.index');
// });

Route::get('/pendaftaran', function () {
	return view('pendaftaran.pendaftaran-pasien.index');
});

Route::get('/penerimaan-pasien', function () {
	return view('dokter.penerimaan-pasien.index');
});

// Route::get('/dokter-rawat-inap', function () {
// 	return view('dokter.rawat-inap.index');
// });

Route::get('/hasil-pemeriksaan', function () {
	return view('hasil-pemeriksaan.index');
});

Route::get('/hasil-pemeriksaan/tambah', function () {
	return view('hasil-pemeriksaan.form-hasil-pemeriksaan');
});

Route::get('/hasil-pemeriksaan/edit/{id}', function () {
	return view('hasil-pemeriksaan.form-hasil-pemeriksaan');
});

Route::get('/hasil-pemeriksaan/detail/{id}', function () {
	return view('hasil-pemeriksaan.detail-hasil-pemeriksaan');
});

Route::get('/pembayaran', function () {
	return view('pembayaran.index');
});

Route::get('/pembayaran/tambah', function () {
	return view('pembayaran.pembayaran-tambah');
});

Route::get('/pembayaran/edit/{id}', function () {
	return view('pembayaran.pembayaran-edit');
});

Route::get('/pembayaran/detail/{id}', function () {
	return view('pembayaran.pembayaran-detail');
});

Route::get('/kelompok-obat', function () {
	return view('gudang.kelompok-obat.index');
});

Route::get('/laporan-keuangan-harian', function () {
	return view('laporan-keuangan.harian.index');
});

Route::get('/laporan-keuangan-harian/detail/{id}', function () {
	return view('laporan-keuangan.harian.detail-harian');
});

Route::get('/laporan-keuangan-mingguan', function () {
	return view('laporan-keuangan.mingguan.index');
});

Route::get('/laporan-keuangan-mingguan/detail/{id}', function () {
	return view('laporan-keuangan.mingguan.detail-mingguan');
});

Route::get('/laporan-keuangan-bulanan', function () {
	return view('laporan-keuangan.bulanan.index');
});

Route::get('/laporan-keuangan-rekap', function () {
	return view('laporan-keuangan.rekap.index');
});

Route::get('/laporan-keuangan-omset', function () {
	return view('laporan-keuangan.omset.index');
});

Route::get('/laporan-keuangan-bulanan/detail/{id}', function () {
	return view('laporan-keuangan.bulanan.detail-bulanan');
});

Route::get('/profil/{id}', function () {
	return view('profil.index');
});

Route::get('/penggajian', function () {
	return view('penggajian.index');
});

Route::get('/pengeluaran', function () {
	return view('pengeluaran.index');
});

Route::get('pembayaran/print/{check_up_result_id}/{service_payment}/{item_payment}', 'PembayaranController@print_pdf');

Route::get('penggajian/generate/{id}', 'PenggajianController@generate');

Route::get('pembayaranpetshop/printreceipt/{master_payment_id}', 'PembayaranPetShopController@print_receipt');

Route::post('/print', function(Request $request) {
  if ($request->ajax()) {
		try {
			$ip = '192.168.111.11'; // IP Komputer kita atau printer lain yang masih satu jaringan
			// $printer = 'EPSON TM-U220 Receipt'; // Nama Printer yang di sharing
      // $printer = 'POS-58';
      // $connector = new WindowsPrintConnector("smb://" . $ip . "/" . $printer);
      $connector = new WindowsPrintConnector('POS-58');
      $printer = new Printer($connector);
      // info('check_up_result_id', $request->check_up_result_id);
      // info('data', $request->data);
      // Nama 14 karakter, qty 3, Harga 6, total 6
      $printer->setJustification(Printer::JUSTIFY_CENTER);
      $printer -> text('Stella Vet Clinic' . "\n", Printer::JUSTIFY_CENTER);
      $printer->setJustification(Printer::JUSTIFY_LEFT);
      $printer -> text($request->address. "\n");
      $printer -> text('--------------------------------'. "\n");
      $printer -> text('Data Pasien'. "\n");
      $printer -> text('No. Pasien:'. "\n");
      $printer -> text($request->id_patient. "\n");
      $printer -> text('Nama Hewan:'. "\n");
      $printer -> text($request->pet_name. "\n");
      $printer -> text('Nama Pemilik:'. "\n");
      $printer -> text($request->owner_name. "\n");
      $printer -> text('--------------------------------'. "\n");
      $printer -> text('No. Berobat:'. "\n");
      $printer -> text($request->registration_number. "\n");
      $printer -> text('Kasir:'. "\n");
      $printer -> text($request->cashier_name. "\n");
      $printer -> text('Tanggal:'. "\n");
      $printer -> text($request->time. "\n");
      $printer -> text('--------------------------------'. "\n");
      $printer -> text('Nama       Qty  Harga    Total'. "\n");
      $printer -> text('--------------------------------'. "\n");

      $content = '';
      $getTable = $request->table;
      // info('data', $getTable);
      for($i = 0; $i < count($getTable) ; $i++) {
        $content .= $getTable[$i]['name']."\n"."            ".$getTable[$i]['qty']."   ".$getTable[$i]['harga']."  ".$getTable[$i]['total']."\n";
      }
      $printer -> text($content);

      $printer -> text('--------------------------------'. "\n");
      $textQty = $request->quantity_total > 1 ? 'items(s)' : 'item';
      $printer -> text('Total-'.$request->quantity_total.$textQty.'. Rp.'.$request->price_overall.',-'. "\n");
      $printer -> feed(2);
      $printer -> cut();
      $printer -> close();
      $response = ['success'=> 'true'];
		} catch (Exception $e) {
      info($e);
      $response = ['success'=>'false'];
		}

		return response()->json($response);
	}
});

Route::get('/unauthorized', function () {
	return view('errors.unauthorized');
});
