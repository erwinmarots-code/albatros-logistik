<?php

namespace App\Exports;

class InvoicesExport extends BaseExport
{
    public function __construct($query)
    {
        parent::__construct($query, [
            'ID',
            'No Invoice',
            'Client',
            'Project (No PO)',
            'Total (Rp)',
            'Tanggal Jatuh Tempo',
            'Status',
            'Hari Terlambat',
            'Cabang'
        ]);
    }

    public function map($invoice): array
    {
        // Hitung hari terlambat
        $days = now()->diffInDays($invoice->due_date, false);
        $late = $days < 0 ? abs($days) . ' hari' : ($days == 0 ? 'Hari ini' : $days . ' hari lagi');

        return [
            $invoice->id,
            $invoice->invoice_number ?? '',
            $invoice->client->name ?? '-',
            $invoice->shippingProject->no_po ?? '-',
            number_format($invoice->total_amount ?? 0, 0, ',', '.'),
            $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '',
            ucfirst($invoice->status), // draft, sent, paid, cancelled
            $late,
            $invoice->branch->name ?? '-',
        ];
    }
}