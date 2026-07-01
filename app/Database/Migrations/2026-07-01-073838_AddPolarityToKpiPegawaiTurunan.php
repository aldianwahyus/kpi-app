<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPolarityToKpiPegawaiTurunan extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('kpi_pegawai_turunan', [
            'polarity' => [
                'type'       => 'ENUM',
                'constraint' => ['max', 'min'],
                'default'    => 'max',
                'after'      => 'target',
            ],
            'perubahan_polarity' => [
                'type'       => 'ENUM',
                'constraint' => ['pos', 'neg'],
                'default'    => 'pos',
                'after'      => 'polarity',
            ],
            'satuan' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'default'    => null,
                'after'      => 'perubahan_polarity',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('kpi_pegawai_turunan', 'polarity');
        $this->forge->dropColumn('kpi_pegawai_turunan', 'perubahan_polarity');
        $this->forge->dropColumn('kpi_pegawai_turunan', 'satuan');
    }
}