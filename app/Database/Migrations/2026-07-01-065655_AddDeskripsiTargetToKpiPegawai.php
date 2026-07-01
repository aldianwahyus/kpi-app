<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDeskripsiTargetToKpiPegawai extends Migration
{
    public function up(): void
    {
        // Tambah deskripsi_target ke kpi_pegawai (level Induk)
        $this->forge->addColumn('kpi_pegawai', [
            'deskripsi_target' => [
                'type'       => 'TEXT',
                'null'       => true,
                'default'    => null,
                'after'      => 'target',
            ],
        ]);

        // Tambah deskripsi_target ke kpi_pegawai_turunan (level Turunan)
        $this->forge->addColumn('kpi_pegawai_turunan', [
            'deskripsi_target' => [
                'type'       => 'TEXT',
                'null'       => true,
                'default'    => null,
                'after'      => 'target',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('kpi_pegawai', 'deskripsi_target');
        $this->forge->dropColumn('kpi_pegawai_turunan', 'deskripsi_target');
    }
}