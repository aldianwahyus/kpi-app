<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropTargetHarianDariTertimbang extends Migration
{
    public function up(): void
    {
        // Koreksi rumus Scoring Tertimbang: Indikator 2 (Rata-rata Harian)
        // ternyata BUKAN rasio realisasi/target seperti Indikator 1, tapi
        // langsung berupa persentase rata-rata pencapaian harian selama
        // periode penilaian (dihitung di luar sistem), yang dimasukkan
        // apa adanya ke tabel Pengkali. Kolom target_harian jadi tidak
        // relevan sama sekali — dihapus dari ketiga tabel.
        $this->forge->dropColumn('kpi_unit', 'target_harian');
        $this->forge->dropColumn('kpi_pegawai', 'target_harian');
        $this->forge->dropColumn('kpi_pegawai_turunan', 'target_harian');
    }

    public function down(): void
    {
        $this->forge->addColumn('kpi_unit', [
            'target_harian' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true, 'default' => null, 'after' => 'sifat_khusus'],
        ]);
        $this->forge->addColumn('kpi_pegawai', [
            'target_harian' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true, 'default' => null, 'after' => 'target'],
        ]);
        $this->forge->addColumn('kpi_pegawai_turunan', [
            'target_harian' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true, 'default' => null, 'after' => 'sifat_khusus'],
        ]);
    }
}
