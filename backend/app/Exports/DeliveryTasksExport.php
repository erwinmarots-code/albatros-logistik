<?php

namespace App\Exports;

class DeliveryTasksExport extends BaseExport
{
    public function __construct($query)
    {
        parent::__construct($query, [
            'ID',
            'No Resi',
            'Project (No PO)',
            'Kendaraan',
            'Driver',
            'Client',
            'Tanggal Kirim',
            'Status',
            'Catatan',
            'Cabang'
        ]);
    }

    public function map($task): array
    {
        return [
            $task->id,
            $task->no_resi ?? '',
            $task->project->no_po ?? '-',
            $task->vehicle->plate_number ?? '-',
            $task->driver->name ?? '-',
            $task->client->name ?? '-',
            $task->delivery_date ? $task->delivery_date->format('d/m/Y') : '',
            ucfirst($task->status),
            $task->notes ?? '',
            $task->branch->name ?? '-',
        ];
    }
}