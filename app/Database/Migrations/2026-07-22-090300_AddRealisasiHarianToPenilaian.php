<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRealisasiHarianToPenilaian extends Migration
{
    public function up(): void
    {
        // Realisasi Indikator 2 (Rata-rata Harian) untuk polarity
        // 'tertimbang' — kolom `realisasi` yang sudah ada dipakai untuk
        // Indikator 1 (Posisi Akhir).
        $this->forge->addColumn('penilaian', [
            'realisasi_harian' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,4',
                'null'       => true,
                'default'    => null,
                'after'      => 'realisasi',
            ],
        ]);

        $this->forge->addColumn('penilaian_turunan', [
            'realisasi_harian' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,4',
                'null'       => true,
                'default'    => null,
                'after'      => 'realisasi',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('penilaian', 'realisasi_harian');
        $this->forge->dropColumn('penilaian_turunan', 'realisasi_harian');
    }
}
