<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdatePetAge extends Command
{
    protected $signature   = 'pet:update-age';
    protected $description = 'Perbarui tahun, bulan, dan hari usia hewan berdasarkan pet_birth_date';

    public function handle(): int
    {
        DB::statement("
            UPDATE patients
            SET
                pet_year_age  = CASE
                                    WHEN pet_day_age + 1 >= 30 AND pet_month_age + 1 >= 12
                                        THEN pet_year_age + 1
                                    ELSE pet_year_age
                                END,

                pet_month_age = CASE
                                    WHEN pet_day_age + 1 >= 30 AND pet_month_age + 1 >= 12
                                        THEN 0
                                    WHEN pet_day_age + 1 >= 30
                                        THEN pet_month_age + 1
                                    ELSE pet_month_age
                                END,

                pet_day_age   = CASE
                                    WHEN pet_day_age + 1 >= 30
                                        THEN 0
                                    ELSE pet_day_age + 1
                                END

            WHERE isDeleted = 0
        ");

        $count = DB::select('SELECT ROW_COUNT() as n')[0]->n ?? 0;

        $this->info("Usia hewan diperbarui: {$count} pasien.");
        Log::info("UpdatePetAge: {$count} pasien diperbarui pada " . now()->toDateTimeString());

        return self::SUCCESS;
    }
}
