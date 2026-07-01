<?php
namespace App\Models;

use CodeIgniter\Model;

class KpiPegawaiTurunanModel extends Model
{
    protected $table         = 'kpi_pegawai_turunan';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'kpi_pegawai_id', 'nama_turunan',
        'bobot', 'target', 'deskripsi_target',
        'polarity', 'perubahan_polarity', 'satuan',
        'urutan', 'is_active',
    ];
    protected $useTimestamps = true;

    // Ambil seluruh Parameter Turunan milik satu Parameter Induk (kpi_pegawai)
    public function getByKpiPegawai(int $kpiPegawaiId): array
    {
        return $this->where('kpi_pegawai_id', $kpiPegawaiId)
                    ->where('is_active', 1)
                    ->orderBy('urutan', 'ASC')
                    ->orderBy('id', 'ASC')
                    ->findAll();
    }

    // Cek apakah suatu Parameter Induk sudah memiliki Turunan
    public function hasTurunan(int $kpiPegawaiId): bool
    {
        return $this->where('kpi_pegawai_id', $kpiPegawaiId)
                    ->where('is_active', 1)
                    ->countAllResults() > 0;
    }

    // Total Bobot seluruh Turunan milik satu Induk — dipakai untuk validasi
    // bahwa SUM Bobot Turunan harus tepat sama dengan Bobot Induk.
    // $excludeId dipakai saat mengedit satu Turunan, agar Turunan yang
    // sedang diedit tidak ikut dihitung dengan nilai lamanya.
    public function getTotalBobot(int $kpiPegawaiId, ?int $excludeId = null): float
    {
        $builder = $this->where('kpi_pegawai_id', $kpiPegawaiId)
                        ->where('is_active', 1);
        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }
        $result = $builder->selectSum('bobot')->get()->getRowArray();
        return round((float)($result['bobot'] ?? 0), 4);
    }

    // Total Target seluruh Turunan milik satu Induk — dipakai untuk
    // validasi bahwa SUM Target Turunan harus tepat sama dengan
    // Target Induk (pagu/plafon yang ditentukan manual oleh Admin),
    // dengan aturan yang sama persis seperti validasi Bobot Turunan.
    // $excludeId dipakai saat mengedit satu Turunan, agar Turunan yang
    // sedang diedit tidak ikut dihitung dengan nilai lamanya.
    public function getTotalTarget(int $kpiPegawaiId, ?int $excludeId = null): float
    {
        $builder = $this->where('kpi_pegawai_id', $kpiPegawaiId)
                        ->where('is_active', 1);
        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }
        $result = $builder->selectSum('target')->get()->getRowArray();
        return round((float)($result['target'] ?? 0), 2);
    }

    // Hapus seluruh Turunan milik satu Induk (dipakai saat Induk dihapus
    // dari KPI Per Pegawai, agar tidak ada data Turunan yang menjadi yatim)
    public function deleteByKpiPegawai(int $kpiPegawaiId): void
    {
        $this->where('kpi_pegawai_id', $kpiPegawaiId)->delete();
    }
}