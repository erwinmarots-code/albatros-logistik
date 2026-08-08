<?php

namespace App\Exports;

use App\Models\Client;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ClientsExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Client::all(['name', 'phone', 'email', 'address', 'pic_name']);
    }

    public function headings(): array
    {
        return ['Nama Client', 'Telepon', 'Email', 'Alamat', 'PIC'];
    }
}