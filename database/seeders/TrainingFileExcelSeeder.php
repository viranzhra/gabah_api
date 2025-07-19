<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\TrainingGroup;
use App\Models\TrainingData;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class TrainingFileExcelSeeder extends Seeder
{
    public function run(): void
    {
        // Define the directory for Excel files
        $importDir = storage_path('app/import');
        $files = glob("{$importDir}/*.xlsx");

        if (empty($files)) {
            Log::warning("No .xlsx files found in {$importDir}");
            echo "No .xlsx files found in {$importDir}.\n";
            return;
        }

        foreach ($files as $file) {
            try {
                echo "Processing file: {$file}\n";
                Log::info("Processing file: {$file}");

                // Load Excel file
                $sheet = Excel::toArray([], $file)[0]; // Get first sheet

                // Validate that sheet has data
                if (empty($sheet)) {
                    Log::warning("File {$file} is empty or could not be read.");
                    echo "File {$file} is empty or could not be read.\n";
                    continue;
                }

                // Get and remove header
                $header = array_shift($sheet);

                // Validate header (expected columns)
                $expectedColumns = [
                    1 => 'drying_time',
                    2 => 'grain_moisture',
                    3 => 'grain_temperature',
                    4 => 'room_temperature',
                    5 => 'combustion_temperature',
                    6 => 'weight'
                ];
                if (count($header) < count($expectedColumns)) {
                    Log::warning("File {$file} has fewer columns than expected.");
                    echo "File {$file} has fewer columns than expected.\n";
                    continue;
                }

                // Map rows to data
                $mapped = [];
                foreach ($sheet as $rowIndex => $row) {
                    // Ensure row has enough columns
                    if (count($row) < count($expectedColumns)) {
                        Log::warning("Row " . ($rowIndex + 2) . " in {$file} has missing columns.");
                        continue;
                    }

                    $mapped[] = [
                        'drying_time'           => isset($row[1]) ? (int) $row[1] : null, // Minutes
                        'grain_moisture'        => isset($row[2]) ? (float) $row[2] : null,
                        'grain_temperature'     => isset($row[3]) ? (float) $row[3] : null,
                        'room_temperature'      => isset($row[4]) ? (float) $row[4] : null,
                        'combustion_temperature'=> isset($row[5]) ? (float) $row[5] : null,
                        'weight'                => isset($row[6]) ? (float) $row[6] : null,
                    ];
                }

                // Skip if no valid data
                if (empty($mapped)) {
                    Log::warning("No valid data extracted from {$file}.");
                    echo "No valid data extracted from {$file}.\n";
                    continue;
                }

                // Group by drying_time
                $grouped = collect($mapped)->groupBy('drying_time');

                // Process within a transaction
                DB::transaction(function () use ($grouped, $file) {
                    foreach ($grouped as $drying_time => $items) {
                        // Skip if drying_time is null or invalid
                        if ($drying_time === null || $drying_time < 0) {
                            Log::warning("Invalid drying_time ({$drying_time}) in {$file}, skipping group.");
                            continue;
                        }

                        // Create TrainingGroup
                        $group = TrainingGroup::create([
                            'drying_time' => $drying_time, // In minutes
                            'process_id' => null, // Set if needed
                        ]);

                        foreach ($items as $item) {
                            // Validate required fields
                            if (is_null($item['grain_moisture']) || is_null($item['grain_temperature']) || is_null($item['room_temperature'])) {
                                Log::warning("Skipping invalid data in {$file} for drying_time {$drying_time}: " . json_encode($item));
                                continue;
                            }

                            TrainingData::create([
                                'training_group_id'     => $group->id,
                                'grain_temperature'     => $item['grain_temperature'],
                                'grain_moisture'        => $item['grain_moisture'],
                                'room_temperature'      => $item['room_temperature'],
                                'combustion_temperature'=> $item['combustion_temperature'],
                                'weight'                => $item['weight'] ?? CONFIG['DEFAULT_WEIGHT'],
                            ]);
                        }
                    }
                });

                Log::info("Successfully imported data from {$file}.");
                echo "Successfully imported data from {$file}.\n";

            } catch (\Exception $e) {
                Log::error("Failed to process file {$file}: {$e->getMessage()}");
                echo "Failed to process file {$file}: {$e->getMessage()}\n";
                continue; // Continue with next file
            }
        }

        echo "Import process completed.\n";
        Log::info("Import process completed.");
    }
}