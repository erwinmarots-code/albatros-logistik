<?php

namespace App\Exports;

class ClientsExport extends BaseExport
{
    public function __construct($query)
    {
        parent::__construct($query, [
            'ID',
            'Nama Client',
            'Alamat',
            'Telepon',
            'Email',
            'NPWP',
            'Cabang',
            'Dibuat Pada'
        ]);
    }

    public function map($client): array
    {
        return [
            $client->id,
            $client->name,
            $client->address ?? '',
            $client->phone ?? '',
            $client->email ?? '',
            $client->npwp ?? '',
            $client->branch->name ?? '-',
            $client->created_at->format('d/m/Y H:i'),
        ];
    }
}