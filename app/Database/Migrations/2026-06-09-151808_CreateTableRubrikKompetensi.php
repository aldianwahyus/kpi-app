<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class CreateTableRubrikKompetensi extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'auto_increment' => true],
            'pegawai_id'  => ['type' => 'INT'],
            'periode_id'  => ['type' => 'INT'],
            'penilai_id'  => ['type' => 'INT'],
            'kelompok'    => ['type' => 'VARCHAR', 'constraint' => 30],
            'kode_item'   => ['type' => 'VARCHAR', 'constraint' => 5],
            'indikator'   => ['type' => 'TEXT'],
            'skor'        => ['type' => 'TINYINT'],  // 1-4
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['pegawai_id','periode_id','kode_item']);
        $this->forge->createTable('rubrik_kompetensi');
    }

    public function down(): void
    {
        $this->forge->dropTable('rubrik_kompetensi');
    }
}