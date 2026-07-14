<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPetAgeRegistrations extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::table('registrations', function (Blueprint $table) {
      $table->integer('pet_year_age')->nullable()->after('registrant');
      $table->integer('pet_month_age')->nullable()->after('pet_year_age');
      $table->integer('pet_day_age')->nullable()->after('pet_month_age');
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::table('registrations', function (Blueprint $table) {
      $table->dropColumn(['pet_year_age', 'pet_month_age', 'pet_day_age']);
    });
  }
}
