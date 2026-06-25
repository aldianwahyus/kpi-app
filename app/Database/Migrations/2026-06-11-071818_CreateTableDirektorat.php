<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class CreateTableDirektorat extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'auto_increment' => true],
            'kode'        => ['type' => 'VARCHAR', 'constraint' => 20],
            'nama'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'singkatan'   => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'deskripsi'   => ['type' => 'TEXT', 'null' => true],
            'is_active'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('kode');
        $this->forge->createTable('direktorat');
    }

    public function down(): void
    {
        $this->forge->dropTable('direktorat');
    }
}