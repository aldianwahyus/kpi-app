<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Sama seperti kpi_pegawai_bobot_tahunan, untuk Parameter Turunan.
 */
class CreateTableKpiPegawaiTurunanBobotTahunan extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                     => ['type' => 'INT', 'auto_increment' => true],
            'kpi_pegawai_turunan_id' => ['type' => 'INT'],
            'tahun'                  => ['type' => 'SMALLINT', 'constraint' => 6],
            'bobot'                  => ['type' => 'DECIMAL', 'constraint' => '5,4', 'null' => true],
            'created_at'             => ['type' => 'DATETIME', 'null' => true],
            'updated_at'             => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('kpi_pegawai_turunan_id');
        $this->forge->addUniqueKey(['kpi_pegawai_turunan_id', 'tahun']);
        $this->forge->createTable('kpi_pegawai_turunan_bobot_tahunan');
    }

    public function down(): void
    {
        $this->forge->dropTable('kpi_pegawai_turunan_bobot_tahunan');
    }
}
