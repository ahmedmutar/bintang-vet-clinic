<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MitraVetDummySeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 1. Bersihkan / drop data lama di seluruh tabel Mitra Vet
        DB::table('mitra_vet_order_payments')->truncate();
        DB::table('mitra_vet_order_items')->truncate();
        DB::table('mitra_vet_orders')->truncate();
        DB::table('mitra_vet_stock_histories')->truncate();
        DB::table('mitra_vet_items')->truncate();
        DB::table('mitra_vet_customers')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Ambil user ID admin pertama untuk created_by
        $adminUser = DB::table('users')->first();
        $adminId = $adminUser ? $adminUser->id : 1;

        $now = Carbon::now();

        // 2. Data Pelanggan / Mitra Dummy
        $customersData = [
            [
                'nama'       => 'Drh. Ahmad Fauzi (Klinik Satwa Sehat)',
                'no_hp'      => '081234567801',
                'alamat'     => 'Jl. Raya Darmo No. 45, Wonokromo, Surabaya',
                'created_by' => $adminId,
                'created_at' => $now->copy()->subMonths(3),
                'updated_at' => $now->copy()->subMonths(3),
            ],
            [
                'nama'       => 'Drh. Siti Rahmawati (Paw Care Clinic)',
                'no_hp'      => '081234567802',
                'alamat'     => 'Jl. Jemursari No. 12, Wonocolo, Surabaya',
                'created_by' => $adminId,
                'created_at' => $now->copy()->subMonths(2),
                'updated_at' => $now->copy()->subMonths(2),
            ],
            [
                'nama'       => 'Drh. Budi Santoso (Mitra Vet Mandiri)',
                'no_hp'      => '081234567803',
                'alamat'     => 'Jl. HR Muhammad No. 88, Sukomanunggal, Surabaya',
                'created_by' => $adminId,
                'created_at' => $now->copy()->subMonths(2),
                'updated_at' => $now->copy()->subMonths(2),
            ],
            [
                'nama'       => 'Klinik Hewan Anugerah Vet',
                'no_hp'      => '081234567804',
                'alamat'     => 'Jl. Diponegoro No. 102, Sidoarjo',
                'created_by' => $adminId,
                'created_at' => $now->copy()->subMonth(),
                'updated_at' => $now->copy()->subMonth(),
            ],
            [
                'nama'       => 'Pet Shop & Vet Sahabat Satwa',
                'no_hp'      => '081234567805',
                'alamat'     => 'Jl. Manyar Kertoarjo No. 23, Gubeng, Surabaya',
                'created_by' => $adminId,
                'created_at' => $now->copy()->subMonth(),
                'updated_at' => $now->copy()->subMonth(),
            ],
            [
                'nama'       => 'Drh. Hendra Wijaya (Pet Health Center)',
                'no_hp'      => '081234567806',
                'alamat'     => 'Jl. Rungkut Asri No. 15, Rungkut, Surabaya',
                'created_by' => $adminId,
                'created_at' => $now->copy()->subDays(20),
                'updated_at' => $now->copy()->subDays(20),
            ],
            [
                'nama'       => 'Klinik Satwa Lestari Gresik',
                'no_hp'      => '081234567807',
                'alamat'     => 'Jl. Veteran No. 70, Kebomas, Gresik',
                'created_by' => $adminId,
                'created_at' => $now->copy()->subDays(15),
                'updated_at' => $now->copy()->subDays(15),
            ],
            [
                'nama'       => 'Drh. Maya Kusuma (Kucing Anjing Sehat)',
                'no_hp'      => '081234567808',
                'alamat'     => 'Jl. Kertajaya Indah No. 5, Mulyorejo, Surabaya',
                'created_by' => $adminId,
                'created_at' => $now->copy()->subDays(10),
                'updated_at' => $now->copy()->subDays(10),
            ],
        ];

        $customerIds = [];
        foreach ($customersData as $c) {
            $customerIds[] = DB::table('mitra_vet_customers')->insertGetId($c);
        }

        // 3. Data Produk / Barang Mitra Vet Dummy
        $itemsMaster = [
            ['nama' => 'Amoxicillin Drop 15ml', 'satuan' => 'Botol', 'modal' => 25000, 'jual' => 40000, 'stok' => 85],
            ['nama' => 'Enrofloxacin Inj 100ml', 'satuan' => 'Vial', 'modal' => 95000, 'jual' => 145000, 'stok' => 50],
            ['nama' => 'Toltrazuril Oral 50ml', 'satuan' => 'Botol', 'modal' => 60000, 'jual' => 90000, 'stok' => 40],
            ['nama' => 'Dexamethasone Inj 50ml', 'satuan' => 'Vial', 'modal' => 35000, 'jual' => 55000, 'stok' => 100],
            ['nama' => 'Ivermectin Inj 10ml', 'satuan' => 'Vial', 'modal' => 45000, 'jual' => 70000, 'stok' => 60],
            ['nama' => 'Multi-Vitamin Syrup 100ml', 'satuan' => 'Botol', 'modal' => 30000, 'jual' => 50000, 'stok' => 120],
            ['nama' => 'Salep Kulit Scabies & Jamur 20g', 'satuan' => 'Tube', 'modal' => 18000, 'jual' => 32000, 'stok' => 95],
            ['nama' => 'Obat Cacing Spektrum Luas', 'satuan' => 'Box', 'modal' => 80000, 'jual' => 125000, 'stok' => 45],
            ['nama' => 'Shampo Anti Kutu & Jamur 250ml', 'satuan' => 'Botol', 'modal' => 35000, 'jual' => 60000, 'stok' => 70],
            ['nama' => 'Benang Operasi Catgut 3/0', 'satuan' => 'Box', 'modal' => 110000, 'jual' => 170000, 'stok' => 35],
            ['nama' => 'Spuit Jarum Suntik 3ml (Isi 100)', 'satuan' => 'Box', 'modal' => 65000, 'jual' => 95000, 'stok' => 60],
            ['nama' => 'Infus RL 500ml Hewan', 'satuan' => 'Kolf', 'modal' => 15000, 'jual' => 25000, 'stok' => 160],
            ['nama' => 'Infus Set Makro / Mikro', 'satuan' => 'Pcs', 'modal' => 12000, 'jual' => 20000, 'stok' => 130],
            ['nama' => 'Probiotik Pencernaan 30g', 'satuan' => 'Sachet', 'modal' => 22000, 'jual' => 38000, 'stok' => 90],
            ['nama' => 'Suplemen Kalsium Tulang 60 Tab', 'satuan' => 'Botol', 'modal' => 50000, 'jual' => 85000, 'stok' => 65],
        ];

        $itemIds = [];
        foreach ($itemsMaster as $it) {
            $modal  = $it['modal'];
            $jual   = $it['jual'];
            $profit = $jual - $modal;
            $stok   = $it['stok'];

            $itemId = DB::table('mitra_vet_items')->insertGetId([
                'nama_barang'  => $it['nama'],
                'satuan'       => $it['satuan'],
                'harga_modal'  => $modal,
                'harga_jual'   => $jual,
                'profit'       => $profit,
                'harga_satuan' => $jual,
                'stok'         => $stok,
                'created_by'   => $adminId,
                'created_at'   => $now->copy()->subMonths(2),
                'updated_at'   => $now->copy()->subMonths(2),
            ]);

            $itemIds[] = [
                'id'           => $itemId,
                'nama'         => $it['nama'],
                'satuan'       => $it['satuan'],
                'harga_modal'  => $modal,
                'harga_jual'   => $jual,
                'profit'       => $profit,
                'harga_satuan' => $jual,
            ];

            // Catat stok awal di history
            DB::table('mitra_vet_stock_histories')->insert([
                'item_id'    => $itemId,
                'quantity'   => $stok + 20, // anggap sebelumnya ada sedikit pengurangan
                'type'       => 'masuk',
                'keterangan' => 'Stok awal distributor Mitra Vet',
                'created_by' => $adminId,
                'created_at' => $now->copy()->subMonths(2),
                'updated_at' => $now->copy()->subMonths(2),
            ]);
        }

        // 4. Data Orderan Dummy (Campuran Lunas, Cicilan Aktif, Cicilan Lunas, Jatuh Tempo)
        $ordersBlueprint = [
            // Order 1: Bulan lalu (Oktober) - Lunas
            [
                'cust_idx'  => 0,
                'date'      => $now->copy()->subDays(35),
                'tipe'      => 'lunas',
                'ongkir'    => 20000,
                'items'     => [[0, 5], [1, 2], [3, 4]], // [item_index, qty]
                'catatan'   => 'Kirim via ekspedisi reguler',
            ],
            // Order 2: Bulan lalu (Oktober) - Cicilan Lunas (3x bayar)
            [
                'cust_idx'  => 1,
                'date'      => $now->copy()->subDays(30),
                'tipe'      => 'cicilan',
                'termin'    => 3,
                'tempo'     => $now->copy()->subDays(5)->toDateString(),
                'ongkir'    => 25000,
                'items'     => [[9, 2], [10, 4], [11, 10]],
                'dp_ratio'  => 0.4,
                'pay_all'   => true, // sudah dilunasi
                'catatan'   => 'Pembayaran cicilan tempo 1 bulan lunas',
            ],
            // Order 3: Bulan ini (November) - Lunas Langsung
            [
                'cust_idx'  => 2,
                'date'      => $now->copy()->subDays(6),
                'tipe'      => 'lunas',
                'ongkir'    => 15000,
                'items'     => [[5, 6], [6, 10], [13, 8]],
                'catatan'   => 'Ambil langsung di klinik',
            ],
            // Order 4: Bulan ini (November) - Cicilan Aktif (Baru DP + Cicilan 1)
            [
                'cust_idx'  => 3,
                'date'      => $now->copy()->subDays(5),
                'tipe'      => 'cicilan',
                'termin'    => 3,
                'tempo'     => $now->copy()->addDays(20)->toDateString(), // belum jatuh tempo
                'ongkir'    => 30000,
                'items'     => [[1, 3], [7, 3], [9, 2]],
                'dp_ratio'  => 0.3,
                'installment_count' => 1, // sudah cicil 1x
                'catatan'   => 'Paket bedah & antibiotik untuk klinik Sidoarjo',
            ],
            // Order 5: Bulan ini (November) - Cicilan Jatuh Tempo (Lewat batas)
            [
                'cust_idx'  => 4,
                'date'      => $now->copy()->subDays(18),
                'tipe'      => 'cicilan',
                'termin'    => 2,
                'tempo'     => $now->copy()->subDays(2)->toDateString(), // LEWAT TEMPO!
                'ongkir'    => 20000,
                'items'     => [[8, 5], [14, 4], [0, 8]],
                'dp_ratio'  => 0.25,
                'installment_count' => 0, // baru DP saja
                'catatan'   => 'Konfirmasi jatuh tempo segera ditagih',
            ],
            // Order 6: Bulan ini (November) - Cicilan Aktif
            [
                'cust_idx'  => 5,
                'date'      => $now->copy()->subDays(3),
                'tipe'      => 'cicilan',
                'termin'    => 4,
                'tempo'     => $now->copy()->addDays(25)->toDateString(),
                'ongkir'    => 15000,
                'items'     => [[3, 6], [4, 4], [2, 3]],
                'dp_ratio'  => 0.25,
                'installment_count' => 0,
                'catatan'   => 'Pengiriman Rungkut Surabaya',
            ],
            // Order 7: Bulan ini (November) - Lunas Langsung
            [
                'cust_idx'  => 6,
                'date'      => $now->copy()->subDays(2),
                'tipe'      => 'lunas',
                'ongkir'    => 25000,
                'items'     => [[11, 15], [12, 10], [5, 4]],
                'catatan'   => 'Restock cairan infus klinik Gresik',
            ],
            // Order 8: Hari ini (November) - Cicilan Baru
            [
                'cust_idx'  => 7,
                'date'      => $now->copy()->subHours(4),
                'tipe'      => 'cicilan',
                'termin'    => 3,
                'tempo'     => $now->copy()->addMonth()->toDateString(),
                'ongkir'    => 15000,
                'items'     => [[6, 8], [8, 6], [13, 5]],
                'dp_ratio'  => 0.35,
                'installment_count' => 0,
                'catatan'   => 'Order suplemen & shampo perawatan',
            ],
        ];

        $orderSeq = 1;
        foreach ($ordersBlueprint as $ob) {
            $cust = $customersData[$ob['cust_idx']];
            $custId = $customerIds[$ob['cust_idx']];
            $orderDate = $ob['date'];

            $dateCode = $orderDate->format('Ymd');
            $nomorOrder = 'MV-' . $dateCode . '-' . str_pad($orderSeq++, 4, '0', STR_PAD_LEFT);

            // Hitung total item
            $totalHarga = 0;
            $itemsDetail = [];
            foreach ($ob['items'] as $itemRef) {
                $itemObj = $itemIds[$itemRef[0]];
                $qty = $itemRef[1];
                $subtotal = $itemObj['harga_jual'] * $qty;
                $totalHarga += $subtotal;
                $itemsDetail[] = [
                    'item'     => $itemObj,
                    'qty'      => $qty,
                    'subtotal' => $subtotal,
                ];
            }

            $ongkir = $ob['ongkir'];
            $grandTotal = $totalHarga + $ongkir;

            $tipe = $ob['tipe'];
            $termin = $ob['termin'] ?? 1;
            $tempo = $ob['tempo'] ?? null;

            if ($tipe === 'lunas') {
                $statusBayar = 'lunas';
                $dp = $grandTotal;
                $totalTerbayar = $grandTotal;
                $sisaTagihan = 0;
            } else {
                $dpRatio = $ob['dp_ratio'] ?? 0.3;
                $dp = round($grandTotal * $dpRatio, -3); // bulatkan ke ribuan
                $totalTerbayar = $dp;
                $sisaTagihan = $grandTotal - $dp;
                $statusBayar = $sisaTagihan <= 0 ? 'lunas' : 'cicilan';
            }

            $orderId = DB::table('mitra_vet_orders')->insertGetId([
                'nomor_order'         => $nomorOrder,
                'customer_id'         => $custId,
                'nama_pembeli'        => $cust['nama'],
                'no_hp'               => $cust['no_hp'],
                'alamat'              => $cust['alamat'],
                'biaya_ongkir'        => $ongkir,
                'total_harga'         => $totalHarga,
                'grand_total'         => $grandTotal,
                'tipe_pembayaran'     => $tipe,
                'status_pembayaran'   => $statusBayar,
                'jumlah_termin'       => $termin,
                'uang_muka'           => $dp,
                'total_terbayar'      => $totalTerbayar,
                'sisa_tagihan'        => $sisaTagihan,
                'tanggal_jatuh_tempo' => $tempo,
                'catatan'             => $ob['catatan'] ?? null,
                'created_by'          => $adminId,
                'created_at'          => $orderDate,
                'updated_at'          => $orderDate,
            ]);

            // Insert order items & kurangi stok
            foreach ($itemsDetail as $idtl) {
                DB::table('mitra_vet_order_items')->insert([
                    'order_id'     => $orderId,
                    'item_id'      => $idtl['item']['id'],
                    'quantity'     => $idtl['qty'],
                    'harga_satuan' => $idtl['item']['harga_jual'],
                    'subtotal'     => $idtl['subtotal'],
                    'created_at'   => $orderDate,
                    'updated_at'   => $orderDate,
                ]);

                // History stok keluar
                DB::table('mitra_vet_stock_histories')->insert([
                    'item_id'    => $idtl['item']['id'],
                    'quantity'   => $idtl['qty'],
                    'type'       => 'keluar',
                    'keterangan' => "Order {$nomorOrder} - {$cust['nama']}",
                    'created_by' => $adminId,
                    'created_at' => $orderDate,
                    'updated_at' => $orderDate,
                ]);
            }

            // Catat pembayaran DP / Pembayaran Awal
            if ($dp > 0) {
                DB::table('mitra_vet_order_payments')->insert([
                    'order_id'          => $orderId,
                    'nomor_pembayaran'  => 'DP-' . $nomorOrder,
                    'cicilan_ke'        => 0, // DP
                    'jumlah_bayar'      => $dp,
                    'tanggal_bayar'     => $orderDate->toDateString(),
                    'metode_pembayaran' => 'transfer',
                    'catatan'           => $tipe === 'lunas' ? 'Pembayaran lunas order' : 'Uang muka (DP) awal',
                    'created_by'        => $adminId,
                    'created_at'        => $orderDate,
                    'updated_at'        => $orderDate,
                ]);
            }

            // Jika ada cicilan lanjutan
            if (!empty($ob['pay_all'])) {
                // Kasus cicilan dilunasi penuh
                $angsuran = round($sisaTagihan / 2, -3);
                $angsuran1 = $angsuran;
                $angsuran2 = $sisaTagihan - $angsuran1;

                DB::table('mitra_vet_order_payments')->insert([
                    'order_id'          => $orderId,
                    'nomor_pembayaran'  => 'PAY-' . $orderDate->copy()->addDays(10)->format('Ymd') . '-' . str_pad($orderId, 4, '0', STR_PAD_LEFT) . '-1',
                    'cicilan_ke'        => 1,
                    'jumlah_bayar'      => $angsuran1,
                    'tanggal_bayar'     => $orderDate->copy()->addDays(10)->toDateString(),
                    'metode_pembayaran' => 'transfer',
                    'catatan'           => 'Pembayaran angsuran cicilan 1',
                    'created_by'        => $adminId,
                    'created_at'        => $orderDate->copy()->addDays(10),
                    'updated_at'        => $orderDate->copy()->addDays(10),
                ]);

                DB::table('mitra_vet_order_payments')->insert([
                    'order_id'          => $orderId,
                    'nomor_pembayaran'  => 'PAY-' . $orderDate->copy()->addDays(20)->format('Ymd') . '-' . str_pad($orderId, 4, '0', STR_PAD_LEFT) . '-2',
                    'cicilan_ke'        => 2,
                    'jumlah_bayar'      => $angsuran2,
                    'tanggal_bayar'     => $orderDate->copy()->addDays(20)->toDateString(),
                    'metode_pembayaran' => 'transfer',
                    'catatan'           => 'Pelunasan sisa cicilan order',
                    'created_by'        => $adminId,
                    'created_at'        => $orderDate->copy()->addDays(20),
                    'updated_at'        => $orderDate->copy()->addDays(20),
                ]);

                // Update order jadi lunas
                DB::table('mitra_vet_orders')->where('id', $orderId)->update([
                    'total_terbayar'    => $grandTotal,
                    'sisa_tagihan'      => 0,
                    'status_pembayaran' => 'lunas',
                ]);
            } elseif (!empty($ob['installment_count']) && $ob['installment_count'] > 0) {
                // Cicilan sebagian (misal 1x cicil)
                $cicilAmount = round($sisaTagihan / $termin, -3);

                DB::table('mitra_vet_order_payments')->insert([
                    'order_id'          => $orderId,
                    'nomor_pembayaran'  => 'PAY-' . $orderDate->copy()->addDays(3)->format('Ymd') . '-' . str_pad($orderId, 4, '0', STR_PAD_LEFT) . '-1',
                    'cicilan_ke'        => 1,
                    'jumlah_bayar'      => $cicilAmount,
                    'tanggal_bayar'     => $orderDate->copy()->addDays(3)->toDateString(),
                    'metode_pembayaran' => 'transfer',
                    'catatan'           => 'Pembayaran cicilan termin 1',
                    'created_by'        => $adminId,
                    'created_at'        => $orderDate->copy()->addDays(3),
                    'updated_at'        => $orderDate->copy()->addDays(3),
                ]);

                $newTerbayar = $dp + $cicilAmount;
                $newSisa = max($grandTotal - $newTerbayar, 0);

                DB::table('mitra_vet_orders')->where('id', $orderId)->update([
                    'total_terbayar' => $newTerbayar,
                    'sisa_tagihan'   => $newSisa,
                ]);
            }
        }
    }
}
