<?php

namespace App\Exports;

use App\Models\Driver;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DriversExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Driver::all(['name', 'phone', 'license_number', 'address', 'status']);
    }

    public function headings(): array
    {
        return ['Nama', 'Telepon', 'Nomor SIM', 'Alamat', 'Status'];
    }
}