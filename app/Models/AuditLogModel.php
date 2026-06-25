<?php
namespace App\Models;

use CodeIgniter\Model;

class AuditLogModel extends Model
{
    protected $table      = 'audit_log';
    protected $primaryKey = 'id';

    // Ambil histori untuk satu record
    public function getByRecord(string $table, int $recordId): array
    {
        return $this->db->table('audit_log')
            ->where('table_name', $table)
            ->where('record_id', $recordId)
            ->orderBy('created_at', 'DESC')
            ->get()->getResultArray();
    }

    // Ambil histori penilaian per pegawai per periode
    public function getPenilaianHistory(
        int $pegawaiId,
        int $periodeId
    ): array {
        // Ambil semua penilaian_id untuk pegawai & periode ini
        $penilaianIds = $this->db->table('penilaian')
            ->select('id')
            ->where('pegawai_id', $pegawaiId)
            ->where('periode_id', $periodeId)
            ->get()->getResultArray();

        if (empty($penilaianIds)) return [];

        $ids = array_column($penilaianIds, 'id');

        return $this->db->table('audit_log')
            ->where('table_name', 'penilaian')
            ->whereIn('record_id', $ids)
            ->orderBy('created_at', 'DESC')
            ->get()->getResultArray();
    }
}