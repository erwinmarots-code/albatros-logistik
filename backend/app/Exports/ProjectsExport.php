<?php

namespace App\Exports;

class ProjectsExport extends BaseExport
{
    public function __construct($query)
    {
        parent::__construct($query, [
            'ID',
            'No PO',
            'No Resi',
            'Client',
            'Cabang',
            'Status',
            'Berat (kg)',
            'Collie',
            'Volumetrik',
            'Nilai Barang (Rp)',
            'Nilai Kontrak (Rp)',
            'Metode Kirim',
            'Pengirim',
            'Penerima',
            'Catatan',
            'Dibuat Pada'
        ]);
    }

    public function map($project): array
    {
        return [
            $project->id,
            $project->no_po,
            $project->no_resi ?? '',
            $project->client->name ?? '-',
            $project->branch->name ?? '-',
            ucfirst($project->status),
            $project->weight_kg ?? 0,
            $project->collie ?? 0,
            $project->volumetric ?? 0,
            number_format($project->goods_value ?? 0, 0, ',', '.'),
            number_format($project->contract_value ?? 0, 0, ',', '.'),
            $project->shipping_method ?? '',
            $project->sender_name ?? '',
            $project->receiver_name ?? '',
            $project->notes ?? '',
            $project->created_at->format('d/m/Y H:i'),
        ];
    }
}