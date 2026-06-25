<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class AddTableKpiDivisi extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'auto_increment' => true],
            'divisi_id'   => ['type' => 'INT'],
            'kpi_id'      => ['type' => 'INT'],
            'bobot'       => ['type' => 'DECIMAL', 'constraint' => '5,4'],
            'urutan'      => ['type' => 'INT', 'default' => 0],
            'is_active'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['divisi_id', 'kpi_id']);
        $this->forge->createTable('kpi_divisi');
    }

    public function down(): void
    {
        $this->forge->dropTable('kpi_divisi');
    }
}