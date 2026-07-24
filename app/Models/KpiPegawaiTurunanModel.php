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
        'toleransi_skor4', 'toleransi_skor3', 'toleransi_skor2', 'sifat_khusus',
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

    /**
     * Sama seperti getByKpiPegawai(), tapi Target & Bobot sudah diresolve
     * dari Master Target untuk satu Periode tertentu (rata-rata Target
     * Bulanan sesuai rentang bulan Periode; Bobot Tahunan untuk tahun
     * Periode ini). NULL jika belum lengkap diisi di Master Target.
     */
    public function getByKpiPegawaiUntukPeriode(int $kpiPegawaiId, array $periode): array
    {
        $list = $this->getByKpiPegawai($kpiPegawaiId);
        if (empty($list)) {
            return [];
        }

        $turunanIds     = array_column($list, 'id');
        $periodeModel   = new PeriodeModel();
        $bulanTahunList = $periodeModel->getBulanTahunList($periode);
        $tahunList      = array_values(array_unique(array_column($bulanTahunList, 'tahun')));
        $tahunAnchor    = (int)date('Y', strtotime($periode['tgl_mulai']));

        $targetIndexed = (new KpiPegawaiTurunanTargetBulananModel())
            ->getIndexedByRefAndTahunList($turunanIds, $tahunList);
        $bobotIndexed  = (new KpiPegawaiTurunanBobotTahunanModel())
            ->getIndexedByRefAndTahun($turunanIds, $tahunAnchor);

        foreach ($list as &$row) {
            $row['bobot_dasar'] = $row['bobot'];
            $row['bobot']       = $bobotIndexed[$row['id']] ?? null;
            $row['target']      = KpiPegawaiModel::hitungTargetEfektif($targetIndexed[$row['id']] ?? [], $bulanTahunList);
        }
        unset($row);

        return $list;
    }

    // Hapus seluruh Turunan milik satu Induk (dipakai saat Induk dihapus
    // dari KPI Per Pegawai, agar tidak ada data Turunan yang menjadi yatim)
    public function deleteByKpiPegawai(int $kpiPegawaiId): void
    {
        $this->where('kpi_pegawai_id', $kpiPegawaiId)->delete();
    }
}