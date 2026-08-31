<?php

namespace App\Exports;

class FuelExpensesExport extends BaseExport
{
    public function __construct($query)
    {
        parent::__construct($query, [
            'ID',
            'Kode Unik',
            'Tugas Kirim (Resi)',
            'Kendaraan',
            'Driver',
            'Jenis Biaya',
            'Tanggal',
            'Nominal (Rp)',
            'Deskripsi',
            'Status',
            'Disetujui Oleh',
            'Cabang'
        ]);
    }

    public function map($expense): array
    {
        return [
            $expense->id,
            $expense->unique_code ?? '',
            $expense->deliveryTask->no_resi ?? '-',
            $expense->vehicle->plate_number ?? '-',
            $expense->driver->name ?? '-',
            $expense->type ?? '', // fuel, toll, parking, etc
            $expense->expense_date ? $expense->expense_date->format('d/m/Y') : '',
            number_format($expense->amount ?? 0, 0, ',', '.'),
            $expense->description ?? '',
            ucfirst($expense->status), // pending, approved, rejected
            $expense->approver->name ?? '-',
            $expense->branch->name ?? '-',
        ];
    }
}