<?php

namespace App\Exports;

class VehiclesExport extends BaseExport
{
    public function __construct($query)
    {
        parent::__construct($query, [
            'ID',
            'No. Polisi',
            'Brand',
            'Model',
            'Tahun',
            'Status',
            'Cabang'
        ]);
    }

    public function map($vehicle): array
    {
        return [
            $vehicle->id,
            $vehicle->plate_number,
            $vehicle->brand,
            $vehicle->model,
            $vehicle->year,
            ucfirst($vehicle->status), // active, maintenance, inactive
            $vehicle->branch->name ?? '-',
        ];
    }
}