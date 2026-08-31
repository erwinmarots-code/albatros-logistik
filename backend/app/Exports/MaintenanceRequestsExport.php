<?php

namespace App\Exports;

class MaintenanceRequestsExport extends BaseExport
{
    public function __construct($query)
    {
        parent::__construct($query, [
            'ID',
            'Kode Request',
            'Kendaraan (Plat)',
            'Driver',
            'Tipe Service',
            'Urgency',
            'Tanggal Pengajuan',
            'Tanggal Service',
            'Status',
            'Estimasi Biaya (Rp)',
            'Biaya Aktual (Rp)',
            'Deskripsi',
            'Disetujui Oleh',
            'Dieksekusi Oleh',
            'Cabang'
        ]);
    }

    public function map($item): array
    {
        return [
            $item->id,
            $item->request_code ?? '',
            $item->vehicle->plate_number ?? '-',
            $item->driver->name ?? '-',
            $this->getServiceTypeLabel($item->service_type ?? ''),
            $this->getUrgencyLabel($item->urgency ?? ''),
            $item->request_date ? $item->request_date->format('d/m/Y') : '',
            $item->scheduled_date ? $item->scheduled_date->format('d/m/Y') : '',
            $this->getStatusLabel($item->status ?? ''),
            number_format($item->estimated_cost ?? 0, 0, ',', '.'),
            number_format($item->actual_cost ?? 0, 0, ',', '.'),
            $item->description ?? '',
            $item->approver->name ?? '-',
            $item->executor->name ?? '-',
            $item->branch->name ?? '-',
        ];
    }

    private function getServiceTypeLabel($type)
    {
        $labels = [
            'oil_change' => 'Ganti Oli',
            'tire_replacement' => 'Ganti Ban',
            'sparepart' => 'Sparepart',
            'general' => 'General Service',
            'other' => 'Lainnya'
        ];
        return $labels[$type] ?? $type;
    }

    private function getUrgencyLabel($urgency)
    {
        $labels = [
            'low' => 'Rendah',
            'medium' => 'Sedang',
            'high' => 'Tinggi'
        ];
        return $labels[$urgency] ?? $urgency;
    }

    private function getStatusLabel($status)
    {
        $labels = [
            'draft' => 'Draft',
            'pending' => 'Pending',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'done' => 'Selesai'
        ];
        return $labels[$status] ?? $status;
    }
}