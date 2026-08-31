<?php

namespace App\Imports;

use App\Models\Vehicle;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class VehiclesImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new Vehicle([
            'plate_number' => $row['plat_nomor'] ?? $row['plate_number'],
            'brand'        => $row['merek'] ?? $row['brand'] ?? null,
            'model'        => $row['model'] ?? null,
            'year'         => $row['tahun'] ?? $row['year'] ?? null,
            'capacity'     => $row['kapasitas'] ?? $row['capacity'] ?? null,
            'status'       => $row['status'] ?? 'available',
        ]);
    }
}