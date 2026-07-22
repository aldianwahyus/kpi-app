<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPolarityTypesToKpiUnit extends Migration
{
    public function up(): void
    {
        // Perluas ENUM polarity dari 2 nilai (max/min) menjadi 5, menambah
        // 'precise' (Precise is Better), 'special' (Special Scoring), dan
        // 'tertimbang' (Scoring Tertimbang) sesuai skema penilaian baru.
        $this->db->query(
            "ALTER TABLE kpi_unit MODIFY polarity
             ENUM('max','min','precise','special','tertimbang') NOT NULL DEFAULT 'max'"
        );

        $this->forge->addColumn('kpi_unit', [
            // Precise is Better: toleransi deviasi (%) dari target, simetris
            // di atas & di bawah 100% — satu nilai per band Skor 4/3/2.
            'toleransi_skor4' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
                'default'    => null,
                'after'      => 'perubahan_polarity',
            ],
            'toleransi_skor3' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
                'default'    => null,
                'after'      => 'toleransi_skor4',
            ],
            'toleransi_skor2' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
                'default'    => null,
                'after'      => 'toleransi_skor3',
            ],
            // Special Scoring: sifat menentukan arah skor untuk kejadian
            // "Ada"/"Tidak Ada" — maximize: Ada=4/TidakAda=1, minimize kebalikannya.
            'sifat_khusus' => [
                'type'       => 'ENUM',
                'constraint' => ['maximize', 'minimize'],
                'null'       => true,
                'default'    => null,
                'after'      => 'toleransi_skor2',
            ],
            // Scoring Tertimbang: target Indikator 2 (Rata-rata Harian).
            // Target Indikator 1 (Posisi Akhir) memakai kolom `target` yang
            // sudah ada.
            'target_harian' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'null'       => true,
                'default'    => null,
                'after'      => 'sifat_khusus',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('kpi_unit', [
            'toleransi_skor4', 'toleransi_skor3', 'toleransi_skor2',
            'sifat_khusus', 'target_harian',
        ]);
        $this->db->query(
            "ALTER TABLE kpi_unit MODIFY polarity ENUM('max','min') NOT NULL DEFAULT 'max'"
        );
    }
}
