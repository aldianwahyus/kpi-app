<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTablePenilaianArsip extends Migration
{
    public function up(): void
    {
        // Snapshot beku (frozen) dari penilaian + konfigurasi KPI/pegawai
        // pada saat suatu Periode ditutup — supaya laporan periode yang
        // sudah ditutup TIDAK terpengaruh oleh perubahan konfigurasi KPI
        // (bobot, target, atau bahkan penghapusan KPI dari pegawai) yang
        // terjadi setelahnya untuk periode berikutnya. Tanpa arsip ini,
        // Rekap/Laporan historis akan mengikuti kondisi KPI TERKINI (via
        // JOIN langsung ke kpi_unit/kpi_pegawai), bukan kondisi saat
        // penilaian sebenarnya dilakukan.
        $this->forge->addField([
            'id'                     => ['type' => 'INT', 'auto_increment' => true],
            'periode_id'             => ['type' => 'INT'],
            'periode_nama'           => ['type' => 'VARCHAR', 'constraint' => 100],
            'periode_kode'           => ['type' => 'VARCHAR', 'constraint' => 30],
            'penilaian_id'           => ['type' => 'INT', 'null' => true], // jejak ke sumber asli, bukan FK enforced
            'pegawai_id'             => ['type' => 'INT'],
            'pegawai_nama'           => ['type' => 'VARCHAR', 'constraint' => 100],
            'pegawai_nip'            => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'pegawai_jabatan'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'divisi_id'              => ['type' => 'INT', 'null' => true],
            'divisi_nama'            => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'direktorat_nama'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'kpi_id'                 => ['type' => 'INT'],
            'kpi_kode'               => ['type' => 'VARCHAR', 'constraint' => 30],
            'kpi_nama'               => ['type' => 'VARCHAR', 'constraint' => 150],
            'kpi_satuan'             => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'kpi_perspektif'         => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'polarity'               => ['type' => 'VARCHAR', 'constraint' => 20],
            'perubahan_polarity'     => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'sifat_khusus'           => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'toleransi_skor4'        => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'toleransi_skor3'        => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'toleransi_skor2'        => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'bobot'                  => ['type' => 'DECIMAL', 'constraint' => '5,4', 'default' => 0],
            'target'                 => ['type' => 'DECIMAL', 'constraint' => '15,4', 'null' => true],
            'realisasi'              => ['type' => 'DECIMAL', 'constraint' => '15,4', 'null' => true],
            'realisasi_harian'       => ['type' => 'DECIMAL', 'constraint' => '15,4', 'null' => true],
            'skor'                   => ['type' => 'DECIMAL', 'constraint' => '8,2', 'null' => true],
            'nilai_kontribusi'       => ['type' => 'DECIMAL', 'constraint' => '10,4', 'null' => true],
            'catatan'                => ['type' => 'TEXT', 'null' => true],
            'status'                 => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'submitted_at'           => ['type' => 'DATETIME', 'null' => true],
            'approved_by_nama'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'approved_at'            => ['type' => 'DATETIME', 'null' => true],
            'reject_note'            => ['type' => 'TEXT', 'null' => true],
            'input_by_nama'          => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'arsip_dibuat_oleh'      => ['type' => 'INT', 'null' => true],
            'created_at'             => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('periode_id');
        $this->forge->addKey(['periode_id', 'pegawai_id']);
        $this->forge->createTable('penilaian_arsip');
    }

    public function down(): void
    {
        $this->forge->dropTable('penilaian_arsip');
    }
}
