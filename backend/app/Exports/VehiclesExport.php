<?php

namespace App\Exports;

use App\Models\Vehicle;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class VehiclesExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Vehicle::all(['plate_number', 'brand', 'model', 'year', 'color', 'engine_capacity', 'fuel_type', 'status', 'price']);
    }

    public function headings(): array
    {
        return ['No. Polisi', 'Merek', 'Model', 'Tahun', 'Warna', 'Kapasitas Mesin', 'BBM', 'Status', 'Harga'];
    }
}