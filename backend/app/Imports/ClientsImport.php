<?php

namespace App\Imports;

use App\Models\Client;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ClientsImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        return new Client([
            'name'    => $row['nama'] ?? $row['name'],
            'phone'   => $row['telepon'] ?? $row['phone'] ?? null,
            'email'   => $row['email'] ?? null,
            'address' => $row['alamat'] ?? $row['address'] ?? null,
            'npwp'    => $row['npwp'] ?? null,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
        ];
    }
}