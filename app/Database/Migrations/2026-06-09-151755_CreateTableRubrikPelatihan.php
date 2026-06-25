<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class CreateTableRubrikPelatihan extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'auto_increment' => true],
            'periode_id'   => ['type' => 'INT'],
            'nama_pelatihan' => ['type' => 'VARCHAR', 'constraint' => 150],
            'tanggal'      => ['type' => 'DATE', 'null' => true],
            'penyelenggara'=> ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'no_item'      => ['type' => 'TINYINT'],
            'dimensi'      => ['type' => 'VARCHAR', 'constraint' => 100],
            'bobot'        => ['type' => 'DECIMAL', 'constraint' => '4,2'],
            'rata_skor'    => ['type' => 'DECIMAL', 'constraint' => '4,2', 'null' => true],
            'nilai_terbobot' => ['type' => 'DECIMAL', 'constraint' => '6,2', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('rubrik_pelatihan');
    }

    public function down(): void
    {
        $this->forge->dropTable('rubrik_pelatihan');
    }
}