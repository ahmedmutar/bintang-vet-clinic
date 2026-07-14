<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });
Route::group(['middleware' => ['api']], function () {

    Route::post('masuk', 'UserController@login');

    Route::post('lupa-password/kirim-otp',    'ForgotPasswordController@sendOtp')->middleware('throttle:3,1');
    Route::post('lupa-password/verifikasi-otp', 'ForgotPasswordController@verifyOtp')->middleware('throttle:5,1');
    Route::post('lupa-password/reset',        'ForgotPasswordController@reset')->middleware('throttle:5,1');

    Route::group(['middleware' => ['jwt.auth']], function () {

        Route::post('keluar', 'UserController@logout');

        //cabang
        Route::get('cabang', 'CabangController@index');
        Route::get('cabang/all', 'CabangController@listCabangAll');
        Route::post('cabang', 'CabangController@create');
        Route::put('cabang', 'CabangController@update');
        Route::delete('cabang', 'CabangController@delete');

        //user management
        Route::get('user', 'UserController@index');
        Route::post('user', 'UserController@register');
        Route::put('user', 'UserController@update');
        Route::delete('user', 'UserController@delete');

        Route::get('user/dokter', 'UserController@doctor');

        Route::post('user/upload-image', 'ProfileController@upload_photo_profile');
        Route::get('user/profile', 'ProfileController@get_data_user');
        Route::put('user/profile', 'ProfileController@update_data_user');
        Route::put('user/change-password', 'ProfileController@change_password')->middleware('throttle:5,1');
        Route::put('user/reset-password', 'UserController@reset_password')->middleware('throttle:10,1');

        //pasien
        Route::get('pasien', 'PasienController@index');
        Route::post('pasien', 'PasienController@create');
        Route::put('pasien', 'PasienController@update');
        Route::delete('pasien', 'PasienController@delete');
        Route::get('pasien/status-terima', 'PasienController@patient_accept_only');

        Route::get('pasien/daftar-pemilik', 'PasienController@ListOwner');

        Route::get('pasien/dropdown', 'PasienController@dropdown');
        Route::get('pasien/pet-categories', 'PasienController@petCategories');

        //kategori barang
        Route::get('kategori-barang', 'KategoriBarangController@index');
        Route::post('kategori-barang', 'KategoriBarangController@create');
        Route::put('kategori-barang', 'KategoriBarangController@update');
        Route::delete('kategori-barang', 'KategoriBarangController@delete');

        //satuan barang
        Route::get('satuan-barang', 'SatuanBarangController@index');
        Route::post('satuan-barang', 'SatuanBarangController@create');
        Route::put('satuan-barang', 'SatuanBarangController@update');
        Route::delete('satuan-barang', 'SatuanBarangController@delete');

        //daftar barang
        Route::get('daftar-barang-batas', 'DaftarBarangController@index_limit');
        Route::get('daftar-barang-batas-pet-shop', 'DaftarBarangPetshopController@index_limit');

        Route::get('daftar-barang', 'DaftarBarangController@index');
        Route::post('daftar-barang', 'DaftarBarangController@create');
        Route::put('daftar-barang', 'DaftarBarangController@update');
        Route::delete('daftar-barang', 'DaftarBarangController@delete');

        Route::get('daftar-barang/generate-excel', 'DaftarBarangController@generate_excel');

        Route::get('daftar-barang/download-template', 'DaftarBarangController@download_template');
        Route::post('daftar-barang/upload', 'DaftarBarangController@upload_template');

        //daftar barang pet shop
        // Route::get('daftar-barang-batas', 'DaftarBarangController@index_limit');

        Route::get('daftar-barang-petshop', 'DaftarBarangPetshopController@index');
        Route::post('daftar-barang-petshop', 'DaftarBarangPetshopController@create');
        Route::put('daftar-barang-petshop', 'DaftarBarangPetshopController@update');
        Route::delete('daftar-barang-petshop', 'DaftarBarangPetshopController@delete');

        Route::get('daftar-barang-petshop/generate-excel', 'DaftarBarangPetshopController@generate_excel');

        Route::get('daftar-barang-petshop/download-template', 'DaftarBarangPetshopController@download_template');
        Route::post('daftar-barang-petshop/upload', 'DaftarBarangPetshopController@upload_template');

        //kategori jasa
        Route::get('kategori-jasa', 'KategoriJasaController@index');
        Route::post('kategori-jasa', 'KategoriJasaController@create');
        Route::put('kategori-jasa', 'KategoriJasaController@update');
        Route::delete('kategori-jasa', 'KategoriJasaController@delete');

        //daftar jasa
        Route::get('daftar-jasa', 'DaftarJasaController@index');
        Route::post('daftar-jasa', 'DaftarJasaController@create');
        Route::put('daftar-jasa', 'DaftarJasaController@update');
        Route::delete('daftar-jasa', 'DaftarJasaController@delete');

        //pembagian harga jasa
        Route::get('pembagian-harga-jasa', 'HargaJasaController@index');
        Route::post('pembagian-harga-jasa', 'HargaJasaController@create');
        Route::put('pembagian-harga-jasa', 'HargaJasaController@update');
        Route::delete('pembagian-harga-jasa', 'HargaJasaController@delete');
        Route::get('pembagian-harga-jasa/kategori-jasa', 'HargaJasaController@service_category');
        Route::get('pembagian-harga-jasa/nama-jasa', 'HargaJasaController@service_name');

        Route::get('pembagian-harga-jasa/dropdown', 'HargaJasaController@dropdown');

        //pembagian harga barang
        Route::get('pembagian-harga-barang', 'HargaBarangController@index');
        Route::post('pembagian-harga-barang', 'HargaBarangController@create');
        Route::put('pembagian-harga-barang', 'HargaBarangController@update');
        Route::delete('pembagian-harga-barang', 'HargaBarangController@delete');
        Route::get('pembagian-harga-barang/kategori-barang', 'HargaBarangController@item_category');
        Route::get('pembagian-harga-barang/nama-barang', 'HargaBarangController@item_name');

        Route::get('pembagian-harga-barang/generate-excel', 'HargaBarangController@generate_excel');
        Route::get('pembagian-harga-barang/download-template', 'HargaBarangController@download_template');
        Route::post('pembagian-harga-barang/upload', 'HargaBarangController@upload_template');

        Route::get('pembagian-harga-barang/dropdown', 'HargaBarangController@dropdown');

        //pembagian harga barang pet shop
        Route::get('pembagian-harga-barang-petshop', 'HargaBarangPetShopController@index');
        Route::post('pembagian-harga-barang-petshop', 'HargaBarangPetShopController@create');
        Route::put('pembagian-harga-barang-petshop', 'HargaBarangPetShopController@update');
        Route::delete('pembagian-harga-barang-petshop', 'HargaBarangPetShopController@delete');
        Route::get('pembagian-harga-barang-petshop/barang-petshop', 'HargaBarangPetShopController@item_category');
        // Route::get('pembagian-harga-barang-petshop/nama-barang-petshop', 'HargaBarangPetShopController@item_name');

        Route::get('pembagian-harga-barang-petshop/generate-excel', 'HargaBarangPetShopController@generate_excel');
        Route::get('pembagian-harga-barang-petshop/download-template', 'HargaBarangPetShopController@download_template');
        Route::post('pembagian-harga-barang-petshop/upload', 'HargaBarangPetShopController@upload_template');

        Route::get('pembagian-harga-barang-petshop/dropdown', 'HargaBarangPetShopController@dropdown');

        //registrasi pasien
        Route::get('registrasi-pasien', 'RegistrasiController@index');
        Route::get('list-keluhan', 'RegistrasiController@listKeluhan');
        Route::post('registrasi-pasien', 'RegistrasiController@create');
        Route::put('registrasi-pasien', 'RegistrasiController@update');
        Route::delete('registrasi-pasien', 'RegistrasiController@delete');

        //penerimaan pasien
        Route::get('penerimaan-pasien', 'PenerimaanPasienController@index');
        Route::get('penerimaan-pasien/terima', 'PenerimaanPasienController@accept');
        Route::get('penerimaan-pasien/tolak', 'PenerimaanPasienController@decline');

        //hasil pemeriksaan
        Route::post('hasil-pemeriksaan', 'HasilPemeriksaanController@create');
        Route::get('hasil-pemeriksaan', 'HasilPemeriksaanController@index');
        Route::get('hasil-pemeriksaan/detail', 'HasilPemeriksaanController@detail');
        Route::put('hasil-pemeriksaan', 'HasilPemeriksaanController@update');
        Route::delete('hasil-pemeriksaan', 'HasilPemeriksaanController@delete');

        //Route::post('hasil-pemeriksaan/upload-gambar', 'HasilPemeriksaanController@upload_images');

        Route::post('hasil-pemeriksaan/update-upload-gambar', 'HasilPemeriksaanController@update_upload_images');

        Route::get('hasil-pemeriksaan/pembayaran', 'HasilPemeriksaanController@payment');

        //pembayaran    DropDownPatient
        Route::get('pembayaran/pasien', 'PembayaranController@DropDownPatient');
        Route::get('pembayaran', 'PembayaranController@index');
        Route::post('pembayaran', 'PembayaranController@create');
        Route::put('pembayaran', 'PembayaranController@update');
        Route::get('pembayaran/detail', 'PembayaranController@detail');
        Route::delete('pembayaran', 'PembayaranController@delete');

        //pembayaran petshop
        Route::get('pembayaranpetshop', 'PembayaranPetShopController@index');
        Route::get('pembayaranpetshop/filteritem', 'PembayaranPetShopController@filteritempetshop');
        Route::post('pembayaranpetshop', 'PembayaranPetShopController@create');
        Route::get('pembayaranpetshop/printreceipt', 'PembayaranPetShopController@print_receipt');
        Route::delete('pembayaranpetshop', 'PembayaranPetShopController@delete');
        // Route::get('pembayaran/print', 'PembayaranController@print_pdf');
        //Route::post('pembayaran/printpdf', 'PembayaranController@print_pdf');



        //Metode Pembayaran
        Route::get('metode-pembayaran', 'DaftarMetodePembayaranController@index');
        Route::post('metode-pembayaran', 'DaftarMetodePembayaranController@create');
        Route::put('metode-pembayaran', 'DaftarMetodePembayaranController@update');
        Route::delete('metode-pembayaran', 'DaftarMetodePembayaranController@delete');

        //Route::delete('pembayaran/all', 'PembayaranController@delete_all');

        //riwayat pasien
        Route::get('pasien/riwayat', 'PasienController@HistoryPatient');
        Route::get('pasien/detail-riwayat', 'PasienController@DetailHistoryPatient');

        //kategori obat
        Route::get('kelompok-obat', 'KelompokObatController@index');
        Route::post('kelompok-obat', 'KelompokObatController@create');
        Route::put('kelompok-obat', 'KelompokObatController@update');
        Route::delete('kelompok-obat', 'KelompokObatController@delete');

        Route::get('kelompok-obat/download-template', 'KelompokObatController@download_template');
        Route::post('kelompok-obat/upload-template', 'KelompokObatController@upload_template');

        //harga kelompok obat
        Route::get('pembagian-harga-kelompok-obat', 'HargaKelompokObatController@index');
        Route::post('pembagian-harga-kelompok-obat', 'HargaKelompokObatController@create');
        Route::put('pembagian-harga-kelompok-obat', 'HargaKelompokObatController@update');
        Route::delete('pembagian-harga-kelompok-obat', 'HargaKelompokObatController@delete');

        Route::get('pembagian-harga-kelompok-obat/download-template', 'HargaKelompokObatController@download_template');
        Route::post('pembagian-harga-kelompok-obat/upload-template', 'HargaKelompokObatController@upload_template');

        Route::get('pembagian-harga-kelompok-obat/generate-excel', 'HargaKelompokObatController@generate_excel');
        Route::get('pembagian-harga-kelompok-obat/cabang-obat', 'HargaKelompokObatController@branch_medicine');

        Route::get('pembagian-harga-kelompok-obat/dropdown', 'HargaKelompokObatController@dropdown');

        //laporan keuangan

        //harian
        Route::get('laporan-keuangan/harian', 'LaporanKeuanganHarianController@index');
        Route::get('laporan-keuangan/harian/download', 'LaporanKeuanganHarianController@download_excel');

        Route::get('laporan-keuangan/detail', 'LaporanKeuanganHarianController@detail');

        //mingguan
        Route::get('laporan-keuangan/mingguan', 'LaporanKeuanganMingguanController@index');
        Route::get('laporan-keuangan/mingguan/download', 'LaporanKeuanganMingguanController@download_excel');

        Route::get('laporan-keuangan/mingguan/detail', 'LaporanKeuanganMingguanController@detail');

        //bulanan
        Route::get('laporan-keuangan/bulanan', 'LaporanKeuanganBulananController@index');
        Route::get('laporan-keuangan/bulanan/download', 'LaporanKeuanganBulananController@download_excel');

        Route::get('laporan-keuangan/bulanan/detail', 'LaporanKeuanganBulananController@detail');

        //rekap
        Route::get('laporan-keuangan/rekap/table', 'RekapController@index');
        Route::get('laporan-keuangan/rekap/chart', 'RekapController@chart');

        Route::get('laporan-keuangan/rekap/listperiode', 'RekapController@listperiode');
        Route::get('laporan-keuangan/rekap/download', 'RekapController@export');
        Route::get('laporan-keuangan/rekap/patient-summary', 'RekapController@patientSummary');

        Route::get('laporan-keuangan/rekap-all', 'RekapKeseluruhanController@index');
        Route::get('laporan-keuangan/rekap-all-chart', 'RekapKeseluruhanController@chart');
        Route::get('laporan-keuangan/rekap-all/export', 'RekapKeseluruhanController@export');

        //dashboard
        Route::get('dashboard/barchart', 'DashboardController@BarChartPatient');
        Route::get('dashboard/barchart-inpatient', 'DashboardController@BarChartInPatient');
        Route::get('dashboard/tidak-pengabaran', 'DashboardController@PasienTidakPengabaran');

        //penggajian
        Route::get('penggajian/gaji-user', 'PenggajianController@sallary_user');

        Route::get('penggajian', 'PenggajianController@index');
        Route::post('penggajian', 'PenggajianController@create');
        Route::put('penggajian', 'PenggajianController@update');
        Route::delete('penggajian', 'PenggajianController@delete');

        Route::get('penggajian/generate', 'PenggajianController@generate');

        //pengeluaran
        Route::get('pengeluaran', 'PengeluaranController@index');
        Route::post('pengeluaran', 'PengeluaranController@create');
        Route::put('pengeluaran', 'PengeluaranController@update');
        Route::delete('pengeluaran', 'PengeluaranController@delete');

        //shift
        Route::get('shift', 'ShiftController@index');
        Route::get('shift/dropdown', 'ShiftController@dropdown');
        Route::post('shift', 'ShiftController@create');
        Route::put('shift', 'ShiftController@update');
        Route::put('shift/toggle-status', 'ShiftController@toggleStatus');
        Route::delete('shift', 'ShiftController@delete');

        //absensi
        Route::get('absensi/cek-hari-ini', 'AbsensiController@cekHariIni');
        Route::post('absensi/masuk', 'AbsensiController@masuk');
        Route::post('absensi/keluar', 'AbsensiController@keluar');
        Route::get('absensi/export', 'AbsensiController@export');
        Route::get('absensi', 'AbsensiController@index');

        //absensi radius exception
        Route::get('absensi-radius-exception', 'AbsensiRadiusExceptionController@index');
        Route::post('absensi-radius-exception', 'AbsensiRadiusExceptionController@create');
        Route::delete('absensi-radius-exception', 'AbsensiRadiusExceptionController@delete');

        //pengabaran
        Route::get('pengabaran/nomor-wa', 'PengabaranController@getNomorWa');
        Route::put('pengabaran/nomor-wa', 'PengabaranController@saveNomorWa');
        Route::get('pengabaran/template', 'PengabaranController@getTemplates');
        Route::put('pengabaran/template', 'PengabaranController@saveTemplate');
        Route::delete('pengabaran/template', 'PengabaranController@deleteTemplate');
    });
});

// Route::post('register', 'UserController@register');
// Route::post('login', 'UserController@login');
// Route::get('book', 'BookController@book');

// Route::get('bookall', 'BookController@bookAuth')->middleware('jwt.verify');
// Route::get('user', 'UserController@getAuthenticatedUser')->middleware('jwt.verify');
