<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class CreateTablePenilaian extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'auto_increment' => true],
            'pegawai_id'  => ['type' => 'INT'],
            'kpi_id'      => ['type' => 'INT'],
            'periode_id'  => ['type' => 'INT'],
            'target'      => ['type' => 'DECIMAL', 'constraint' => '15,4', 'null' => true],
            'realisasi'   => ['type' => 'DECIMAL', 'constraint' => '15,4', 'null' => true],
            'capaian'     => ['type' => 'DECIMAL', 'constraint' => '8,4', 'null' => true],
            'nilai_kontribusi' => ['type' => 'DECIMAL', 'constraint' => '8,4', 'null' => true],
            'catatan'     => ['type' => 'TEXT', 'null' => true],
            'input_by'    => ['type' => 'INT', 'null' => true],
            'verified_by' => ['type' => 'INT', 'null' => true],
            'verified_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['pegawai_id','kpi_id','periode_id']);
        $this->forge->createTable('penilaian');
    }

    public function down(): void
    {
        $this->forge->dropTable('penilaian');
    }
}