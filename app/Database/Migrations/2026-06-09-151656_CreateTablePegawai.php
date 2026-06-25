<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class CreateTablePegawai extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'auto_increment' => true],
            'nip'          => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'nama'         => ['type' => 'VARCHAR', 'constraint' => 100],
            'jabatan'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'unit'         => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'golongan'     => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'tgl_masuk'    => ['type' => 'DATE', 'null' => true],
            'atasan_id'    => ['type' => 'INT', 'null' => true],
            'is_active'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('pegawai');
    }

    public function down(): void
    {
        $this->forge->dropTable('pegawai');
    }
}