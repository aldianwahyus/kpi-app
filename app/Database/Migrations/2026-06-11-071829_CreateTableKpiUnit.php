<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class CreateTableKpiUnit extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'auto_increment' => true],
            'direktorat_id'       => ['type' => 'INT', 'null' => true],
            'perspektif'          => ['type' => 'ENUM', 'constraint' => ['Financial','Customer','Internal Process','Learning & Growth']],
            'nama_kpi'            => ['type' => 'VARCHAR', 'constraint' => 150],
            'kode'                => ['type' => 'VARCHAR', 'constraint' => 30],
            'satuan'              => ['type' => 'VARCHAR', 'constraint' => 30],
            'bobot'               => ['type' => 'DECIMAL', 'constraint' => '5,4', 'default' => 0],
            'polarity'            => ['type' => 'ENUM', 'constraint' => ['max','min'], 'default' => 'max'],
            'perubahan_polarity'  => ['type' => 'ENUM', 'constraint' => ['pos','neg'], 'default' => 'pos'],
            'is_active'           => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'urutan'              => ['type' => 'INT', 'default' => 0],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('kode');
        $this->forge->createTable('kpi_unit');
    }

    public function down(): void
    {
        $this->forge->dropTable('kpi_unit');
    }
}