<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class CreateTableKpiPegawaiTurunan extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'auto_increment' => true],
            'kpi_pegawai_id' => ['type' => 'INT'],
            'nama_turunan'   => ['type' => 'VARCHAR', 'constraint' => 150],
            'bobot'          => ['type' => 'DECIMAL', 'constraint' => '5,4', 'default' => 0],
            'target'         => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'urutan'         => ['type' => 'INT', 'default' => 0],
            'is_active'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('kpi_pegawai_id');
        $this->forge->createTable('kpi_pegawai_turunan');
    }

    public function down(): void
    {
        $this->forge->dropTable('kpi_pegawai_turunan');
    }
}