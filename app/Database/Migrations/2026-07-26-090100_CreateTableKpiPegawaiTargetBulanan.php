<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Master Target: Target KPI Induk diisi per bulan untuk satu tahun penuh
 * (12 baris per KPI per tahun). Target Periode Triwulan/Semester/Tahunan
 * dihitung otomatis sebagai rata-rata bulan-bulan yang bersangkutan — lihat
 * PeriodeModel::getBulanTahunList() & KpiPegawaiModel::getTargetEfektifUntukPeriode().
 */
class CreateTableKpiPegawaiTargetBulanan extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'auto_increment' => true],
            'kpi_pegawai_id' => ['type' => 'INT'],
            'tahun'          => ['type' => 'SMALLINT', 'constraint' => 6],
            'bulan'          => ['type' => 'TINYINT', 'constraint' => 2],
            'target'         => ['type' => 'DECIMAL', 'constraint' => '15,4', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('kpi_pegawai_id');
        $this->forge->addUniqueKey(['kpi_pegawai_id', 'tahun', 'bulan']);
        $this->forge->createTable('kpi_pegawai_target_bulanan');
    }

    public function down(): void
    {
        $this->forge->dropTable('kpi_pegawai_target_bulanan');
    }
}
