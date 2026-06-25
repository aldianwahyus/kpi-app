<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class CreateTableKpiPegawai extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'auto_increment' => true],
            'pegawai_id' => ['type' => 'INT'],
            'kpi_id'     => ['type' => 'INT'],
            'divisi_id'  => ['type' => 'INT'],
            'bobot'      => ['type' => 'DECIMAL', 'constraint' => '5,4', 'default' => 0],
            'urutan'     => ['type' => 'INT', 'default' => 0],
            'is_active'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['pegawai_id', 'kpi_id']);
        $this->forge->createTable('kpi_pegawai');
    }

    public function down(): void
    {
        $this->forge->dropTable('kpi_pegawai');
    }
}