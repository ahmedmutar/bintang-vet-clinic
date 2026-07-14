<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateComplaint extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            $table->boolean('isDeleted')->nullable()->default(false);
            $table->integer('user_id');
            $table->integer('user_update_id')->nullable();
            $table->string('deleted_by')->nullable();
            $table->timestamp('deleted_at',0)->nullable();
            $table->timestamps();
        });

        $complaints = [
            ['id' => 1,  'name' => 'Periksa'],
            ['id' => 2,  'name' => 'Kontrol'],
            ['id' => 3,  'name' => 'Operasi'],
            ['id' => 4,  'name' => 'Vaksin'],
            ['id' => 5,  'name' => 'Tambahan rawat inap'],
            ['id' => 6,  'name' => 'Tambahan biaya'],
            ['id' => 7,  'name' => 'Grooming'],
            ['id' => 8,  'name' => 'Pet hotel'],
            ['id' => 9,  'name' => 'Home visit'],
            ['id' => 10, 'name' => 'Steril'],
            ['id' => 11, 'name' => 'Lainnya'],
        ];

        $now = now();
        $rows = array_map(fn($c) => [
            'id'         => $c['id'],
            'name'       => $c['name'],
            'isDeleted'  => false,
            'user_id'    => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ], $complaints);

        DB::table('complaints')->insert($rows);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('complaints');
    }
}
