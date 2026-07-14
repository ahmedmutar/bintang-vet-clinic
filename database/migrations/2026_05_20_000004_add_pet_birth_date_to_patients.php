<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddPetBirthDateToPatients extends Migration
{
    public function up()
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->date('pet_birth_date')->nullable()->after('pet_day_age');
        });

        // Backfill perkiraan tanggal lahir dari data usia yang sudah ada.
        // COALESCE dipakai agar pet_day_age NULL (data lama) dianggap 0.
        DB::statement("
            UPDATE patients
            SET pet_birth_date = DATE_SUB(CURDATE(),
                INTERVAL (
                    COALESCE(pet_year_age, 0)  * 365 +
                    COALESCE(pet_month_age, 0) * 30  +
                    COALESCE(pet_day_age, 0)
                ) DAY)
            WHERE pet_birth_date IS NULL
              AND isDeleted = 0
        ");
    }

    public function down()
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn('pet_birth_date');
        });
    }
}
