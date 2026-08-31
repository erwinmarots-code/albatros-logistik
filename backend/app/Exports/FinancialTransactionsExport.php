<?php

namespace App\Exports;

class FinancialTransactionsExport extends BaseExport
{
    public function __construct($query)
    {
        parent::__construct($query, [
            'ID',
            'Tanggal',
            'Tipe',
            'Kategori',
            'Jumlah (Rp)',
            'Deskripsi',
            'PO / Resi',
            'No Maintenance',
            'Status',
            'Kendaraan',
            'Driver',
            'Client',
            'Cabang'
        ]);
    }

    public function map($trx): array
    {
        return [
            $trx->id,
            $trx->transaction_date->format('d/m/Y'),
            ucfirst($trx->type), // income / expense
            $trx->category ?? '',
            number_format($trx->amount ?? 0, 0, ',', '.'),
            $trx->description ?? '',
            $trx->po_number ?? '',
            $trx->maintenance_number ?? '',
            ucfirst($trx->status ?? ''),
            $trx->vehicle->plate_number ?? '-',
            $trx->driver->name ?? '-',
            $trx->client->name ?? '-',
            $trx->branch->name ?? '-',
        ];
    }
}