<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePetCategoriesTable extends Migration
{
    public function up()
    {
        Schema::create('pet_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        DB::table('pet_categories')->insert([
            ['id' => 1, 'name' => 'Kucing',       'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Anjing',       'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Kelinci',      'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'Sugar Glider', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'Monyet',       'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'Lainnya',      'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('pet_categories');
    }
}
