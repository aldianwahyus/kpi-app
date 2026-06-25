<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTargetAndCappedToKpiUnit extends Migration
{
    public function up()
    {
        // Menambahkan kolom baru ke tabel kpi_unit
        $fields = [
            'target' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 100.00,
                'after'      => 'satuan' // diletakkan setelah kolom satuan
            ],
            'is_capped' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'after'      => 'polarity' // diletakkan setelah kolom polarity
            ],
        ];

        $this->forge->addColumn('kpi_unit', $fields);
    }

    public function down()
    {
        // Menghapus kembali kolom jika migrasi di-rollback
        $this->forge->dropColumn('kpi_unit', ['target', 'is_capped']);
    }
}