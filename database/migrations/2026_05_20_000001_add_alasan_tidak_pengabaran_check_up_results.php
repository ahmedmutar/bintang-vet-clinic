<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAlasanTidakPengabaranCheckUpResults extends Migration
{
    public function up()
    {
        Schema::table('check_up_results', function (Blueprint $table) {
            $table->text('alasan_tidak_pengabaran')->nullable()->after('status_finish');
        });
    }

    public function down()
    {
        Schema::table('check_up_results', function (Blueprint $table) {
            $table->dropColumn('alasan_tidak_pengabaran');
        });
    }
}
