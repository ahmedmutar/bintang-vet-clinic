<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddFollowupDaysToNotificationTemplates extends Migration
{
    public function up()
    {
        Schema::table('notification_templates', function (Blueprint $table) {
            $table->unsignedSmallInteger('followup_days')->default(3)->after('message');
        });

        // Hapus setting global followup_days yang sebelumnya ada di notification_settings
        DB::table('notification_settings')->where('key', 'followup_days')->delete();
    }

    public function down()
    {
        Schema::table('notification_templates', function (Blueprint $table) {
            $table->dropColumn('followup_days');
        });
    }
}
