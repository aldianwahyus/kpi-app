<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class CreateTableRubrikEsi extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'auto_increment' => true],
            'pegawai_id'  => ['type' => 'INT'],
            'periode_id'  => ['type' => 'INT'],
            'dimensi'     => ['type' => 'VARCHAR', 'constraint' => 50],
            'kode_item'   => ['type' => 'VARCHAR', 'constraint' => 5],
            'pernyataan'  => ['type' => 'TEXT'],
            'skor'        => ['type' => 'TINYINT'],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['pegawai_id','periode_id','kode_item']);
        $this->forge->createTable('rubrik_esi');
    }

    public function down(): void
    {
        $this->forge->dropTable('rubrik_esi');
    }
}