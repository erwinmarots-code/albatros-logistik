<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Database\Eloquent\Builder;

abstract class BaseExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected Builder $query;
    protected array $headings;

    public function __construct(Builder $query, array $headings)
    {
        $this->query = $query;
        $this->headings = $headings;
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E2E8F0']],
                'alignment' => ['horizontal' => 'center'],
            ],
        ];
    }

    // Wajib diisi oleh child class
    abstract public function map($row): array;
}