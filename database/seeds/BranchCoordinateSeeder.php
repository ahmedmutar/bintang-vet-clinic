<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BranchCoordinateSeeder extends Seeder
{
    public function run()
    {
        $coordinates = [
            // id => [latitude, longitude]  (perkiraan berdasarkan alamat — sesuaikan jika perlu)
            2  => [-6.18020, 106.79410], // Tanjung Duren — Jl. Tanjung Duren Barat 1, Jakarta Barat
            3  => [-6.22640, 106.65310], // Alam Sutera — Jl. Jalur Sutera Ruko Spectra, Tangerang
            4  => [-6.29420, 106.83540], // Kebagusan — Jl. Raya Kebagusan 48, Jakarta Selatan
            5  => [-6.34320, 106.81900], // Kahfi — Jl. M. Kahfi 1 no.27a, Jagakarsa
            6  => [-6.16180, 106.73220], // Westpet — Rukan Puri Niaga, Puri Kembangan
            7  => [-6.26310, 106.75810], // Bintang Vet Bintaro Veteran — Jl. RC. Veteran Raya 28, Pesanggrahan
            8  => [-6.25360, 106.79320], // Bintang Vet Radio Dalam — Jl. Radio Dalam Raya 47D, Kebayoran Baru
            9  => [-6.27780, 106.72320], // Hello Vet Pondok Aren — Jl. Jombang Raya 69, Pondok Aren
            10 => [-6.38560, 106.83110], // Aiko Vet Sukmajaya — Jl. KH. M. Yusuf 23, Sukmajaya Depok
        ];

        foreach ($coordinates as $id => [$lat, $lng]) {
            DB::table('branches')
                ->where('id', $id)
                ->update([
                    'latitude'   => $lat,
                    'longitude'  => $lng,
                    'updated_at' => now(),
                ]);
        }

        $this->command->info('BranchCoordinateSeeder: koordinat ' . count($coordinates) . ' cabang berhasil diperbarui.');
    }
}
