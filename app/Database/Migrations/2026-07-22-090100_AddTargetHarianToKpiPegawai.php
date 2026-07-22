<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTargetHarianToKpiPegawai extends Migration
{
    public function up(): void
    {
        // Override per-pegawai untuk Target Indikator 2 (Rata-rata Harian)
        // pada polarity 'tertimbang' — mengikuti pola kolom `target` yang
        // sudah ada (IFNULL(kp.target_harian, k.target_harian) di query join).
        $this->forge->addColumn('kpi_pegawai', [
            'target_harian' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'null'       => true,
                'default'    => null,
                'after'      => 'target',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('kpi_pegawai', 'target_harian');
    }
}
