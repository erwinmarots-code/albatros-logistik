<?php

namespace App\Exports;

use App\Models\FinancialTransaction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class FinancialTransactionsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $from;
    protected $to;

    public function __construct($from = null, $to = null)
    {
        $this->from = $from;
        $this->to = $to;
    }

    public function collection()
    {
        $query = FinancialTransaction::with(['vehicle', 'client', 'driver']);

        if ($this->from) {
            $query->whereDate('transaction_date', '>=', $this->from);
        }
        if ($this->to) {
            $query->whereDate('transaction_date', '<=', $this->to);
        }

        return $query->orderBy('transaction_date', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Tanggal',
            'Tipe',
            'Kategori',
            'Nominal (Rp)',
            'Deskripsi',
            'Kendaraan',
            'Driver',
            'Client',
            'Status',
            'Referensi',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->transaction_date,
            $row->type === 'income' ? 'Pemasukan' : 'Pengeluaran',
            $row->category,
            number_format($row->amount, 0, ',', '.'),
            $row->description ?? '-',
            $row->vehicle->plate_number ?? '-',
            $row->driver->name ?? '-',
            $row->client->name ?? '-',
            $row->status,
            $row->reference_type ? $row->reference_type . ' #' . $row->reference_id : '-',
        ];
    }
}