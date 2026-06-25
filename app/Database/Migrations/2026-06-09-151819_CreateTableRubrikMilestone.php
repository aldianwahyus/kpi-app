<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class CreateTableRubrikMilestone extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'auto_increment' => true],
            'pegawai_id'   => ['type' => 'INT'],
            'periode_id'   => ['type' => 'INT'],
            'nama_project' => ['type' => 'VARCHAR', 'constraint' => 150],
            'milestone'    => ['type' => 'VARCHAR', 'constraint' => 10],  // M1–M6
            'poin'         => ['type' => 'TINYINT'],
            'tercapai'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('rubrik_milestone');
    }

    public function down(): void
    {
        $this->forge->dropTable('rubrik_milestone');
    }
}