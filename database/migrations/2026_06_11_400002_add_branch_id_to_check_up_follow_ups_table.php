<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBranchIdToCheckUpFollowUpsTable extends Migration
{
    public function up()
    {
        Schema::table('check_up_follow_ups', function (Blueprint $table) {
            $table->unsignedInteger('branch_id')->default(0)->after('check_up_result_id');
        });
    }

    public function down()
    {
        Schema::table('check_up_follow_ups', function (Blueprint $table) {
            $table->dropColumn('branch_id');
        });
    }
}
