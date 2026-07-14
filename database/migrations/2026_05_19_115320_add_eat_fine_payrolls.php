<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEatFinePayrolls extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::table('payrolls', function (Blueprint $table) {
      $table->decimal('eat', 18, 2)->nullable(0)->after('accomodation');
      $table->decimal('fine', 18, 2)->nullable(0)->after('eat');
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::table('payrolls', function (Blueprint $table) {
      $table->dropColumn('eat');
      $table->dropColumn('fine');
    });
  }
}
