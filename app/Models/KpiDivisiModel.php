<?php
namespace App\Models;

use CodeIgniter\Model;

class KpiDivisiModel extends Model
{
    protected $table         = 'kpi_divisi';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'divisi_id', 'kpi_id', 'bobot',
        'urutan', 'is_active',
    ];
    protected $useTimestamps = true;

    // Ambil KPI milik divisi tertentu lengkap dengan detail KPI
    // public function getByDivisi(int $divisiId): array
    // {
    //     return $this->db->table('kpi_divisi kd')
    //         ->select('kd.*, k.nama_kpi, k.kode, k.satuan,
    //                   k.polarity, k.perubahan_polarity,
    //                   k.is_kualitatif, k.rubrik_sheet,
    //                   k.perspektif')
    //         ->join('kpi_master k', 'k.id = kd.kpi_id')
    //         ->where('kd.divisi_id', $divisiId)
    //         ->where('kd.is_active', 1)
    //         ->where('k.is_active', 1)
    //         ->orderBy('k.perspektif', 'ASC')
    //         ->orderBy('kd.urutan', 'ASC')
    //         ->get()->getResultArray();
    // }
    public function getByDivisi(int $divisiId): array
    {
        return $this->db->table('kpi_divisi kd')
            ->select('kd.*, k.nama_kpi, k.kode, k.satuan,
                    k.polarity, k.perubahan_polarity,
                    k.perspektif, k.direktorat_id')
            ->join('kpi_unit k', 'k.id = kd.kpi_id')  // ← join ke kpi_unit
            ->where('kd.divisi_id', $divisiId)
            ->where('kd.is_active', 1)
            ->where('k.is_active', 1)
            ->orderBy('k.perspektif', 'ASC')
            ->orderBy('kd.urutan', 'ASC')
            ->get()->getResultArray();
    }

    // Ambil KPI divisi dikelompokkan per perspektif
    public function getGroupedByPerspektif(int $divisiId): array
    {
        $rows = $this->getByDivisi($divisiId);
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['perspektif']][] = $row;
        }
        return $grouped;
    }

    // Cek total bobot KPI divisi (harus = 1.00)
    public function getTotalBobot(int $divisiId): float
    {
        $result = $this->db->table('kpi_divisi')
            ->selectSum('bobot')
            ->where('divisi_id', $divisiId)
            ->where('is_active', 1)
            ->get()->getRowArray();
        return round((float)($result['bobot'] ?? 0), 4);
    }

    // Cek apakah KPI sudah di-assign ke divisi
    public function isAssigned(int $divisiId, int $kpiId, ?int $excludeId = null): bool
    {
        $builder = $this->where('divisi_id', $divisiId)
                        ->where('kpi_id', $kpiId);
        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }
        return $builder->countAllResults() > 0;
    }

    // Hapus semua KPI dari divisi (untuk re-assign)
    public function deleteByDivisi(int $divisiId): void
    {
        $this->where('divisi_id', $divisiId)->delete();
    }

    // Ambil id KPI yang sudah di-assign ke divisi
    public function getAssignedKpiIds(int $divisiId): array
    {
        $rows = $this->select('kpi_id')
                     ->where('divisi_id', $divisiId)
                     ->findAll();
        return array_column($rows, 'kpi_id');
    }



}