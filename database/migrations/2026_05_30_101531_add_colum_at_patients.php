<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumAtPatients extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::table('patients', function (Blueprint $table) {
      $table->integer('pet_category_id')->nullable()->after('pet_category');
      $table->string('other_pet_category')->nullable()->after('pet_category_id');
    });

    $pets = [
      1 => 'Kucing',
      2 => 'Anjing',
      3 => 'Kelinci',
      4 => 'Sugar Glider',
      5 => 'Monyet',
    ];

    foreach ($pets as $id => $name) {
      DB::table('patients')
        ->where('pet_category', 'like', "%{$name}%")
        ->update(['pet_category_id' => $id]);
    }

    // Pet category yang tidak cocok dengan opsi 1-5 → masuk ke other_pet_category (id 6 = Lainnya)
    DB::table('patients')
      ->whereNull('pet_category_id')
      ->whereNotNull('pet_category')
      ->where('pet_category', '!=', '')
      ->update([
        'pet_category_id'    => 6,
        'other_pet_category' => DB::raw('pet_category'),
      ]);
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::table('patients', function (Blueprint $table) {
      $table->dropColumn('pet_category_id');
      $table->dropColumn('other_pet_category');
    });
  }
}
