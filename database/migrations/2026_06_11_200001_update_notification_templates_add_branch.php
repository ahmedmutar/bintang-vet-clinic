<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateNotificationTemplatesAddBranch extends Migration
{
    public function up()
    {
        Schema::table('notification_templates', function (Blueprint $table) {
            // Hapus unique lama pada complaint_id saja
            $table->dropUnique(['complaint_id']);

            // Tambah branch_id: 0 = Global/Default, >0 = cabang spesifik
            $table->unsignedInteger('branch_id')->default(0)->after('id');

            // Unique per kombinasi cabang + keluhan
            $table->unique(['branch_id', 'complaint_id']);
        });

        // Seed followup_days ke notification_settings
        $now = now();
        DB::table('notification_settings')->insertOrIgnore([
            'key'        => 'followup_days',
            'value'      => '3',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down()
    {
        Schema::table('notification_templates', function (Blueprint $table) {
            $table->dropUnique(['branch_id', 'complaint_id']);
            $table->dropColumn('branch_id');
            $table->unique('complaint_id');
        });

        DB::table('notification_settings')->where('key', 'followup_days')->delete();
    }
}
