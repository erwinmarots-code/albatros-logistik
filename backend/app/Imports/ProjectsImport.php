<?php

namespace App\Imports;

use App\Models\Project;
use App\Models\Client;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;

class ProjectsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    public function model(array $row)
    {
        // Cari client_id berdasarkan nama client (asumsi ada kolom 'client_name')
        $client = Client::where('name', $row['client_name'])->first();
        if (!$client) {
            return null; // akan skip jika client tidak ditemukan
        }

        return new Project([
            'no_po' => $row['no_po'],
            'no_resi' => $row['no_resi'] ?? 'UPG-' . date('my') . '-' . rand(100,999),
            'client_id' => $client->id,
            'nilai_project' => $row['nilai_project'] ?? null,
            'status' => $row['status'] ?? 'draft',
        ]);
    }

    public function rules(): array
    {
        return [
            'no_po' => 'required|string|unique:projects,no_po',
            'client_name' => 'required|string|exists:clients,name',
        ];
    }
}