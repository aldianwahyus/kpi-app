<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class CreateTableKpiMaster extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'auto_increment' => true],
            'perspektif'          => ['type' => 'ENUM', 'constraint' => ['Financial','Customer','Internal Process','Learning & Growth']],
            'nama_kpi'            => ['type' => 'VARCHAR', 'constraint' => 150],
            'kode'                => ['type' => 'VARCHAR', 'constraint' => 20],
            'satuan'              => ['type' => 'VARCHAR', 'constraint' => 30],
            'bobot'               => ['type' => 'DECIMAL', 'constraint' => '5,4'],
            'total_bobot_perspektif' => ['type' => 'DECIMAL', 'constraint' => '5,4', 'null' => true],
            'polarity'            => ['type' => 'ENUM', 'constraint' => ['max','min'], 'default' => 'max'],
            'perubahan_polarity'  => ['type' => 'ENUM', 'constraint' => ['pos','neg'], 'default' => 'pos'],
            'is_kualitatif'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'rubrik_sheet'        => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'is_active'           => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'urutan'              => ['type' => 'INT', 'default' => 0],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('kode');
        $this->forge->createTable('kpi_master');
    }

    public function down(): void
    {
        $this->forge->dropTable('kpi_master');
    }
}