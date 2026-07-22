<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTablePenilaianTurunanArsip extends Migration
{
    public function up(): void
    {
        // Snapshot beku untuk Parameter Turunan, dipasangkan dengan baris
        // penilaian_arsip induknya — sama alasannya dengan penilaian_arsip:
        // konfigurasi Turunan (bobot, target, polarity) bisa berubah atau
        // dihapus di kemudian hari, laporan periode yang sudah ditutup
        // tidak boleh ikut berubah.
        $this->forge->addField([
            'id'                        => ['type' => 'INT', 'auto_increment' => true],
            'penilaian_arsip_id'        => ['type' => 'INT'],
            'nama_turunan'              => ['type' => 'VARCHAR', 'constraint' => 150],
            'satuan'                    => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'polarity'                  => ['type' => 'VARCHAR', 'constraint' => 20],
            'perubahan_polarity'        => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'sifat_khusus'              => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'toleransi_skor4'           => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'toleransi_skor3'           => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'toleransi_skor2'           => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'bobot'                     => ['type' => 'DECIMAL', 'constraint' => '5,4', 'default' => 0],
            'target'                    => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
            'realisasi'                 => ['type' => 'DECIMAL', 'constraint' => '15,4', 'null' => true],
            'realisasi_harian'          => ['type' => 'DECIMAL', 'constraint' => '15,4', 'null' => true],
            'skor'                      => ['type' => 'DECIMAL', 'constraint' => '8,2', 'null' => true],
            'nilai_kontribusi'          => ['type' => 'DECIMAL', 'constraint' => '10,4', 'null' => true],
            'catatan'                   => ['type' => 'TEXT', 'null' => true],
            'urutan'                    => ['type' => 'INT', 'default' => 0],
            'created_at'                => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('penilaian_arsip_id');
        $this->forge->createTable('penilaian_turunan_arsip');
    }

    public function down(): void
    {
        $this->forge->dropTable('penilaian_turunan_arsip');
    }
}
