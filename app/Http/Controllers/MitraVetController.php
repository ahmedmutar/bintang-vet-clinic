<?php

namespace App\Http\Controllers;

use App\Exports\MitraVetItemTemplate;
use App\Exports\MitraVetOrderExport;
use App\Exports\MitraVetStockHistoryExport;
use App\Imports\MitraVetItemImport;
use App\Models\MitraVetCustomer;
use App\Models\MitraVetItem;
use App\Models\MitraVetOrder;
use App\Models\MitraVetOrderItem;
use App\Models\MitraVetOrderPayment;
use App\Models\MitraVetStockHistory;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Validator;

class MitraVetController extends Controller
{
    // Host yang diizinkan mengakses modul Mitra Vet
    private const ALLOWED_HOSTS = ['localhost', '127.0.0.1', 'uat.stellavet.net', 'admin.stellavet.net'];
    // Role yang diizinkan mengakses modul Mitra Vet (Dokter TIDAK memiliki akses)
    private const MITRA_VET_ROLES = ['admin', 'admin_gudang', 'admin gudang', 'resepsionis'];
    private const MITRA_VET_ITEM_MANAGERS = ['admin', 'admin_gudang', 'admin gudang'];

    /**
     * Helper validasi role akses Mitra Vet
     */
    private function validateRoleAccess(Request $request, array $allowedRoles, $deniedMessage = 'Akses tidak diizinkan untuk role Anda.')
    {
        // Validasi domain/host yang diizinkan
        if (!in_array($request->getHost(), self::ALLOWED_HOSTS)) {
            return response()->json([
                'message' => 'Modul Mitra Vet tidak tersedia pada domain ini.',
                'errors'  => ['Akses tidak diizinkan untuk domain ini.'],
            ], 403);
        }

        $user = $request->user();
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.',
                'errors'  => ['Silakan login terlebih dahulu.'],
            ], 401);
        }

        $userRole = strtolower($user->role ?? '');
        $allowed = array_map('strtolower', $allowedRoles);

        if (!in_array($userRole, $allowed)) {
            return response()->json([
                'message' => 'Akses tidak diizinkan.',
                'errors'  => [$deniedMessage],
            ], 403);
        }

        return null;
    }

    // =========================================================
    //  STOK BARANG
    // =========================================================

    /**
     * GET /api/mitra-vet/items
     * Daftar barang beserta stok real, harga satuan, nilai aset, total aset
     * Role: admin, admin_gudang, resepsionis
     */
    public function index(Request $request)
    {
        if ($deny = $this->validateRoleAccess($request, self::MITRA_VET_ROLES, 'Hanya Admin, Admin Gudang, dan Resepsionis yang dapat mengakses Mitra Vet.')) {
            return $deny;
        }

        $items_per_page = 50;
        $page           = $request->page ?? 1;

        $query = MitraVetItem::where('is_deleted', false)
            ->select(
                'id',
                'nama_barang',
                'satuan',
                DB::raw('TRIM(harga_modal)+0 as harga_modal'),
                DB::raw('TRIM(harga_jual)+0 as harga_jual'),
                DB::raw('TRIM(profit)+0 as profit'),
                DB::raw('TRIM(harga_satuan)+0 as harga_satuan'),
                'stok',
                DB::raw('TRIM(harga_satuan)+0 * stok as nilai_aset'),
                'created_by',
                DB::raw("DATE_FORMAT(created_at, '%d %b %Y') as created_at")
            );

        if ($request->keyword) {
            $query->where('nama_barang', 'like', '%' . $request->keyword . '%');
        }

        if ($request->orderby && $request->column) {
            $query->orderBy($request->column, $request->orderby);
        } else {
            $query->orderBy('nama_barang', 'asc');
        }

        $count_data  = $query->count();
        $offset      = ($page - 1) * $items_per_page;
        $total_paging = ceil($count_data / $items_per_page);

        $items = $query->offset($offset)->limit($items_per_page)->get();

        // Hitung total nilai aset keseluruhan
        $total_nilai_aset = MitraVetItem::where('is_deleted', false)
            ->selectRaw('SUM(harga_satuan * stok) as total')
            ->value('total') ?? 0;

        $now = Carbon::now();

        // Hitung total profit bulan ini (cash basis: proporsional terhadap uang yang sudah terbayar)
        $total_profit_bulan = DB::table('mitra_vet_order_items as oi')
            ->join('mitra_vet_items as i', 'oi.item_id', '=', 'i.id')
            ->join('mitra_vet_orders as o', 'oi.order_id', '=', 'o.id')
            ->whereMonth('o.created_at', $now->month)
            ->whereYear('o.created_at', $now->year)
            ->selectRaw('SUM(oi.quantity * GREATEST(oi.harga_satuan - i.harga_modal, 0) * (o.grand_total - o.sisa_tagihan) / GREATEST(o.grand_total, 1)) as profit')
            ->value('profit') ?? 0;

        // Hitung total profit keseluruhan (cash basis: proporsional terhadap uang yang sudah terbayar)
        $total_profit_keseluruhan = DB::table('mitra_vet_order_items as oi')
            ->join('mitra_vet_items as i', 'oi.item_id', '=', 'i.id')
            ->join('mitra_vet_orders as o', 'oi.order_id', '=', 'o.id')
            ->selectRaw('SUM(oi.quantity * GREATEST(oi.harga_satuan - i.harga_modal, 0) * (o.grand_total - o.sisa_tagihan) / GREATEST(o.grand_total, 1)) as profit')
            ->value('profit') ?? 0;

        return response()->json([
            'total_paging'             => $total_paging,
            'data'                     => $items,
            'total_nilai_aset'         => $total_nilai_aset,
            'total_profit_bulan'       => $total_profit_bulan,
            'total_profit_keseluruhan' => $total_profit_keseluruhan,
        ], 200);
    }

    /**
     * GET /api/mitra-vet/items/dropdown
     * Dropdown list barang aktif (untuk form order)
     */
    public function itemDropdown(Request $request)
    {
        if ($deny = $this->validateRoleAccess($request, self::MITRA_VET_ROLES)) {
            return $deny;
        }

        $items = MitraVetItem::where('is_deleted', false)
            ->where('stok', '>', 0)
            ->select('id', 'nama_barang', 'satuan', 'harga_satuan', 'harga_jual', 'stok')
            ->orderBy('nama_barang')
            ->get();

        return response()->json($items, 200);
    }

    /**
     * POST /api/mitra-vet/items
     * Tambah barang baru
     * Role: admin, admin_gudang
     */
    public function createItem(Request $request)
    {
        if ($deny = $this->validateRoleAccess($request, self::MITRA_VET_ITEM_MANAGERS, 'Hanya Admin dan Admin Gudang yang dapat menambah barang baru. Resepsionis hanya dapat order barang.')) {
            return $deny;
        }

        $validate = Validator::make($request->all(), [
            'nama_barang' => 'required|string|max:150',
            'satuan'      => 'required|string|max:50',
            'harga_modal' => 'required|numeric|min:0',
            'harga_jual'  => 'required|numeric|min:0',
            'stok'        => 'required|integer|min:0',
        ], [
            'nama_barang.required' => 'Nama barang wajib diisi.',
            'satuan.required'      => 'Satuan barang wajib diisi.',
            'harga_modal.required' => 'Harga modal wajib diisi.',
            'harga_modal.numeric'  => 'Harga modal harus berupa angka.',
            'harga_jual.required'  => 'Harga jual wajib diisi.',
            'harga_jual.numeric'   => 'Harga jual harus berupa angka.',
            'stok.required'        => 'Stok wajib diisi.',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'message' => 'Data tidak valid.',
                'errors'  => $validate->errors()->all(),
            ], 422);
        }

        $hargaModal = (float) $request->harga_modal;
        $hargaJual  = (float) $request->harga_jual;

        if ($hargaJual <= $hargaModal) {
            return response()->json([
                'message' => 'Data tidak valid.',
                'errors'  => ['Harga Jual harus lebih besar dari Harga Modal!'],
            ], 422);
        }

        $profit = $hargaJual - $hargaModal;

        // Cek duplikasi nama barang
        $exists = MitraVetItem::whereRaw('LOWER(nama_barang) = ?', [strtolower(trim($request->nama_barang))])
            ->where('is_deleted', false)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Data sudah ada.',
                'errors'  => ['Barang dengan nama "' . $request->nama_barang . '" sudah terdaftar!'],
            ], 422);
        }

        $item = MitraVetItem::create([
            'nama_barang'  => trim($request->nama_barang),
            'satuan'       => trim($request->satuan),
            'harga_modal'  => $hargaModal,
            'harga_jual'   => $hargaJual,
            'profit'       => $profit,
            'harga_satuan' => $hargaJual,
            'stok'         => $request->stok,
            'created_by'   => $request->user()->id,
        ]);

        // Catat riwayat stok masuk awal
        if ($request->stok > 0) {
            MitraVetStockHistory::create([
                'item_id'    => $item->id,
                'quantity'   => $request->stok,
                'type'       => 'masuk',
                'keterangan' => 'Stok awal saat barang ditambahkan',
                'created_by' => $request->user()->id,
            ]);
        }

        return response()->json(['message' => 'Barang berhasil ditambahkan.'], 200);
    }

    /**
     * PUT /api/mitra-vet/items
     * Edit data barang (nama, satuan, harga) — tidak mengubah stok
     * Role: admin, admin_gudang
     */
    public function updateItem(Request $request)
    {
        if ($deny = $this->validateRoleAccess($request, self::MITRA_VET_ITEM_MANAGERS, 'Hanya Admin dan Admin Gudang yang dapat mengubah data barang. Resepsionis hanya dapat order barang.')) {
            return $deny;
        }

        $validate = Validator::make($request->all(), [
            'id'          => 'required|integer',
            'nama_barang' => 'required|string|max:150',
            'satuan'      => 'required|string|max:50',
            'harga_modal' => 'required|numeric|min:0',
            'harga_jual'  => 'required|numeric|min:0',
            'stok'        => 'nullable|integer|min:0',
        ], [
            'nama_barang.required' => 'Nama barang wajib diisi.',
            'satuan.required'      => 'Satuan barang wajib diisi.',
            'harga_modal.required' => 'Harga modal wajib diisi.',
            'harga_modal.numeric'  => 'Harga modal harus berupa angka.',
            'harga_jual.required'  => 'Harga jual wajib diisi.',
            'harga_jual.numeric'   => 'Harga jual harus berupa angka.',
            'stok.integer'         => 'Stok harus berupa angka bulat.',
            'stok.min'             => 'Stok tidak boleh kurang dari 0.',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'message' => 'Data tidak valid.',
                'errors'  => $validate->errors()->all(),
            ], 422);
        }

        $item = MitraVetItem::find($request->id);
        if (!$item || $item->is_deleted) {
            return response()->json([
                'message' => 'Data tidak ditemukan.',
                'errors'  => ['Barang tidak ditemukan.'],
            ], 404);
        }

        $hargaModal = (float) $request->harga_modal;
        $hargaJual  = (float) $request->harga_jual;

        if ($hargaJual <= $hargaModal) {
            return response()->json([
                'message' => 'Data tidak valid.',
                'errors'  => ['Harga Jual harus lebih besar dari Harga Modal!'],
            ], 422);
        }

        $profit = $hargaJual - $hargaModal;

        // Cek duplikasi nama (kecuali dirinya sendiri)
        $exists = MitraVetItem::whereRaw('LOWER(nama_barang) = ?', [strtolower(trim($request->nama_barang))])
            ->where('is_deleted', false)
            ->where('id', '!=', $request->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Data sudah ada.',
                'errors'  => ['Barang dengan nama "' . $request->nama_barang . '" sudah terdaftar!'],
            ], 422);
        }

        $item->nama_barang  = trim($request->nama_barang);
        $item->satuan       = trim($request->satuan);
        $item->harga_modal  = $hargaModal;
        $item->harga_jual   = $hargaJual;
        $item->profit       = $profit;
        $item->harga_satuan = $hargaJual;
        $item->updated_by   = $request->user()->id;

        // Validasi & Update Stok Barang jika input stok disertakan
        if ($request->has('stok') && !is_null($request->stok)) {
            $newStok  = (int) $request->stok;
            $oldStok  = (int) $item->stok;
            $userRole = strtolower($request->user()->role ?? '');

            // Admin Gudang HANYA BISA update/tambah stok, TIDAK BISA mengurangi stok
            if ($newStok < $oldStok && $userRole !== 'admin') {
                return response()->json([
                    'message' => 'Akses ditolak.',
                    'errors'  => ["Admin Gudang tidak diizinkan mengurangi stok barang! Stok saat ini ({$oldStok}) tidak boleh diturunkan menjadi ({$newStok})."],
                ], 422);
            }

            if ($newStok > $oldStok) {
                $selisih = $newStok - $oldStok;
                MitraVetStockHistory::create([
                    'item_id'    => $item->id,
                    'quantity'   => $selisih,
                    'type'       => 'masuk',
                    'keterangan' => "Update penambahan stok barang ({$oldStok} -> {$newStok})",
                    'created_by' => $request->user()->id,
                ]);
                $item->stok = $newStok;
            } elseif ($newStok < $oldStok && $userRole === 'admin') {
                $selisih = $oldStok - $newStok;
                MitraVetStockHistory::create([
                    'item_id'    => $item->id,
                    'quantity'   => $selisih,
                    'type'       => 'keluar',
                    'keterangan' => "Penyesuaian pengurangan stok oleh Admin ({$oldStok} -> {$newStok})",
                    'created_by' => $request->user()->id,
                ]);
                $item->stok = $newStok;
            }
        }

        $item->save();

        return response()->json(['message' => 'Barang & stok berhasil diubah.'], 200);
    }

    /**
     * POST /api/mitra-vet/tambah-stok
     * Tambah jumlah stok barang yang sudah ada
     * Role: admin, admin_gudang
     */
    public function tambahStok(Request $request)
    {
        if ($deny = $this->validateRoleAccess($request, self::MITRA_VET_ITEM_MANAGERS, 'Hanya Admin dan Admin Gudang yang dapat menambah stok barang. Resepsionis hanya dapat order barang.')) {
            return $deny;
        }

        $validate = Validator::make($request->all(), [
            'item_id'    => 'required|integer',
            'quantity'   => 'required|integer|min:1',
            'keterangan' => 'nullable|string|max:255',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'message' => 'Data tidak valid.',
                'errors'  => $validate->errors()->all(),
            ], 422);
        }

        $item = MitraVetItem::find($request->item_id);
        if (!$item || $item->is_deleted) {
            return response()->json([
                'message' => 'Data tidak ditemukan.',
                'errors'  => ['Barang tidak ditemukan.'],
            ], 404);
        }

        $item->stok      += $request->quantity;
        $item->updated_by = $request->user()->id;
        $item->save();

        MitraVetStockHistory::create([
            'item_id'    => $item->id,
            'quantity'   => $request->quantity,
            'type'       => 'masuk',
            'keterangan' => $request->keterangan ?? 'Penambahan stok manual',
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['message' => 'Stok berhasil ditambahkan.'], 200);
    }

    /**
     * DELETE /api/mitra-vet/items
     * Hapus barang (soft delete)
     * Role: admin only
     */
    public function deleteItem(Request $request)
    {
        if ($deny = $this->validateRoleAccess($request, ['admin'], 'Hanya Admin yang dapat menghapus barang.')) {
            return $deny;
        }

        $item = MitraVetItem::find($request->id);
        if (!$item || $item->is_deleted) {
            return response()->json([
                'message' => 'Data tidak ditemukan.',
                'errors'  => ['Barang tidak ditemukan.'],
            ], 404);
        }

        $item->is_deleted = true;
        $item->updated_by = $request->user()->id;
        $item->save();

        return response()->json(['message' => 'Barang berhasil dihapus.'], 200);
    }

    // =========================================================
    //  UPLOAD EXCEL
    // =========================================================

    /**
     * GET /api/mitra-vet/items/download-template
     * Download template Excel untuk upload massal
     */
    public function downloadTemplate(Request $request)
    {
        if ($deny = $this->validateRoleAccess($request, self::MITRA_VET_ITEM_MANAGERS, 'Hanya Admin dan Admin Gudang yang dapat mendownload template barang.')) {
            return $deny;
        }

        return Excel::download(new MitraVetItemTemplate, 'Template Produk Mitra Vet.xlsx');
    }

    /**
     * POST /api/mitra-vet/items/upload
     * Upload Excel untuk input massal produk
     * Role: admin, admin_gudang
     */
    public function uploadItems(Request $request)
    {
        if ($deny = $this->validateRoleAccess($request, self::MITRA_VET_ITEM_MANAGERS, 'Hanya Admin dan Admin Gudang yang dapat mengupload barang baru. Resepsionis hanya dapat order barang.')) {
            return $deny;
        }

        $validate = Validator::make($request->all(), [
            'file' => 'required|mimes:xls,xlsx|max:5120',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'message' => 'File tidak valid.',
                'errors'  => $validate->errors()->all(),
            ], 422);
        }

        try {
            $import = new MitraVetItemImport($request->user()->id);

            // Baca dulu untuk preview & validasi sebelum import
            $rows = Excel::toArray(new MitraVetItemImport($request->user()->id), $request->file('file'));
            $data = $rows[0] ?? [];

            if (empty($data)) {
                return response()->json([
                    'message' => 'File kosong.',
                    'errors'  => ['File Excel tidak memiliki data.'],
                ], 422);
            }

            // Validasi kolom wajib ada
            $requiredColumns = ['nama_barang', 'satuan', 'harga_modal', 'harga_jual', 'stok'];
            $firstRow        = array_keys($data[0] ?? []);
            $missingCols     = array_diff($requiredColumns, $firstRow);

            if (!empty($missingCols)) {
                return response()->json([
                    'message' => 'Format file tidak sesuai.',
                    'errors'  => ['Kolom berikut tidak ditemukan pada template Excel: ' . implode(', ', $missingCols) . '. Silakan download dan gunakan template Excel terbaru.'],
                ], 422);
            }

            // Validasi tiap baris
            $validationErrors = [];
            foreach ($data as $index => $row) {
                $rowNumber = $index + 2; // +2 karena baris 1 adalah header

                if (empty(trim($row['nama_barang'] ?? ''))) {
                    $validationErrors[] = "Baris {$rowNumber}: nama_barang wajib diisi.";
                }
                if (empty(trim($row['satuan'] ?? ''))) {
                    $validationErrors[] = "Baris {$rowNumber}: satuan wajib diisi.";
                }
                
                $hasModal = isset($row['harga_modal']) && is_numeric($row['harga_modal']) && $row['harga_modal'] >= 0;
                $hasJual  = isset($row['harga_jual']) && is_numeric($row['harga_jual']) && $row['harga_jual'] >= 0;

                if (!$hasModal) {
                    $validationErrors[] = "Baris {$rowNumber}: harga_modal harus berupa angka >= 0.";
                }
                if (!$hasJual) {
                    $validationErrors[] = "Baris {$rowNumber}: harga_jual harus berupa angka >= 0.";
                }

                if ($hasModal && $hasJual) {
                    $modalVal = (float) $row['harga_modal'];
                    $jualVal  = (float) $row['harga_jual'];
                    if ($jualVal <= $modalVal) {
                        $validationErrors[] = "Baris {$rowNumber}: Harga Jual (Rp " . number_format($jualVal, 0, ',', '.') . ") harus lebih besar dari Harga Modal (Rp " . number_format($modalVal, 0, ',', '.') . ").";
                    }
                }

                if (!isset($row['stok']) || !is_numeric($row['stok']) || (int) $row['stok'] < 0) {
                    $validationErrors[] = "Baris {$rowNumber}: stok harus berupa angka bulat >= 0.";
                }
            }

            if (!empty($validationErrors)) {
                return response()->json([
                    'message' => 'Terdapat kesalahan pada data.',
                    'errors'  => $validationErrors,
                ], 422);
            }

            // Proses import
            $inserted = 0;
            $updated  = 0;
            $updatedNames = [];
            $insertedNames = [];

            DB::beginTransaction();
            try {
                foreach ($data as $row) {
                    $namaBarangClean = trim(preg_replace('/\s+/', ' ', $row['nama_barang']));
                    $qtyTambah       = (int) $row['stok'];
                    $hargaModal      = (float) str_replace(',', '', $row['harga_modal']);
                    $hargaJual       = (float) str_replace(',', '', $row['harga_jual']);
                    $profit          = $hargaJual - $hargaModal;
                    $satuan          = trim($row['satuan']);

                    // Cek apakah barang sudah ada di tabel (case-insensitive)
                    $existing = MitraVetItem::whereRaw('LOWER(nama_barang) = ?', [strtolower($namaBarangClean)])
                        ->where('is_deleted', false)
                        ->first();

                    if ($existing) {
                        // JIKA SUDAH ADA: TAMBAH STOK KE BARANG TERSEBUT & UPDATE HARGA
                        $stokSebelumnya       = $existing->stok;
                        $existing->stok      += $qtyTambah;
                        $existing->satuan     = $satuan ?: $existing->satuan;
                        $existing->harga_modal = $hargaModal;
                        $existing->harga_jual  = $hargaJual;
                        $existing->profit      = $profit;
                        $existing->harga_satuan = $hargaJual;
                        $existing->updated_by = $request->user()->id;
                        $existing->save();

                        if ($qtyTambah > 0) {
                            MitraVetStockHistory::create([
                                'item_id'    => $existing->id,
                                'quantity'   => $qtyTambah,
                                'type'       => 'masuk',
                                'keterangan' => "Penambahan stok via upload Excel (Stok: {$stokSebelumnya} -> {$existing->stok})",
                                'created_by' => $request->user()->id,
                            ]);
                        }
                        $updated++;
                        $updatedNames[] = "{$existing->nama_barang} (+{$qtyTambah})";
                    } else {
                        // JIKA BELUM ADA: BUAT BARANG BARU BESERTA STOK AWALNYA
                        $item = MitraVetItem::create([
                            'nama_barang'  => $namaBarangClean,
                            'satuan'       => $satuan,
                            'harga_modal'  => $hargaModal,
                            'harga_jual'   => $hargaJual,
                            'profit'       => $profit,
                            'harga_satuan' => $hargaJual,
                            'stok'         => $qtyTambah,
                            'created_by'   => $request->user()->id,
                        ]);

                        if ($qtyTambah > 0) {
                            MitraVetStockHistory::create([
                                'item_id'    => $item->id,
                                'quantity'   => $qtyTambah,
                                'type'       => 'masuk',
                                'keterangan' => 'Stok awal via upload Excel',
                                'created_by' => $request->user()->id,
                            ]);
                        }
                        $inserted++;
                        $insertedNames[] = "{$item->nama_barang} ({$qtyTambah} {$item->satuan})";
                    }
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Gagal menyimpan data ke database.',
                    'errors'  => [$e->getMessage()],
                ], 500);
            }

            $message = "Upload berhasil!";
            if ($inserted > 0) {
                $message .= " {$inserted} barang baru ditambahkan.";
            }
            if ($updated > 0) {
                $message .= " {$updated} barang sudah terdaftar & stoknya otomatis ditambahkan.";
            }

            return response()->json([
                'message'        => $message,
                'inserted'       => $inserted,
                'updated'        => $updated,
                'updated_items'  => $updatedNames,
                'inserted_items' => $insertedNames,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan saat memproses file.',
                'errors'  => [$e->getMessage()],
            ], 500);
        }
    }

    // =========================================================
    //  ORDERAN
    // =========================================================

    /**
     * GET /api/mitra-vet/orders
     * Daftar orderan dengan filter bulan/tahun
     */
    public function getOrders(Request $request)
    {
        if ($deny = $this->validateRoleAccess($request, self::MITRA_VET_ROLES)) {
            return $deny;
        }

        $items_per_page = 50;
        $page           = $request->page ?? 1;

        $query = DB::table('mitra_vet_orders as o')
            ->leftJoin('users as u', 'o.created_by', '=', 'u.id')
            ->select(
                'o.id',
                'o.nomor_order',
                'o.nama_pembeli',
                'o.alamat',
                'o.no_hp',
                DB::raw('TRIM(o.biaya_ongkir)+0 as biaya_ongkir'),
                DB::raw('TRIM(o.total_harga)+0 as total_harga'),
                DB::raw('TRIM(o.grand_total)+0 as grand_total'),
                'o.tipe_pembayaran',
                'o.status_pembayaran',
                'o.jumlah_termin',
                DB::raw('TRIM(o.uang_muka)+0 as uang_muka'),
                DB::raw('TRIM(o.total_terbayar)+0 as total_terbayar'),
                DB::raw('TRIM(o.sisa_tagihan)+0 as sisa_tagihan'),
                'o.tanggal_jatuh_tempo',
                'o.catatan',
                'u.fullname as created_by',
                DB::raw("DATE_FORMAT(o.created_at, '%d %b %Y %H:%i') as created_at")
            );

        if ($request->keyword) {
            $query->where(function ($q) use ($request) {
                $q->where('o.nama_pembeli', 'like', '%' . $request->keyword . '%')
                    ->orWhere('o.nomor_order', 'like', '%' . $request->keyword . '%')
                    ->orWhere('o.no_hp', 'like', '%' . $request->keyword . '%');
            });
        }

        if ($request->filled('month') && $request->filled('year')) {
            $query->whereMonth('o.created_at', $request->month)
                ->whereYear('o.created_at', $request->year);
        } elseif ($request->filled('year')) {
            $query->whereYear('o.created_at', $request->year);
        } elseif ($request->filled('month')) {
            $query->whereMonth('o.created_at', $request->month);
        }

        $query->orderBy('o.id', 'desc');

        $count_data   = $query->count();
        $offset       = ($page - 1) * $items_per_page;
        $total_paging = ceil($count_data / $items_per_page);

        // Hitung total keseluruhan (Total Harga, Ongkir, Grand Total) sesuai filter aktif
        $totalsQuery = DB::table('mitra_vet_orders as o');
        if ($request->keyword) {
            $totalsQuery->where(function ($q) use ($request) {
                $q->where('o.nama_pembeli', 'like', '%' . $request->keyword . '%')
                    ->orWhere('o.nomor_order', 'like', '%' . $request->keyword . '%')
                    ->orWhere('o.no_hp', 'like', '%' . $request->keyword . '%');
            });
        }
        if ($request->filled('month') && $request->filled('year')) {
            $totalsQuery->whereMonth('o.created_at', $request->month)
                ->whereYear('o.created_at', $request->year);
        } elseif ($request->filled('year')) {
            $totalsQuery->whereYear('o.created_at', $request->year);
        } elseif ($request->filled('month')) {
            $totalsQuery->whereMonth('o.created_at', $request->month);
        }

        $totals = $totalsQuery->selectRaw('
            COALESCE(SUM(o.total_harga), 0) as total_harga_keseluruhan,
            COALESCE(SUM(o.biaya_ongkir), 0) as total_ongkir_keseluruhan,
            COALESCE(SUM(o.grand_total), 0) as grand_total_keseluruhan
        ')->first();

        $orders = $query->offset($offset)->limit($items_per_page)->get();

        // Ambil rincian barang untuk setiap order yang ditampilkan
        $orderIds = $orders->pluck('id')->toArray();
        if (!empty($orderIds)) {
            $orderItems = DB::table('mitra_vet_order_items as oi')
                ->leftJoin('mitra_vet_items as i', 'oi.item_id', '=', 'i.id')
                ->whereIn('oi.order_id', $orderIds)
                ->select(
                    'oi.order_id',
                    'oi.quantity',
                    DB::raw('TRIM(oi.harga_satuan)+0 as harga_satuan'),
                    DB::raw('TRIM(oi.subtotal)+0 as subtotal'),
                    'i.nama_barang',
                    'i.satuan'
                )
                ->get()
                ->groupBy('order_id');

            foreach ($orders as $order) {
                $order->items = $orderItems->get($order->id, collect())->values();
            }
        }

        return response()->json([
            'total_paging'             => $total_paging,
            'data'                     => $orders,
            'total_harga_keseluruhan'  => (float) ($totals->total_harga_keseluruhan ?? 0),
            'total_ongkir_keseluruhan' => (float) ($totals->total_ongkir_keseluruhan ?? 0),
            'grand_total_keseluruhan'   => (float) ($totals->grand_total_keseluruhan ?? 0),
        ], 200);
    }

    /**
     * POST /api/mitra-vet/orders
     * Buat orderan baru (stok berkurang otomatis)
     */
    public function createOrder(Request $request)
    {
        if ($deny = $this->validateRoleAccess($request, ['admin'], 'Hanya Admin yang dapat membuat orderan (mengurangi stok barang).')) {
            return $deny;
        }

        $validate = Validator::make($request->all(), [
            'nama_pembeli'  => 'required|string|max:150',
            'alamat'        => 'required|string',
            'no_hp'         => 'required|string|max:20',
            'biaya_ongkir'  => 'required|numeric|min:0',
            'catatan'       => 'nullable|string',
            'items'         => 'required|array|min:1',
            'items.*.item_id'  => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'message' => 'Data tidak valid.',
                'errors'  => $validate->errors()->all(),
            ], 422);
        }

        // Cek stok semua item sebelum proses
        $itemsData  = $request->items;
        $orderItems = [];
        $totalHarga = 0;

        foreach ($itemsData as $orderItem) {
            $item = MitraVetItem::find($orderItem['item_id']);

            if (!$item || $item->is_deleted) {
                return response()->json([
                    'message' => 'Data barang tidak ditemukan.',
                    'errors'  => ["Barang dengan ID {$orderItem['item_id']} tidak ditemukan."],
                ], 422);
            }

            if ($item->stok < $orderItem['quantity']) {
                return response()->json([
                    'message' => 'Stok tidak mencukupi.',
                    'errors'  => ["Stok {$item->nama_barang} tidak mencukupi. Stok tersedia: {$item->stok}, diminta: {$orderItem['quantity']}."],
                ], 422);
            }

            $subtotal     = $item->harga_satuan * $orderItem['quantity'];
            $totalHarga  += $subtotal;
            $orderItems[] = [
                'item'      => $item,
                'quantity'  => $orderItem['quantity'],
                'subtotal'  => $subtotal,
            ];
        }

        $grandTotal  = $totalHarga + $request->biaya_ongkir;
        $nomorOrder  = 'MV-' . Carbon::now()->format('Ymd') . '-' . str_pad(
            (MitraVetOrder::whereDate('created_at', Carbon::today())->count() + 1),
            4,
            '0',
            STR_PAD_LEFT
        );

        DB::beginTransaction();
        try {
            $customerId = $request->customer_id;
            if (!$customerId) {
                // Buat atau cari customer secara otomatis
                $cust = MitraVetCustomer::firstOrCreate(
                    ['nama' => trim($request->nama_pembeli), 'is_deleted' => false],
                    [
                        'no_hp'      => trim($request->no_hp),
                        'alamat'     => trim($request->alamat),
                        'created_by' => $request->user()->id,
                    ]
                );
                $customerId = $cust->id;
            } else {
                // Update no_hp dan alamat jika berbeda
                $cust = MitraVetCustomer::find($customerId);
                if ($cust) {
                    $cust->no_hp  = trim($request->no_hp);
                    $cust->alamat = trim($request->alamat);
                    $cust->save();
                }
            }

            $tipePembayaran = $request->tipe_pembayaran ?: 'lunas';
            $jumlahTermin   = max((int) ($request->jumlah_termin ?: 1), 1);
            $uangMuka       = (float) ($request->uang_muka ?: 0);
            $jatuhTempo     = $request->tanggal_jatuh_tempo ?: null;

            if ($tipePembayaran === 'cicilan') {
                $totalTerbayar = $uangMuka;
                $sisaTagihan   = max($grandTotal - $uangMuka, 0);
                $statusBayar   = $sisaTagihan <= 0 ? 'lunas' : 'cicilan';
            } else {
                $totalTerbayar = $grandTotal;
                $sisaTagihan   = 0;
                $statusBayar   = 'lunas';
                $jumlahTermin  = 1;
                $uangMuka      = $grandTotal;
            }

            $order = MitraVetOrder::create([
                'nomor_order'         => $nomorOrder,
                'customer_id'         => $customerId,
                'nama_pembeli'        => $request->nama_pembeli,
                'alamat'              => $request->alamat,
                'no_hp'               => $request->no_hp,
                'biaya_ongkir'        => $request->biaya_ongkir,
                'total_harga'         => $totalHarga,
                'grand_total'         => $grandTotal,
                'tipe_pembayaran'     => $tipePembayaran,
                'status_pembayaran'   => $statusBayar,
                'jumlah_termin'       => $jumlahTermin,
                'uang_muka'           => $uangMuka,
                'total_terbayar'      => $totalTerbayar,
                'sisa_tagihan'        => $sisaTagihan,
                'tanggal_jatuh_tempo' => $jatuhTempo,
                'catatan'             => $request->catatan,
                'created_by'          => $request->user()->id,
            ]);

            // Jika ada uang muka DP pada cicilan, catat di payment
            if ($tipePembayaran === 'cicilan' && $uangMuka > 0) {
                MitraVetOrderPayment::create([
                    'order_id'          => $order->id,
                    'nomor_pembayaran'  => 'DP-' . $nomorOrder,
                    'cicilan_ke'        => 0, // 0 = DP
                    'jumlah_bayar'      => $uangMuka,
                    'tanggal_bayar'     => Carbon::now()->toDateString(),
                    'metode_pembayaran' => 'transfer',
                    'catatan'           => 'Uang Muka (DP) saat order dibuat',
                    'created_by'        => $request->user()->id,
                ]);
            }

            foreach ($orderItems as $oi) {
                MitraVetOrderItem::create([
                    'order_id'    => $order->id,
                    'item_id'     => $oi['item']->id,
                    'quantity'    => $oi['quantity'],
                    'harga_satuan' => $oi['item']->harga_satuan,
                    'subtotal'    => $oi['subtotal'],
                ]);

                // Kurangi stok
                $oi['item']->stok      -= $oi['quantity'];
                $oi['item']->updated_by = $request->user()->id;
                $oi['item']->save();

                // Catat riwayat stok keluar
                MitraVetStockHistory::create([
                    'item_id'    => $oi['item']->id,
                    'quantity'   => $oi['quantity'],
                    'type'       => 'keluar',
                    'keterangan' => "Order {$nomorOrder} - {$request->nama_pembeli}",
                    'created_by' => $request->user()->id,
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal membuat orderan.',
                'errors'  => [$e->getMessage()],
            ], 500);
        }

        return response()->json([
            'message'      => 'Orderan berhasil dibuat.',
            'nomor_order'  => $nomorOrder,
            'order_id'     => $order->id,
        ], 200);
    }

    /**
     * DELETE /api/mitra-vet/orders
     * Hapus orderan (stok dikembalikan) — admin only
     */
    public function deleteOrder(Request $request)
    {
        if ($deny = $this->validateRoleAccess($request, ['admin'], 'Hanya Admin yang dapat membatalkan dan menghapus orderan.')) {
            return $deny;
        }

        $order = MitraVetOrder::with('items.item')->find($request->id);
        if (!$order) {
            return response()->json([
                'message' => 'Data tidak ditemukan.',
                'errors'  => ['Orderan tidak ditemukan.'],
            ], 404);
        }

        DB::beginTransaction();
        try {
            // Kembalikan stok
            foreach ($order->items as $oi) {
                if ($oi->item) {
                    $oi->item->stok      += $oi->quantity;
                    $oi->item->updated_by = $request->user()->id;
                    $oi->item->save();

                    MitraVetStockHistory::create([
                        'item_id'    => $oi->item->id,
                        'quantity'   => $oi->quantity,
                        'type'       => 'masuk',
                        'keterangan' => "Pembatalan order {$order->nomor_order}",
                        'created_by' => $request->user()->id,
                    ]);
                }
            }

            $order->items()->delete();
            $order->delete();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menghapus orderan.',
                'errors'  => [$e->getMessage()],
            ], 500);
        }

        return response()->json(['message' => 'Orderan berhasil dihapus dan stok dikembalikan.'], 200);
    }

    /**
     * GET /api/mitra-vet/piutang
     * Daftar piutang / cicilan orderan dengan filter status, customer, dan keyword
     */
    public function getPiutang(Request $request)
    {
        if ($deny = $this->validateRoleAccess($request, self::MITRA_VET_ROLES)) {
            return $deny;
        }

        $items_per_page = 50;
        $page           = $request->page ?? 1;
        $status         = $request->status; // belum_lunas / jatuh_tempo / lunas
        $customerId     = $request->customer_id;
        $keyword        = $request->keyword;
        $today          = Carbon::now()->toDateString();

        $query = DB::table('mitra_vet_orders as o')
            ->leftJoin('mitra_vet_customers as c', 'o.customer_id', '=', 'c.id')
            ->leftJoin('users as u', 'o.created_by', '=', 'u.id')
            ->select(
                'o.id',
                'o.nomor_order',
                'o.customer_id',
                'o.nama_pembeli',
                'o.no_hp',
                'o.alamat',
                DB::raw('TRIM(o.grand_total)+0 as grand_total'),
                DB::raw('TRIM(o.uang_muka)+0 as uang_muka'),
                DB::raw('TRIM(o.total_terbayar)+0 as total_terbayar'),
                DB::raw('TRIM(o.sisa_tagihan)+0 as sisa_tagihan'),
                'o.tipe_pembayaran',
                'o.status_pembayaran',
                'o.jumlah_termin',
                'o.tanggal_jatuh_tempo',
                'u.fullname as created_by',
                DB::raw("DATE_FORMAT(o.created_at, '%d %b %Y') as tgl_order"),
                DB::raw("DATE_FORMAT(o.tanggal_jatuh_tempo, '%d %b %Y') as tgl_tempo")
            );

        if ($status === 'belum_lunas') {
            $query->where('o.sisa_tagihan', '>', 0);
        } elseif ($status === 'jatuh_tempo') {
            $query->where('o.sisa_tagihan', '>', 0)
                  ->whereNotNull('o.tanggal_jatuh_tempo')
                  ->where('o.tanggal_jatuh_tempo', '<', $today);
        } elseif ($status === 'lunas') {
            $query->where('o.sisa_tagihan', '<=', 0);
        }

        if ($customerId) {
            $query->where('o.customer_id', $customerId);
        }

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('o.nomor_order', 'like', "%{$keyword}%")
                  ->orWhere('o.nama_pembeli', 'like', "%{$keyword}%")
                  ->orWhere('o.no_hp', 'like', "%{$keyword}%");
            });
        }

        // Summary cards
        $totalSisaPiutang = DB::table('mitra_vet_orders')
            ->where('sisa_tagihan', '>', 0)
            ->sum('sisa_tagihan');

        $totalJatuhTempo = DB::table('mitra_vet_orders')
            ->where('sisa_tagihan', '>', 0)
            ->whereNotNull('tanggal_jatuh_tempo')
            ->where('tanggal_jatuh_tempo', '<', $today)
            ->sum('sisa_tagihan');

        $totalTertagih = DB::table('mitra_vet_order_payments')->sum('jumlah_bayar');

        $count_data   = $query->count();
        $offset       = ($page - 1) * $items_per_page;
        $total_paging = ceil($count_data / $items_per_page);

        $orders = $query->orderBy('o.sisa_tagihan', 'desc')
                        ->orderBy('o.created_at', 'desc')
                        ->offset($offset)
                        ->limit($items_per_page)
                        ->get();

        // Hitung cicilan ke-X untuk tiap order
        foreach ($orders as $ord) {
            $paymentsCount = DB::table('mitra_vet_order_payments')
                ->where('order_id', $ord->id)
                ->where('cicilan_ke', '>', 0)
                ->count();
            $ord->total_cicilan_masuk = $paymentsCount;

            // Hitung status jatuh tempo dinamis
            if ($ord->sisa_tagihan > 0 && $ord->tanggal_jatuh_tempo && $ord->tanggal_jatuh_tempo < $today) {
                $ord->is_jatuh_tempo = true;
            } else {
                $ord->is_jatuh_tempo = false;
            }
        }

        return response()->json([
            'total_paging' => $total_paging,
            'data'         => $orders,
            'summary'      => [
                'total_sisa_piutang' => $totalSisaPiutang,
                'total_jatuh_tempo'  => $totalJatuhTempo,
                'total_tertagih'     => $totalTertagih,
            ]
        ], 200);
    }

    /**
     * POST /api/mitra-vet/piutang/bayar
     * Catat pembayaran cicilan baru untuk order tertentu
     */
    public function bayarCicilan(Request $request)
    {
        if ($deny = $this->validateRoleAccess($request, self::MITRA_VET_ROLES)) {
            return $deny;
        }

        $validate = Validator::make($request->all(), [
            'order_id'          => 'required|integer',
            'jumlah_bayar'      => 'required|numeric|min:1',
            'tanggal_bayar'     => 'required|date',
            'metode_pembayaran' => 'required|string',
        ], [
            'jumlah_bayar.required' => 'Nominal pembayaran wajib diisi.',
            'jumlah_bayar.min'      => 'Nominal pembayaran minimal Rp 1.',
            'tanggal_bayar.required' => 'Tanggal pembayaran wajib diisi.',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'message' => 'Data pembayaran tidak valid.',
                'errors'  => $validate->errors()->all(),
            ], 422);
        }

        $order = MitraVetOrder::find($request->order_id);
        if (!$order) {
            return response()->json(['message' => 'Orderan tidak ditemukan.'], 404);
        }

        if ($order->sisa_tagihan <= 0) {
            return response()->json(['message' => 'Orderan ini sudah lunas sepenuhnya.'], 422);
        }

        $jumlahBayar = (float) $request->jumlah_bayar;
        if ($jumlahBayar > $order->sisa_tagihan) {
            $jumlahBayar = (float) $order->sisa_tagihan;
        }

        DB::beginTransaction();
        try {
            $nextCicilanKe = $order->payments()->where('cicilan_ke', '>', 0)->count() + 1;
            $nomorPembayaran = 'PAY-' . date('Ymd') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT) . '-' . $nextCicilanKe;

            MitraVetOrderPayment::create([
                'order_id'          => $order->id,
                'nomor_pembayaran'  => $nomorPembayaran,
                'cicilan_ke'        => $nextCicilanKe,
                'jumlah_bayar'      => $jumlahBayar,
                'tanggal_bayar'     => $request->tanggal_bayar,
                'metode_pembayaran' => $request->metode_pembayaran,
                'catatan'           => $request->catatan,
                'created_by'        => $request->user()->id,
            ]);

            $order->total_terbayar += $jumlahBayar;
            $order->sisa_tagihan    = max($order->grand_total - $order->total_terbayar, 0);

            if ($order->sisa_tagihan <= 0) {
                $order->status_pembayaran = 'lunas';
            } else {
                $order->status_pembayaran = 'cicilan';
            }
            $order->save();

            DB::commit();

            return response()->json([
                'message'      => "Pembayaran cicilan ke-{$nextCicilanKe} sebesar Rp " . number_format($jumlahBayar, 0, ',', '.') . " berhasil dicatat!",
                'status'       => $order->status_pembayaran,
                'sisa_tagihan' => $order->sisa_tagihan,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal mencatat pembayaran.',
                'errors'  => [$e->getMessage()],
            ], 500);
        }
    }

    /**
     * GET /api/mitra-vet/piutang/{orderId}/riwayat
     * Ambil riwayat pembayaran cicilan untuk satu order
     */
    public function getRiwayatCicilan(Request $request, $orderId)
    {
        if ($deny = $this->validateRoleAccess($request, self::MITRA_VET_ROLES)) {
            return $deny;
        }

        $order = MitraVetOrder::with(['customer', 'payments.creator'])->find($orderId);
        if (!$order) {
            return response()->json(['message' => 'Orderan tidak ditemukan.'], 404);
        }

        return response()->json([
            'order'    => $order,
            'payments' => $order->payments,
        ], 200);
    }

    /**
     * GET /mitra-vet/orders/{orderId}/invoice-penagihan
     * Download Invoice / Surat Penagihan Pembayaran dalam format PDF
     */
    public function downloadInvoicePenagihan($orderId)
    {
        $order = MitraVetOrder::with(['items.item', 'creator', 'payments'])->find($orderId);
        if (!$order) {
            abort(404, 'Orderan tidak ditemukan.');
        }

        // Hitung nominal cicilan per termin (cash basis)
        $cicilanMasuk = $order->payments->where('cicilan_ke', '>', 0)->count();
        $sisaTermin   = max(($order->jumlah_termin ?? 1) - $cicilanMasuk, 1);
        $nominalCicilan = round($order->sisa_tagihan / $sisaTermin);

        $filename = 'Invoice-Penagihan-' . str_replace(['/', '\\', ' '], '-', $order->nomor_order) . '.pdf';

        $pdf = \PDF::loadView('mitra-vet.invoice-penagihan-pdf', compact('order', 'nominalCicilan', 'sisaTermin', 'cicilanMasuk'));
        return $pdf->download($filename);
    }

    /**
     * GET /mitra-vet/nota/{id}
     * Tampilkan nota order (HTML printable / downloadable)
     */
    public function notaOrder($id)
    {
        $order = MitraVetOrder::with(['items.item', 'creator'])->find($id);

        if (!$order) {
            abort(404, 'Order tidak ditemukan.');
        }

        return view('mitra-vet.nota', compact('order'));
    }

    /**
     * GET /mitra-vet/nota/{id}/download
     * Download nota order langsung dalam format PDF
     */
    public function downloadNota($id)
    {
        $order = MitraVetOrder::with(['items.item', 'creator'])->find($id);

        if (!$order) {
            abort(404, 'Order tidak ditemukan.');
        }

        $filename = 'Nota-' . str_replace(['/', '\\', ' '], '-', $order->nomor_order) . '.pdf';

        $pdf = \PDF::loadView('mitra-vet.nota-pdf', compact('order'));
        return $pdf->download($filename);
    }

    /**
     * GET /api/mitra-vet/orders/customer-pdf (atau /mitra-vet/orders/customer-pdf)
     * Download struk / rekap PDF riwayat order per pelanggan
     * Filter: customer_id (wajib), year (wajib), month (opsional, jika kosong = 1 tahun penuh)
     * Data diurutkan berdasarkan tanggal order ASC
     */
    public function downloadCustomerRecapPdf(Request $request)
    {
        if ($deny = $this->validateRoleAccess($request, self::MITRA_VET_ROLES)) {
            return $deny;
        }

        $customerId = $request->customer_id;
        $year       = $request->year ?: Carbon::now()->year;
        $month      = $request->month;

        if (!$customerId) {
            return response()->json([
                'message' => 'Silakan pilih pelanggan/mitra terlebih dahulu.'
            ], 422);
        }

        $customer = MitraVetCustomer::find($customerId);
        if (!$customer) {
            // Coba cari nama pembeli dari order pertama dengan customer_id tsb
            $sampleOrder = MitraVetOrder::where('customer_id', $customerId)->first();
            if ($sampleOrder) {
                $customer = (object) [
                    'id'     => $customerId,
                    'nama'   => $sampleOrder->nama_pembeli,
                    'no_hp'  => $sampleOrder->no_hp,
                    'alamat' => $sampleOrder->alamat,
                ];
            } else {
                return response()->json([
                    'message' => 'Data pelanggan/mitra tidak ditemukan.'
                ], 404);
            }
        }

        // Query orders milik pelanggan pada periode tersebut, diurutkan tanggal ASC
        $query = MitraVetOrder::with(['items.item', 'creator'])
            ->where(function ($q) use ($customer, $customerId) {
                $q->where('customer_id', $customerId);
                if (!empty($customer->nama)) {
                    $q->orWhereRaw('LOWER(nama_pembeli) = ?', [strtolower(trim($customer->nama))]);
                }
            })
            ->whereYear('created_at', $year);

        if (!empty($month)) {
            $query->whereMonth('created_at', $month);
        }

        $orders = $query->orderBy('created_at', 'asc')->get();

        if ($orders->isEmpty()) {
            return response()->json([
                'message' => 'Tidak ada transaksi order untuk pelanggan ini pada periode yang dipilih.'
            ], 404);
        }

        // Label periode
        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        if (!empty($month) && isset($namaBulan[(int)$month])) {
            $periodeText = $namaBulan[(int)$month] . ' ' . $year;
            $filePeriode = $namaBulan[(int)$month] . '-' . $year;
        } else {
            $periodeText = 'Tahun ' . $year . ' (Semua Bulan)';
            $filePeriode = 'Tahun-' . $year;
        }

        $totalOrders = $orders->count();
        $totalQty    = 0;
        $totalProduk = 0;
        $totalOngkir = 0;
        $grandTotal  = 0;

        foreach ($orders as $ord) {
            $totalProduk += (float) $ord->total_harga;
            $totalOngkir += (float) $ord->biaya_ongkir;
            $grandTotal  += (float) $ord->grand_total;
            foreach ($ord->items as $it) {
                $totalQty += (int) $it->quantity;
            }
        }

        $summary = (object) [
            'total_orders' => $totalOrders,
            'total_qty'    => $totalQty,
            'total_produk' => $totalProduk,
            'total_ongkir' => $totalOngkir,
            'grand_total'  => $grandTotal,
            'periode'      => $periodeText,
        ];

        $cleanCustomerName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $customer->nama);
        $filename = "Struk-Rekap-{$cleanCustomerName}-{$filePeriode}.pdf";

        $pdf = \PDF::loadView('mitra-vet.rekap-order-customer-pdf', compact('customer', 'orders', 'summary'));
        return $pdf->download($filename);
    }

    // =========================================================
    //  GRAFIK
    // =========================================================

    /**
     * GET /api/mitra-vet/chart
     * Data grafik rekap aset masuk/keluar per bulan
     */
    public function chartData(Request $request)
    {
        if ($deny = $this->validateRoleAccess($request, self::MITRA_VET_ROLES)) {
            return $deny;
        }

        $year  = $request->year  ?? Carbon::now()->year;
        $month = $request->month ?? Carbon::now()->month;

        // Aset masuk: stok yang masuk dalam bulan tersebut (dari history)
        $masuk = DB::table('mitra_vet_stock_histories as h')
            ->join('mitra_vet_items as i', 'h.item_id', '=', 'i.id')
            ->select(
                DB::raw('DAY(h.created_at) as hari'),
                DB::raw('SUM(h.quantity) as total_qty'),
                DB::raw('SUM(h.quantity * i.harga_satuan) as total_nilai')
            )
            ->where('h.type', 'masuk')
            ->whereMonth('h.created_at', $month)
            ->whereYear('h.created_at', $year)
            ->groupBy(DB::raw('DAY(h.created_at)'))
            ->orderBy('hari')
            ->get();

        // Aset keluar: stok yang keluar dalam bulan tersebut
        $keluar = DB::table('mitra_vet_stock_histories as h')
            ->join('mitra_vet_items as i', 'h.item_id', '=', 'i.id')
            ->select(
                DB::raw('DAY(h.created_at) as hari'),
                DB::raw('SUM(h.quantity) as total_qty'),
                DB::raw('SUM(h.quantity * i.harga_satuan) as total_nilai')
            )
            ->where('h.type', 'keluar')
            ->whereMonth('h.created_at', $month)
            ->whereYear('h.created_at', $year)
            ->groupBy(DB::raw('DAY(h.created_at)'))
            ->orderBy('hari')
            ->get();

        // Hitung total profit untuk bulan & tahun terpilih (cash basis: proporsional uang terbayar)
        $profit_bulan = DB::table('mitra_vet_order_items as oi')
            ->join('mitra_vet_items as i', 'oi.item_id', '=', 'i.id')
            ->join('mitra_vet_orders as o', 'oi.order_id', '=', 'o.id')
            ->whereMonth('o.created_at', $month)
            ->whereYear('o.created_at', $year)
            ->selectRaw('SUM(oi.quantity * GREATEST(oi.harga_satuan - i.harga_modal, 0) * (o.grand_total - o.sisa_tagihan) / GREATEST(o.grand_total, 1)) as profit')
            ->value('profit') ?? 0;

        // Hitung total profit keseluruhan (cash basis: proporsional uang terbayar)
        $profit_keseluruhan = DB::table('mitra_vet_order_items as oi')
            ->join('mitra_vet_items as i', 'oi.item_id', '=', 'i.id')
            ->join('mitra_vet_orders as o', 'oi.order_id', '=', 'o.id')
            ->selectRaw('SUM(oi.quantity * GREATEST(oi.harga_satuan - i.harga_modal, 0) * (o.grand_total - o.sisa_tagihan) / GREATEST(o.grand_total, 1)) as profit')
            ->value('profit') ?? 0;

        // Summary total bulan ini & profit
        $summary = [
            'total_masuk_qty'          => $masuk->sum('total_qty'),
            'total_masuk_nilai'        => $masuk->sum('total_nilai'),
            'total_keluar_qty'         => $keluar->sum('total_qty'),
            'total_keluar_nilai'       => $keluar->sum('total_nilai'),
            'total_profit_bulan'       => $profit_bulan,
            'total_profit_keseluruhan' => $profit_keseluruhan,
        ];

        return response()->json([
            'masuk'   => $masuk,
            'keluar'  => $keluar,
            'summary' => $summary,
        ], 200);
    }

    // =========================================================
    //  CUSTOMER / MITRA PEMBELI
    // =========================================================

    /**
     * GET /api/mitra-vet/customers
     * Daftar customer untuk dropdown (nama, no_hp, alamat)
     */
    public function getCustomers(Request $request)
    {
        if ($deny = $this->validateRoleAccess($request, self::MITRA_VET_ROLES)) {
            return $deny;
        }

        $customers = MitraVetCustomer::where('is_deleted', false)
            ->select('id', 'nama', 'no_hp', 'alamat')
            ->orderBy('nama', 'asc')
            ->get();

        return response()->json($customers, 200);
    }

    /**
     * POST /api/mitra-vet/customers
     * Tambah customer baru secara langsung
     */
    public function createCustomer(Request $request)
    {
        if ($deny = $this->validateRoleAccess($request, self::MITRA_VET_ROLES)) {
            return $deny;
        }

        $validate = Validator::make($request->all(), [
            'nama'   => 'required|string|max:150',
            'no_hp'  => 'required|string|max:30',
            'alamat' => 'required|string',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'message' => 'Data tidak valid.',
                'errors'  => $validate->errors()->all(),
            ], 422);
        }

        $customer = MitraVetCustomer::create([
            'nama'       => trim($request->nama),
            'no_hp'      => trim($request->no_hp),
            'alamat'     => trim($request->alamat),
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'message'  => 'Pelanggan berhasil ditambahkan.',
            'customer' => $customer,
        ], 200);
    }

    // =========================================================
    //  EXPORT EXCEL ORDERAN
    // =========================================================

    /**
     * GET /api/mitra-vet/orders/export-excel
     * Download tarikan data orderan ke Excel
     * HASIL DISORT BERDASARKAN NAMA ORANG YANG ORDER
     */
    public function exportOrders(Request $request)
    {
        if ($deny = $this->validateRoleAccess($request, self::MITRA_VET_ROLES)) {
            return $deny;
        }

        $month   = $request->month;
        $year    = $request->year;
        $keyword = $request->keyword;

        $fileName = 'Laporan Orderan Mitra Vet';
        if ($year) $fileName .= " {$year}";
        if ($month) $fileName .= " Bln {$month}";
        $fileName .= '.xlsx';

        return Excel::download(new MitraVetOrderExport($month, $year, $keyword), $fileName);
    }

    /**
     * GET /api/mitra-vet/stock-histories/export-excel
     * Download log riwayat penambahan stok masuk ke Excel
     * Role: admin, admin_gudang
     */
    public function exportStockHistories(Request $request)
    {
        if ($deny = $this->validateRoleAccess($request, self::MITRA_VET_ITEM_MANAGERS, 'Hanya Admin dan Admin Gudang yang dapat mendownload log stok.')) {
            return $deny;
        }

        $month   = $request->month;
        $year    = $request->year;
        $keyword = $request->keyword;

        $fileName = 'Log Mutasi Stok Mitra Vet';
        if ($year) $fileName .= " {$year}";
        if ($month) $fileName .= " Bln {$month}";
        $fileName .= '.xlsx';

        return Excel::download(new MitraVetStockHistoryExport($month, $year, $keyword), $fileName);
    }
}
