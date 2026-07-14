<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusPengabaranToCheckUpResults extends Migration
{
    public function up()
    {
        Schema::table('check_up_results', function (Blueprint $table) {
            $table->tinyInteger('status_pengabaran')->nullable()->after('alasan_tidak_pengabaran');
        });
    }

    public function down()
    {
        Schema::table('check_up_results', function (Blueprint $table) {
            $table->dropColumn('status_pengabaran');
        });
    }
}
