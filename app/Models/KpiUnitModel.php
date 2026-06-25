<?php
namespace App\Models;

use CodeIgniter\Model;

class KpiUnitModel extends Model
{
    protected $table         = 'kpi_unit';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'direktorat_id', 'perspektif', 'nama_kpi',
        'kode', 'satuan', 'bobot',
        'polarity', 'perubahan_polarity',
        'is_active', 'urutan',
    ];
    protected $useTimestamps = true;

    // Ambil semua KPI Unit beserta nama direktorat
    public function getAllWithDirektorat(): array
    {
        return $this->db->table('kpi_unit k')
            ->select('k.*, d.nama as nama_direktorat, d.kode as kode_direktorat')
            ->join('direktorat d', 'd.id = k.direktorat_id', 'left')
            ->where('k.is_active', 1)
            ->orderBy('d.nama', 'ASC')
            ->orderBy('k.perspektif', 'ASC')
            ->orderBy('k.urutan', 'ASC')
            ->get()->getResultArray();
    }

    // Ambil KPI Unit per Direktorat, dikelompokkan per perspektif
    public function getGroupedByDirektorat(): array
    {
        $rows = $this->getAllWithDirektorat();
        $grouped = [];
        foreach ($rows as $row) {
            $dir = $row['nama_direktorat'] ?? 'Tanpa Direktorat';
            $grouped[$dir][$row['perspektif']][] = $row;
        }
        return $grouped;
    }

    // Ambil KPI Unit per Direktorat untuk form assign
    public function getByDirektorat(int $direktoratId): array
    {
        return $this->where('direktorat_id', $direktoratId)
                    ->where('is_active', 1)
                    ->orderBy('perspektif', 'ASC')
                    ->orderBy('urutan', 'ASC')
                    ->findAll();
    }

    // Ambil dikelompokkan per perspektif untuk satu direktorat
    public function getGroupedPerspektif(int $direktoratId): array
    {
        $rows = $this->getByDirektorat($direktoratId);
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['perspektif']][] = $row;
        }
        return $grouped;
    }

    // Dropdown untuk form
    public function getDropdownByDirektorat(int $direktoratId): array
    {
        $rows = $this->getByDirektorat($direktoratId);
        $result = [];
        foreach ($rows as $row) {
            $result[$row['id']] = "[{$row['kode']}] {$row['nama_kpi']}";
        }
        return $result;
    }

    // Total bobot per direktorat
    public function getTotalBobot(int $direktoratId): float
    {
        $result = $this->db->table('kpi_unit')
            ->selectSum('bobot')
            ->where('direktorat_id', $direktoratId)
            ->where('is_active', 1)
            ->get()->getRowArray();
        return round((float)($result['bobot'] ?? 0), 4);
    }
}