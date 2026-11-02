<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCicilanAndPaymentsToMitraVet extends Migration
{
    public function up()
    {
        // 1. Tambah kolom cicilan & piutang pada mitra_vet_orders
        Schema::table('mitra_vet_orders', function (Blueprint $table) {
            $table->string('tipe_pembayaran', 30)->default('lunas')->after('grand_total'); // lunas / cicilan
            $table->string('status_pembayaran', 30)->default('lunas')->after('tipe_pembayaran'); // lunas / cicilan / jatuh_tempo
            $table->integer('jumlah_termin')->default(1)->after('status_pembayaran'); // berapa kali cicil (misal 3x)
            $table->decimal('uang_muka', 15, 2)->default(0)->after('jumlah_termin'); // DP
            $table->decimal('total_terbayar', 15, 2)->default(0)->after('uang_muka');
            $table->decimal('sisa_tagihan', 15, 2)->default(0)->after('total_terbayar');
            $table->date('tanggal_jatuh_tempo')->nullable()->after('sisa_tagihan');

            $table->index('status_pembayaran');
            $table->index('tanggal_jatuh_tempo');
        });

        // 2. Buat tabel riwayat pembayaran cicilan
        Schema::create('mitra_vet_order_payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id');
            $table->string('nomor_pembayaran', 50)->unique();
            $table->integer('cicilan_ke')->default(1); // 0 = DP, 1 = Cicilan 1, dst
            $table->decimal('jumlah_bayar', 15, 2);
            $table->date('tanggal_bayar');
            $table->string('metode_pembayaran', 50)->default('transfer'); // transfer / tunai
            $table->text('catatan')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('tanggal_bayar');
        });
    }

    public function down()
    {
        Schema::dropIfExists('mitra_vet_order_payments');

        Schema::table('mitra_vet_orders', function (Blueprint $table) {
            $table->dropIndex(['status_pembayaran']);
            $table->dropIndex(['tanggal_jatuh_tempo']);
            $table->dropColumn([
                'tipe_pembayaran',
                'status_pembayaran',
                'jumlah_termin',
                'uang_muka',
                'total_terbayar',
                'sisa_tagihan',
                'tanggal_jatuh_tempo',
            ]);
        });
    }
}
