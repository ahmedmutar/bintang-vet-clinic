<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateNotificationTemplatesTable extends Migration
{
    public function up()
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('complaint_id')->unique();
            $table->text('message')->nullable();
            $table->timestamps();
        });

        // Seed template default untuk setiap keluhan
        $now = now();
        $defaultMessage = "Halo Kak, Terima kasih sudah menjadi pelanggan setia kami ya..\n\nBila ada pertanyaan apapun terkait anabul, jangan ragu untuk konsultasi ke whatsapp kami. Dokter kami dengan senang hati menjawab dan mencarikan solusi terbaik.\n\nSemoga kaka sekeluarga dan semua anabul, selalu diberikan kesehatan.";

        $templates = [
            ['complaint_id' => 1,  'message' => $defaultMessage], // Periksa
            ['complaint_id' => 2,  'message' => $defaultMessage], // Kontrol
            ['complaint_id' => 3,  'message' => $defaultMessage], // Operasi
            ['complaint_id' => 4,  'message' => $defaultMessage], // Vaksin
            ['complaint_id' => 5,  'message' => $defaultMessage], // Tambahan rawat inap
            ['complaint_id' => 6,  'message' => $defaultMessage], // Tambahan biaya
            ['complaint_id' => 7,  'message' => $defaultMessage], // Grooming
            ['complaint_id' => 8,  'message' => $defaultMessage], // Pet hotel
            ['complaint_id' => 9,  'message' => $defaultMessage], // Home visit
            ['complaint_id' => 10, 'message' => $defaultMessage], // Steril
            ['complaint_id' => 11, 'message' => $defaultMessage], // Lainnya
        ];

        foreach ($templates as $t) {
            DB::table('notification_templates')->insert([
                'complaint_id' => $t['complaint_id'],
                'message'      => $t['message'],
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }
    }

    public function down()
    {
        Schema::dropIfExists('notification_templates');
    }
}
