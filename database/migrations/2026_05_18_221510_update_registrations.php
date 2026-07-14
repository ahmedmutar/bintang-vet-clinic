<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateRegistrations extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::table('registrations', function (Blueprint $table) {
      $table->integer('complaint_id')->nullable()->after('complaint');
      $table->string('other_complaint')->nullable()->after('complaint_id');
    });

    $complaints = [
      1 => 'Periksa',
      2 => 'Kontrol',
      3 => 'Operasi',
      4 => 'Vaksin',
      5 => 'Tambahan rawat inap',
      6 => 'Tambahan biaya',
      7 => 'Grooming',
      8 => 'Pet hotel',
      9 => 'Home visit',
      10 => 'Steril'
    ];

    foreach ($complaints as $id => $name) {
      DB::table('registrations')
        ->where('complaint', 'like', "%{$name}%")
        ->update(['complaint_id' => $id]);
    }

    // Complaint yang tidak cocok dengan opsi 1-11 → masuk ke other_complaint (id 11 = Lainnya)
    DB::table('registrations')
      ->whereNull('complaint_id')
      ->whereNotNull('complaint')
      ->where('complaint', '!=', '')
      ->update([
        'complaint_id'    => 11,
        'other_complaint' => DB::raw('complaint'),
      ]);
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::table('registrations', function (Blueprint $table) {
      $table->dropColumn('complaint_id');
      $table->dropColumn('other_complaint');
    });
  }
}
