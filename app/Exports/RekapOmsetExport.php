<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Illuminate\Support\Collection;

class RekapOmsetExport implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle
{
    protected $rows;
    protected $headingRow;
    protected $periodeLabel;

    public function __construct(array $rows, array $headingRow, string $periodeLabel)
    {
        $this->rows         = $rows;
        $this->headingRow   = $headingRow;
        $this->periodeLabel = $periodeLabel;
    }

    public function collection(): Collection
    {
        return collect($this->rows);
    }

    public function headings(): array
    {
        return [$this->headingRow];
    }

    public function title(): string
    {
        return 'Rekap Omset ' . $this->periodeLabel;
    }
}
