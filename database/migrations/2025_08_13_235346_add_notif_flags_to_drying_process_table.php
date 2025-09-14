<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('drying_process', function (Blueprint $table) {
            // Tambahkan kolom kalau belum ada (idempoten)
            if (!Schema::hasColumn('drying_process', 'notif_target_sent')) {
                $table->boolean('notif_target_sent')->default(false);
            }
            if (!Schema::hasColumn('drying_process', 'notif_15m_sent')) {
                $table->boolean('notif_15m_sent')->default(false);
            }
            if (!Schema::hasColumn('drying_process', 'notif_5m_sent')) {
                $table->boolean('notif_5m_sent')->default(false);
            }

            // JANGAN buat index di sini pakai $table->index([...]) karena bisa duplikat
        });

        // Jika tetap ingin memastikan index gabungan ada, pakai IF NOT EXISTS (Postgres):
        DB::statement('CREATE INDEX IF NOT EXISTS drying_process_dryer_id_status_index ON drying_process (dryer_id, status)');
    }

    public function down(): void
    {
        // Hapus kolom jika ada
        Schema::table('drying_process', function (Blueprint $table) {
            if (Schema::hasColumn('drying_process', 'notif_target_sent')) {
                $table->dropColumn('notif_target_sent');
            }
            if (Schema::hasColumn('drying_process', 'notif_15m_sent')) {
                $table->dropColumn('notif_15m_sent');
            }
            if (Schema::hasColumn('drying_process', 'notif_5m_sent')) {
                $table->dropColumn('notif_5m_sent');
            }
        });

        // JANGAN drop index kalau mungkin dibuat oleh migrasi lain.
        // Kalau mau, aman-aman saja menambahkan ini:
        // DB::statement('DROP INDEX IF EXISTS drying_process_dryer_id_status_index');
    }
};
