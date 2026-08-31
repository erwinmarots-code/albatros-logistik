<?php

namespace App\Exports;

class DriversExport extends BaseExport
{
    public function __construct($query)
    {
        parent::__construct($query, [
            'ID',
            'Nama Driver',
            'No. SIM',
            'Telepon',
            'Status',
            'Cabang'
        ]);
    }

    public function map($driver): array
    {
        return [
            $driver->id,
            $driver->name,
            $driver->license_number ?? '',
            $driver->phone ?? '',
            ucfirst($driver->status), // available, unavailable
            $driver->branch->name ?? '-',
        ];
    }
}