<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Carbon;
use App\Models\GrainType;

class TrainingFileExcelSeeder extends Seeder
{
    public function run(): void
    {
        $importDir = storage_path('app/import');
        $files = glob("{$importDir}/*.xlsx");

        if (empty($files)) {
            Log::warning("No .xlsx files found in {$importDir}");
            echo "No .xlsx files found in {$importDir}.\n";
            return;
        }

        $grainType = GrainType::first();
        if (!$grainType) {
            throw new \RuntimeException('Grain type tidak ditemukan. Seed grain_types terlebih dahulu.');
        }
        $grain_type_id = $grainType->grain_type_id;

        foreach ($files as $file) {
            try {
                echo "Processing file: {$file}\n";
                Log::info("Processing file: {$file}");

                $sheet = IOFactory::load($file)->getActiveSheet();
                $rows  = $sheet->toArray();

                if (empty($rows)) {
                    Log::warning("File {$file} is empty or could not be read.");
                    continue;
                }

                // Buang header
                array_shift($rows);

                // Mapping (paksa interval 5 detik)
                // Kolom: 0:interval(ignored), 1:Estimasi(Menit), 2:MC, 3:T_gabah, 4:T_room, 5:T_burn, 6:weight
                $mapped = [];
                foreach ($rows as $rowIndex => $row) {
                    if (count(array_filter($row, fn($v) => $v !== null && $v !== '')) === 0) continue;

                    $mapped[] = [
                        'interval_seconds'   => $rowIndex * 5,
                        'estimasi_minutes'   => isset($row[1]) && $row[1] !== '' ? (float) $row[1] : null,
                        'grain_moisture'     => isset($row[2]) ? (float) $row[2] : null,
                        'grain_temperature'  => isset($row[3]) ? (float) $row[3] : null,
                        'room_temperature'   => isset($row[4]) ? (float) $row[4] : null,
                        'burn_temperature'   => isset($row[5]) ? (float) $row[5] : null,
                        'weight'             => isset($row[6]) ? (float) $row[6] : null,
                    ];
                }

                // Minimal valid: moisture & suhu gabah ada
                $valid = array_values(array_filter($mapped, fn($r) =>
                    $r['grain_moisture'] !== null && $r['grain_temperature'] !== null
                ));
                if (empty($valid)) {
                    Log::warning("No valid data with required fields in {$file}.");
                    continue;
                }

                usort($valid, fn($a, $b) => $a['interval_seconds'] <=> $b['interval_seconds']);

                $first = $valid[0];
                $last  = $valid[count($valid) - 1];

                // Estimasi akhir (ambil dari baris valid terakhir yang punya estimasi_minutes)
                $durasiAkhirMenit = null;
                for ($i = count($valid) - 1; $i >= 0; $i--) {
                    if ($valid[$i]['estimasi_minutes'] !== null) {
                        $durasiAkhirMenit = $this->dec7($valid[$i]['estimasi_minutes']);
                        break;
                    }
                }

                // Massa awal & akhir (first/last non-null)
                $massaAwal = null;
                foreach ($valid as $r) { if ($r['weight'] !== null) { $massaAwal = $r['weight']; break; } }
                $massaAkhir = null;
                for ($i = count($valid) - 1; $i >= 0; $i--) { if ($valid[$i]['weight'] !== null) { $massaAkhir = $valid[$i]['weight']; break; } }

                $baseTime = Carbon::now()->floorSecond();

                DB::transaction(function () use ($valid, $grain_type_id, $baseTime, $first, $last, $durasiAkhirMenit, $massaAwal, $massaAkhir) {
                    // 1) training_group
                    $groupId = DB::table('training_group')->insertGetId([
                        'grain_type_id'    => $grain_type_id,
                        'kadar_air_awal'   => $this->dec7($first['grain_moisture']),
                        'kadar_air_akhir'  => $this->dec7($last['grain_moisture']),
                        'target_kadar_air' => $this->dec7($last['grain_moisture']),
                        'massa_awal'       => $massaAwal,
                        'massa_akhir'      => $massaAkhir,
                        'durasi_aktual'    => $durasiAkhirMenit, // dari Estimasi(Menit) baris terakhir yang tersedia
                        'created_at'       => now(),
                        'updated_at'       => now(),
                    ], 'group_id');

                    // 2) training_data rows
                    $batch = [];
                    foreach ($valid as $item) {
                        $ts = $baseTime->copy()->addSeconds($item['interval_seconds']);

                        $batch[] = [
                            'group_id'        => $groupId,
                            'timestamp'       => $ts,
                            'kadar_air_gabah' => $this->dec7($item['grain_moisture']),
                            'suhu_gabah'      => $this->dec7($item['grain_temperature']),
                            'suhu_ruangan'    => $this->dec7($item['room_temperature']),
                            'suhu_pembakaran' => $this->dec7($item['burn_temperature']),
                            'status_pengaduk' => false,
                            // durasi_aktual per baris = Estimasi(Menit) dari Excel (boleh null jika kosong)
                            'durasi_aktual'   => $item['estimasi_minutes'] !== null ? $this->dec7($item['estimasi_minutes']) : null,
                            'created_at'      => now(),
                            'updated_at'      => now(),
                        ];
                    }

                    foreach (array_chunk($batch, 1000) as $chunk) {
                        DB::table('training_data')->insert($chunk);
                    }
                });

                echo "Inserted ".count($valid)." dataset rows + 1 training_group record.\n";
                Log::info("Inserted ".count($valid)." dataset rows + 1 training_group record.");

            } catch (\Exception $e) {
                // Log::error("Failed to process file {$file}: {$e->getMessage()}");
                // echo "Failed to process file {$file}: {$e->getMessage()}\n";
                continue;
            }
        }

        echo "Import to training_data & training_group completed.\n";
        Log::info("Import to training_data & training_group completed.");
    }

    /** round ke 7 desimal (NUMERIC(10,7) akan menyimpan sebagai 7 digit) */
    private function dec7($value)
    {
        return $value === null ? null : round((float)$value, 7);
    }
}
