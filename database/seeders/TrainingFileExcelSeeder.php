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

                // Validate header (expected columns, starting from index 1)
                $expectedColumns = [
                    0 => 'interval',
                    1 => 'estimasi_menit',
                    2 => 'jenis_gabah_id',
                    3 => 'kadar_air_gabah',
                    4 => 'suhu_gabah',
                    5 => 'suhu_ruangan',
                    6 => 'suhu_pembakaran',
                    7 => 'massa_gabah',
                    8 => 'status_pengaduk',
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
                        'interval' => isset($row[0]) ? $row[0] : null, // Interval (not stored in DB)
                        'estimasi_menit' => isset($row[1]) ? (int) $row[1] : null, // Estimasi (menit)
                        'jenis_gabah_id' => isset($row[2]) ? (int) $row[2] : null, // Jenis_Gabah_ID
                        'kadar_air_gabah' => isset($row[3]) ? (float) $row[3] : null, // Kadar Air Gabah (%)
                        'suhu_gabah' => isset($row[4]) ? (float) $row[4] : null, // Suhu Gabah (°C)
                        'suhu_ruangan' => isset($row[5]) ? (float) $row[5] : null, // Suhu Ruangan (°C)
                        'suhu_pembakaran' => isset($row[6]) ? (float) $row[6] : null, // Suhu Pembakaran (°C)
                        'massa_gabah' => isset($row[7]) ? (float) $row[7] : null, // Massa Gabah (Kg)
                        'status_pengaduk' => isset($row[8]) ? (bool) $row[8] : false, // Status Pengaduk (0/1)
                    ];
                }

                // Skip if no valid data
                if (empty($mapped)) {
                    Log::warning("No valid data extracted from {$file}.");
                    echo "No valid data extracted from {$file}.\n";
                    continue;
                }

                // Group by estimasi_menit
                $grouped = collect($mapped)->groupBy('estimasi_menit');

                // Process within a transaction
                DB::transaction(function () use ($grouped, $file) {
                    foreach ($grouped as $estimasi_menit => $items) {
                        // Skip if estimasi_menit is null or invalid
                        if ($estimasi_menit === null || $estimasi_menit < 0) {
                            Log::warning("Invalid estimasi_menit ({$estimasi_menit}) in {$file}, skipping group.");
                            continue;
                        }

                        // Create TrainingGroup
                        $group = TrainingGroup::create([
                            'drying_time' => $estimasi_menit, // In minutes
                            'process_id' => null, // Set if needed
                        ]);

                        foreach ($items as $item) {
                            // Validate required fields
                            if (is_null($item['kadar_air_gabah']) || 
                                is_null($item['suhu_gabah']) || 
                                is_null($item['suhu_ruangan']) || 
                                is_null($item['jenis_gabah_id'])) {
                                Log::warning("Skipping invalid data in {$file} for estimasi_menit {$estimasi_menit}: " . json_encode($item));
                                continue;
                            }

                            // Verify jenis_gabah_id exists in grain_types
                            if (!\App\Models\GrainType::where('grain_type_id', $item['jenis_gabah_id'])->exists()) {
                                Log::warning("Invalid jenis_gabah_id ({$item['jenis_gabah_id']}) in {$file}, skipping row.");
                                continue;
                            }

                            TrainingData::create([
                                'training_group_id' => $group->id,
                                'jenis_gabah_id' => $item['jenis_gabah_id'],
                                'kadar_air_gabah' => $item['kadar_air_gabah'],
                                'suhu_gabah' => $item['suhu_gabah'],
                                'suhu_ruangan' => $item['suhu_ruangan'],
                                'suhu_pembakaran' => $item['suhu_pembakaran'],
                                'massa_gabah' => $item['massa_gabah'] ?? config('app.default_weight', 0),
                                'status_pengaduk' => $item['status_pengaduk'],
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