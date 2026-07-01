<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class CreateTablePenilaianTurunan extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                     => ['type' => 'INT', 'auto_increment' => true],
            'penilaian_id'           => ['type' => 'INT'],
            'kpi_pegawai_turunan_id' => ['type' => 'INT'],
            'realisasi'              => ['type' => 'DECIMAL', 'constraint' => '15,4', 'null' => true],
            'catatan'                => ['type' => 'TEXT', 'null' => true],
            'created_at'             => ['type' => 'DATETIME', 'null' => true],
            'updated_at'             => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('penilaian_id');
        $this->forge->addUniqueKey(['penilaian_id', 'kpi_pegawai_turunan_id']);
        $this->forge->createTable('penilaian_turunan');
    }

    public function down(): void
    {
        $this->forge->dropTable('penilaian_turunan');
    }
}