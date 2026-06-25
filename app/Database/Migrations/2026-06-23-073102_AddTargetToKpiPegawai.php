<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTargetToKpiPegawai extends Migration
{
    public function up()
    {
        // Menambahkan kolom target setelah kolom bobot
        $fields = [
            'target' => [
                'type'    => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 100.00,
                'null'    => false,
                'after'   => 'bobot' // Meletakkan posisi kolom setelah 'bobot'
            ],
        ];
        
        $this->forge->addColumn('kpi_pegawai', $fields);
    }

    public function down()
    {
        // Untuk rollback / membatalkan jika terjadi kesalahan
        $this->forge->dropColumn('kpi_pegawai', 'target');
    }
}