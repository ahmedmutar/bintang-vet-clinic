<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\Exportable;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class MitraVetItemTemplate implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    use Exportable;

    public function array(): array
    {
        // Contoh data: nama_barang, satuan, harga_modal, harga_jual, stok
        return [
            ['Paket Vitamin A', 'Botol', 30000, 50000, 100],
            ['Obat Cacing Kucing', 'Strip', 15000, 25000, 50],
            ['Shampo Anjing', 'Pcs', 20000, 35000, 30],
        ];
    }

    public function headings(): array
    {
        return [
            'nama_barang',
            'satuan',
            'harga_modal',
            'harga_jual',
            'stok',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Style header (A1:E1)
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1a6596'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // Style baris contoh (abu-abu muda)
        $sheet->getStyle('A2:E4')->applyFromArray([
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F2F2F2'],
            ],
        ]);

        // Tambahkan komentar instruksi di baris 1
        $sheet->getComment('A1')->getText()->createTextRun(
            'Isi nama barang. Jika nama sudah ada, stok akan ditambahkan.'
        );
        $sheet->getComment('C1')->getText()->createTextRun(
            'Isi harga modal / modal beli (angka, tanpa titik/koma).'
        );
        $sheet->getComment('D1')->getText()->createTextRun(
            'Isi harga jual (angka, tanpa titik/koma). HARUS LEBIH BESAR DARI HARGA MODAL.'
        );
        $sheet->getComment('E1')->getText()->createTextRun(
            'Isi jumlah stok awal (angka bulat, tidak boleh negatif).'
        );

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 15,
            'C' => 18,
            'D' => 18,
            'E' => 12,
        ];
    }
}
