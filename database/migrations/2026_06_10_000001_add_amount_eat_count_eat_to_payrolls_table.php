<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAmountEatCountEatToPayrollsTable extends Migration
{
    public function up()
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->decimal('amount_eat', 18, 2)->default(0)->after('eat');
            $table->integer('count_eat')->default(0)->after('amount_eat');
        });
    }

    public function down()
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn(['amount_eat', 'count_eat']);
        });
    }
}
