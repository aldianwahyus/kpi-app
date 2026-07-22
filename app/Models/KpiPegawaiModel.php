<?php
namespace App\Models;

use CodeIgniter\Model;

class KpiPegawaiModel extends Model
{
    protected $table         = 'kpi_pegawai';
    protected $primaryKey    = 'id';
    
    // ── PERBAIKAN 1: Tambahkan 'target' ke allowedFields agar bisa di-save/update ──
    protected $allowedFields = [
        'pegawai_id', 'kpi_id', 'divisi_id',
        'bobot', 'target', 'deskripsi_target', 'urutan', 'is_active',
    ];
    protected $useTimestamps = true;

    // Ambil KPI pegawai beserta detail KPI
    public function getByPegawai(int $pegawaiId): array
    {
        return $this->db->table('kpi_pegawai kp')
            // UBAH SELECT DI BAWAH INI:
            // Kita tidak menggunakan kp.* untuk menghindari pengambilan kolom yang tidak ada
            ->select('kp.id, kp.pegawai_id, kp.kpi_id, kp.divisi_id,
                    kp.bobot          as bobot,
                    kp.urutan         as urutan,
                    kp.is_active      as is_active,
                    k.nama_kpi, k.kode, k.satuan,
                    IFNULL(kp.target, k.target) as target,
                    kp.deskripsi_target,
                    k.polarity, k.is_capped, k.perubahan_polarity, k.perspektif,
                    k.toleransi_skor4, k.toleransi_skor3, k.toleransi_skor2, k.sifat_khusus')
            ->join('kpi_unit k', 'k.id = kp.kpi_id')
            ->where('kp.pegawai_id', $pegawaiId)
            ->where('kp.is_active', 1)
            ->where('k.is_active', 1)
            ->orderBy('k.perspektif', 'ASC')
            ->orderBy('kp.urutan', 'ASC')
            ->get()->getResultArray();
    }

    // Kelompokkan per perspektif
    public function getGroupedByPerspektif(int $pegawaiId): array
    {
        $rows = $this->getByPegawai($pegawaiId);
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['perspektif']][] = $row;
        }
        return $grouped;
    }

    // Ambil kpi_id yang sudah di-assign ke pegawai
    public function getAssignedKpiIds(int $pegawaiId): array
    {
        $rows = $this->select('kpi_id')
                     ->where('pegawai_id', $pegawaiId)
                     ->findAll();
        return array_column($rows, 'kpi_id');
    }

    // Cek apakah sudah di-assign
    public function isAssigned(
        int $pegawaiId,
        int $kpiId,
        ?int $excludeId = null
    ): bool {
        $builder = $this->where('pegawai_id', $pegawaiId)
                        ->where('kpi_id', $kpiId);
        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }
        return $builder->countAllResults() > 0;
    }

    // Total bobot KPI pegawai
    public function getTotalBobot(int $pegawaiId): float
    {
        $result = $this->db->table('kpi_pegawai')
            ->selectSum('bobot')
            ->where('pegawai_id', $pegawaiId)
            ->where('is_active', 1)
            ->get()->getRowArray();
        return round((float)($result['bobot'] ?? 0), 4);
    }

    // Hapus semua KPI pegawai (untuk re-assign)
    public function deleteByPegawai(int $pegawaiId): void
    {
        $this->where('pegawai_id', $pegawaiId)->delete();
    }

    public function getPegawaiGroupedByDivisi()
    {
        $data = $this->select('pegawai.*, divisi.nama_divisi')
                    ->join('divisi', 'divisi.id = pegawai.divisi_id')
                    ->findAll();

        $grouped = [];

        foreach ($data as $row) {
            $grouped[$row['divisi_id']][] = $row;
        }

        return $grouped;
    }
}