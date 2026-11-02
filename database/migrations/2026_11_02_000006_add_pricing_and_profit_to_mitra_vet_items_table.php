<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddPricingAndProfitToMitraVetItemsTable extends Migration
{
    public function up()
    {
        Schema::table('mitra_vet_items', function (Blueprint $table) {
            $table->decimal('harga_modal', 15, 2)->default(0)->after('satuan');
            $table->decimal('harga_jual', 15, 2)->default(0)->after('harga_modal');
            $table->decimal('profit', 15, 2)->default(0)->after('harga_jual');
        });

        // Sync existing records if any
        DB::statement("UPDATE mitra_vet_items SET harga_jual = harga_satuan, profit = GREATEST(harga_satuan - harga_modal, 0)");
    }

    public function down()
    {
        Schema::table('mitra_vet_items', function (Blueprint $table) {
            $table->dropColumn(['harga_modal', 'harga_jual', 'profit']);
        });
    }
}
