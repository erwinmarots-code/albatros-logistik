<?php

namespace App\Imports;

use App\Models\Driver;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DriversImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new Driver([
            'name'            => $row['nama'] ?? $row['name'],
            'phone'           => $row['telepon'] ?? $row['phone'] ?? null,
            'license_number'  => $row['no_sim'] ?? $row['license_number'] ?? null,
            'address'         => $row['alamat'] ?? $row['address'] ?? null,
            'status'          => $row['status'] ?? 'available',
        ]);
    }
}