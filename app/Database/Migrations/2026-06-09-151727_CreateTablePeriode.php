<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class CreateTablePeriode extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'auto_increment' => true],
            'nama'       => ['type' => 'VARCHAR', 'constraint' => 50],   // contoh: "Q1 2025"
            'kode'       => ['type' => 'VARCHAR', 'constraint' => 20],   // contoh: "2025-Q1"
            'tgl_mulai'  => ['type' => 'DATE'],
            'tgl_selesai'=> ['type' => 'DATE'],
            'status'     => ['type' => 'ENUM', 'constraint' => ['draft','aktif','tutup'], 'default' => 'draft'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('kode');
        $this->forge->createTable('periode');
    }

    public function down(): void
    {
        $this->forge->dropTable('periode');
    }
}