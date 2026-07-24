<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Sama seperti kpi_pegawai_target_bulanan, untuk Parameter Turunan.
 */
class CreateTableKpiPegawaiTurunanTargetBulanan extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                     => ['type' => 'INT', 'auto_increment' => true],
            'kpi_pegawai_turunan_id' => ['type' => 'INT'],
            'tahun'                  => ['type' => 'SMALLINT', 'constraint' => 6],
            'bulan'                  => ['type' => 'TINYINT', 'constraint' => 2],
            'target'                 => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
            'created_at'             => ['type' => 'DATETIME', 'null' => true],
            'updated_at'             => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('kpi_pegawai_turunan_id');
        $this->forge->addUniqueKey(['kpi_pegawai_turunan_id', 'tahun', 'bulan']);
        $this->forge->createTable('kpi_pegawai_turunan_target_bulanan');
    }

    public function down(): void
    {
        $this->forge->dropTable('kpi_pegawai_turunan_target_bulanan');
    }
}
