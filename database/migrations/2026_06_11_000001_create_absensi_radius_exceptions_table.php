<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAbsensiRadiusExceptionsTable extends Migration
{
    public function up()
    {
        Schema::create('absensi_radius_exceptions', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->unique();
            $table->string('created_by', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('absensi_radius_exceptions');
    }
}
