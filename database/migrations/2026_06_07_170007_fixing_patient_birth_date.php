<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixingPatientBirthDate extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    DB::statement("
            UPDATE patients
            SET pet_birth_date = NULL
        ");

    DB::statement("
            update patients
            set pet_birth_date =
            DATE_SUB(created_at,
                INTERVAL (
                    COALESCE(pet_year_age, 0)  * 365 +
                    COALESCE(pet_month_age, 0) * 30  +
                    COALESCE(pet_day_age, 0)
                ) DAY)
        ");

    DB::statement("
            UPDATE registrations r
JOIN patients p ON r.patient_id = p.id
SET
    r.pet_year_age = TIMESTAMPDIFF(YEAR, p.pet_birth_date, DATE(r.created_at)),
    r.pet_month_age = TIMESTAMPDIFF(MONTH, p.pet_birth_date, DATE(r.created_at)) % 12,
    r.pet_day_age = DATEDIFF(
        DATE(r.created_at),
        DATE_ADD(p.pet_birth_date,
            INTERVAL TIMESTAMPDIFF(MONTH, p.pet_birth_date, DATE(r.created_at)) MONTH)
    )
WHERE p.pet_birth_date IS NOT NULL
  AND r.isDeleted = 0
  AND p.isDeleted = 0;
        ");
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    //
  }
}
