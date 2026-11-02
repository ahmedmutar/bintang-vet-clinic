<?php

namespace App\Imports;

use App\Models\MitraVetItem;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class MitraVetItemImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use Importable, SkipsFailures;

    protected $userId;

    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    /**
     * Kolom yang diharapkan dari Excel:
     * nama_barang | satuan | harga_modal | harga_jual | stok
     */
    public function model(array $row)
    {
        $namaBarang = trim($row['nama_barang']);
        $hargaModal = (float) str_replace(',', '', $row['harga_modal']);
        $hargaJual  = (float) str_replace(',', '', $row['harga_jual']);
        $profit     = $hargaJual - $hargaModal;
        $stok       = (int) $row['stok'];

        // Cek duplikasi nama barang (case insensitive)
        $existing = MitraVetItem::whereRaw('LOWER(nama_barang) = ?', [strtolower($namaBarang)])
            ->where('is_deleted', false)
            ->first();

        if ($existing) {
            $existing->stok        += $stok;
            $existing->satuan       = trim($row['satuan']) ?: $existing->satuan;
            $existing->harga_modal  = $hargaModal;
            $existing->harga_jual   = $hargaJual;
            $existing->profit       = $profit;
            $existing->harga_satuan = $hargaJual;
            $existing->updated_by   = $this->userId;
            $existing->save();
            return null;
        }

        return new MitraVetItem([
            'nama_barang'  => $namaBarang,
            'satuan'       => trim($row['satuan']),
            'harga_modal'  => $hargaModal,
            'harga_jual'   => $hargaJual,
            'profit'       => $profit,
            'harga_satuan' => $hargaJual,
            'stok'         => $stok,
            'created_by'   => $this->userId,
        ]);
    }

    public function rules(): array
    {
        return [
            '*.nama_barang' => 'required|string|max:150',
            '*.satuan'      => 'required|string|max:50',
            '*.harga_modal' => 'required|numeric|min:0',
            '*.harga_jual'  => 'required|numeric|min:0',
            '*.stok'        => 'required|integer|min:0',
        ];
    }

    public function customValidationMessages()
    {
        return [
            '*.nama_barang.required' => 'Kolom nama_barang wajib diisi.',
            '*.satuan.required'      => 'Kolom satuan wajib diisi.',
            '*.harga_modal.required' => 'Kolom harga_modal wajib diisi.',
            '*.harga_modal.numeric'  => 'Kolom harga_modal harus berupa angka.',
            '*.harga_jual.required'  => 'Kolom harga_jual wajib diisi.',
            '*.harga_jual.numeric'   => 'Kolom harga_jual harus berupa angka.',
            '*.stok.required'        => 'Kolom stok wajib diisi.',
            '*.stok.integer'         => 'Kolom stok harus berupa angka bulat.',
            '*.stok.min'             => 'Kolom stok tidak boleh negatif.',
        ];
    }
}
