<?php

namespace App\Imports;

use App\Models\DeliveryTask;
use App\Models\Project;
use App\Models\Vehicle;
use App\Models\Driver;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;

class DeliveryTasksImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    public function model(array $row)
    {
        $project = Project::where('no_po', $row['no_po'])->first();
        $vehicle = Vehicle::where('plate_number', $row['plate_number'])->first();
        $driver = Driver::where('name', $row['driver_name'])->first();

        if (!$project || !$vehicle || !$driver) {
            return null;
        }

        return new DeliveryTask([
            'project_id' => $project->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'tanggal' => $row['tanggal'],
            'status' => $row['status'] ?? 'pending',
            'catatan' => $row['catatan'] ?? null,
        ]);
    }

    public function rules(): array
    {
        return [
            'no_po' => 'required|string|exists:projects,no_po',
            'plate_number' => 'required|string|exists:vehicles,plate_number',
            'driver_name' => 'required|string|exists:drivers,name',
            'tanggal' => 'required|date',
        ];
    }
}