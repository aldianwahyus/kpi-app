<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class AddIndexesForPerformance extends Migration
{
    public function up(): void
    {
        // Index untuk tabel penilaian (paling sering di-query)
        $this->db->query('ALTER TABLE penilaian
            ADD INDEX idx_pegawai_periode (pegawai_id, periode_id),
            ADD INDEX idx_kpi_id (kpi_id),
            ADD INDEX idx_status (status)');

        // Index untuk penilaian_unit
        $this->db->query('ALTER TABLE penilaian_unit
            ADD INDEX idx_divisi_periode (divisi_id, periode_id)');

        // Index untuk pegawai
        $this->db->query('ALTER TABLE pegawai
            ADD INDEX idx_divisi_id (divisi_id),
            ADD INDEX idx_atasan_id (atasan_id),
            ADD INDEX idx_is_active (is_active)');

        // Index untuk kpi_divisi & kpi_pegawai
        $this->db->query('ALTER TABLE kpi_divisi
            ADD INDEX idx_divisi_id (divisi_id),
            ADD INDEX idx_kpi_id (kpi_id)');

        $this->db->query('ALTER TABLE kpi_pegawai
            ADD INDEX idx_pegawai_id (pegawai_id),
            ADD INDEX idx_kpi_id (kpi_id)');

        // Index untuk kpi_unit
        $this->db->query('ALTER TABLE kpi_unit
            ADD INDEX idx_direktorat_id (direktorat_id)');

        // Index untuk divisi
        $this->db->query('ALTER TABLE divisi
            ADD INDEX idx_direktorat_id (direktorat_id)');

        // Index untuk users
        $this->db->query('ALTER TABLE users
            ADD INDEX idx_pegawai_id (pegawai_id),
            ADD INDEX idx_role (role)');

        // Index untuk audit_log (sudah ada di migration awal, verifikasi)
        $this->db->query('ALTER TABLE audit_log
            ADD INDEX idx_created_at (created_at)');
    }

    public function down(): void
    {
        $this->db->query('ALTER TABLE penilaian
            DROP INDEX idx_pegawai_periode,
            DROP INDEX idx_kpi_id,
            DROP INDEX idx_status');
        $this->db->query('ALTER TABLE penilaian_unit
            DROP INDEX idx_divisi_periode');
        $this->db->query('ALTER TABLE pegawai
            DROP INDEX idx_divisi_id,
            DROP INDEX idx_atasan_id,
            DROP INDEX idx_is_active');
        $this->db->query('ALTER TABLE kpi_divisi
            DROP INDEX idx_divisi_id,
            DROP INDEX idx_kpi_id');
        $this->db->query('ALTER TABLE kpi_pegawai
            DROP INDEX idx_pegawai_id,
            DROP INDEX idx_kpi_id');
        $this->db->query('ALTER TABLE kpi_unit
            DROP INDEX idx_direktorat_id');
        $this->db->query('ALTER TABLE divisi
            DROP INDEX idx_direktorat_id');
        $this->db->query('ALTER TABLE users
            DROP INDEX idx_pegawai_id,
            DROP INDEX idx_role');
        $this->db->query('ALTER TABLE audit_log
            DROP INDEX idx_created_at');
    }
}