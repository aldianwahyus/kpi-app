<?php
namespace App\Models;

use CodeIgniter\Model;

class DraftUlangRequestModel extends Model
{
    protected $table         = 'draft_ulang_request';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'tipe', 'pegawai_id', 'periode_id', 'alasan',
        'status', 'requested_by', 'requested_by_nama', 'requested_at',
        'confirmed_by', 'confirmed_by_nama', 'confirmed_at',
        'catatan_admin',
    ];
    protected $useTimestamps = true;

    public function getPending(): array
    {
        return $this->db->table('draft_ulang_request dr')
            ->select('dr.*, p.nama as nama_pegawai, pr.nama as nama_periode')
            ->join('pegawai p', 'p.id = dr.pegawai_id', 'left')
            ->join('periode pr', 'pr.id = dr.periode_id')
            ->where('dr.status', 'pending')
            ->orderBy('dr.requested_at', 'DESC')
            ->get()->getResultArray();
    }

    public function getAllWithDetail(int $limit = 100): array
    {
        return $this->db->table('draft_ulang_request dr')
            ->select('dr.*, p.nama as nama_pegawai, pr.nama as nama_periode')
            ->join('pegawai p', 'p.id = dr.pegawai_id', 'left')
            ->join('periode pr', 'pr.id = dr.periode_id')
            ->orderBy('dr.created_at', 'DESC')
            ->get($limit)->getResultArray();
    }

    public function getCountPending(): int
    {
        return $this->where('status', 'pending')->countAllResults();
    }

    // Cek apakah ada request pending untuk pegawai/periode ini
    public function hasPendingRequest(?int $pegawaiId, int $periodeId, string $tipe): bool
    {
        $builder = $this->where('periode_id', $periodeId)
                        ->where('tipe', $tipe)
                        ->where('status', 'pending');
        if ($tipe === 'pegawai') {
            $builder->where('pegawai_id', $pegawaiId);
        }
        return $builder->countAllResults() > 0;
    }
}