<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShiftSeeder extends Seeder
{
    public function run()
    {
        $branchIds = DB::table('branches')->pluck('id')->toArray();

        $templates = [
            // Shift umum semua role
            [
                'nama_shift'      => 'Shift Pagi',
                'jam_masuk'       => '08:00:00',
                'jam_keluar'      => '16:00:00',
                'toleransi_menit' => 15,
                'for_role'        => null,
            ],
            [
                'nama_shift'      => 'Shift Siang',
                'jam_masuk'       => '12:00:00',
                'jam_keluar'      => '20:00:00',
                'toleransi_menit' => 15,
                'for_role'        => null,
            ],
            // Shift khusus dokter
            [
                'nama_shift'      => 'Shift Dokter Pagi',
                'jam_masuk'       => '08:00:00',
                'jam_keluar'      => '14:00:00',
                'toleransi_menit' => 15,
                'for_role'        => 'dokter',
            ],
            [
                'nama_shift'      => 'Shift Dokter Siang',
                'jam_masuk'       => '14:00:00',
                'jam_keluar'      => '20:00:00',
                'toleransi_menit' => 15,
                'for_role'        => 'dokter',
            ],
            // Shift khusus resepsionis
            [
                'nama_shift'      => 'Shift Resepsionis Pagi',
                'jam_masuk'       => '08:00:00',
                'jam_keluar'      => '15:00:00',
                'toleransi_menit' => 15,
                'for_role'        => 'resepsionis',
            ],
            [
                'nama_shift'      => 'Shift Resepsionis Siang',
                'jam_masuk'       => '13:00:00',
                'jam_keluar'      => '20:00:00',
                'toleransi_menit' => 15,
                'for_role'        => 'resepsionis',
            ],
            // Shift khusus paramedis
            [
                'nama_shift'      => 'Shift Paramedis Pagi',
                'jam_masuk'       => '07:00:00',
                'jam_keluar'      => '15:00:00',
                'toleransi_menit' => 15,
                'for_role'        => 'paramedis',
            ],
            [
                'nama_shift'      => 'Shift Paramedis Siang',
                'jam_masuk'       => '13:00:00',
                'jam_keluar'      => '21:00:00',
                'toleransi_menit' => 15,
                'for_role'        => 'paramedis',
            ],
        ];

        $now  = now();
        $rows = [];

        foreach ($branchIds as $branchId) {
            foreach ($templates as $tpl) {
                $rows[] = array_merge($tpl, [
                    'branch_id'  => $branchId,
                    'status'     => 1,
                    'created_by' => 'Seeder',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        DB::table('shifts')->insert($rows);

        $this->command->info('ShiftSeeder: ' . count($rows) . ' shift berhasil dimasukkan untuk ' . count($branchIds) . ' cabang.');
    }
}
