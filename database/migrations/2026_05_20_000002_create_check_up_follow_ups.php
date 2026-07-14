<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCheckUpFollowUps extends Migration
{
    public function up()
    {
        Schema::create('check_up_follow_ups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('check_up_result_id')->index();
            $table->string('owner_phone', 20);
            $table->string('owner_name', 100);
            $table->string('pet_name', 100);
            $table->text('message');
            $table->date('scheduled_date')->index();
            $table->enum('status', ['pending', 'sent', 'failed', 'cancelled'])->default('pending')->index();
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('check_up_follow_ups');
    }
}
