<?php
namespace App\Models;

use CodeIgniter\Model;

class KpiMasterModel extends Model
{
    protected $table         = 'kpi_master';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'perspektif', 'nama_kpi', 'kode', 'satuan',
        'bobot', 'total_bobot_perspektif',
        'polarity', 'perubahan_polarity',
        'is_kualitatif', 'rubrik_sheet',
        'is_active', 'urutan',
    ];
    protected $useTimestamps = true;

    // Ambil semua KPI diurutkan per perspektif
    public function getAllOrdered(): array
    {
        return $this->orderBy('urutan', 'ASC')->findAll();
    }

    // Ambil KPI dikelompokkan per perspektif
    public function getGroupedByPerspektif(): array
    {
        $rows = $this->getAllOrdered();
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['perspektif']][] = $row;
        }
        return $grouped;
    }

    // Total bobot semua KPI (harus = 1.00)
    public function getTotalBobot(): float
    {
        return (float) $this->selectSum('bobot')->first()['bobot'];
    }

    // Cek apakah kode KPI sudah ada (untuk validasi)
    public function isKodeExists(string $kode, ?int $excludeId = null): bool
    {
        $builder = $this->where('kode', $kode);
        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }
        return $builder->countAllResults() > 0;
    }
}