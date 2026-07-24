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

    /**
     * Ambil KPI pegawai beserta detail KPI, dengan Target & Bobot yang
     * SUDAH DIRESOLVE dari Master Target untuk satu Periode tertentu.
     *   - target : rata-rata Target Bulanan (Master Target) untuk seluruh
     *     bulan yang dicakup Periode ini (1 bulan untuk Bulanan, 3 untuk
     *     Triwulan, dst — lihat PeriodeModel::getBulanTahunList()). NULL
     *     jika ADA bulan dalam rentang itu yang Target-nya belum diisi di
     *     Master Target (pemanggil WAJIB memeriksa ini & memblokir).
     *   - bobot  : Bobot Tahunan (Master Target) untuk tahun Periode ini.
     *     NULL jika belum diisi di Master Target.
     */
    public function getByPegawaiUntukPeriode(int $pegawaiId, array $periode): array
    {
        $assigned = $this->getByPegawai($pegawaiId);
        if (empty($assigned)) {
            return [];
        }

        $kpiPegawaiIds  = array_column($assigned, 'id');
        $periodeModel   = new PeriodeModel();
        $bulanTahunList = $periodeModel->getBulanTahunList($periode);
        $tahunList      = array_values(array_unique(array_column($bulanTahunList, 'tahun')));
        $tahunAnchor    = (int)date('Y', strtotime($periode['tgl_mulai']));

        $targetIndexed = (new KpiPegawaiTargetBulananModel())
            ->getIndexedByRefAndTahunList($kpiPegawaiIds, $tahunList);
        $bobotIndexed  = (new KpiPegawaiBobotTahunanModel())
            ->getIndexedByRefAndTahun($kpiPegawaiIds, $tahunAnchor);

        foreach ($assigned as &$row) {
            $row['bobot_dasar'] = $row['bobot'];
            $row['bobot']       = $bobotIndexed[$row['id']] ?? null;
            $row['target']      = self::hitungTargetEfektif($targetIndexed[$row['id']] ?? [], $bulanTahunList);
        }
        unset($row);

        return $assigned;
    }

    /**
     * Hitung rata-rata Target Bulanan untuk daftar (tahun,bulan) yang
     * dibutuhkan satu Periode. Mengembalikan NULL jika ADA satu saja bulan
     * yang belum diisi (all-or-nothing) — sesuai keputusan bisnis: Periode
     * Triwulan/Semester/Tahunan tidak boleh dihitung dari data yang tidak
     * lengkap.
     */
    public static function hitungTargetEfektif(array $bulanMap, array $bulanTahunList): ?float
    {
        $nilai = [];
        foreach ($bulanTahunList as $bt) {
            $key = $bt['tahun'] . '-' . $bt['bulan'];
            if (!array_key_exists($key, $bulanMap) || $bulanMap[$key] === null) {
                return null;
            }
            $nilai[] = (float)$bulanMap[$key];
        }
        return empty($nilai) ? null : array_sum($nilai) / count($nilai);
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

    // Total Bobot Tahunan (Master Target) untuk tahun Periode ini — dipakai
    // sebagai syarat "harus 100%" sebelum Penilaian pada Periode itu bisa
    // diisi. Bobot yang belum diisi di Master Target dihitung sebagai 0
    // (bukan diabaikan), sehingga total otomatis tidak akan genap 100%
    // selama masih ada yang belum lengkap.
    public function getTotalBobotUntukPeriode(int $pegawaiId, array $periode): float
    {
        $assignedIds = $this->select('id')
            ->where('pegawai_id', $pegawaiId)
            ->where('is_active', 1)
            ->findColumn('id') ?? [];

        if (empty($assignedIds)) {
            return 0.0;
        }

        $tahun = (int)date('Y', strtotime($periode['tgl_mulai']));
        $bobotIndexed = (new KpiPegawaiBobotTahunanModel())
            ->getIndexedByRefAndTahun($assignedIds, $tahun);

        $total = 0.0;
        foreach ($assignedIds as $id) {
            $total += (float)($bobotIndexed[$id] ?? 0);
        }
        return round($total, 4);
    }

    // Total Bobot Tahunan (Master Target) untuk satu tahun tertentu —
    // dipakai layar Master Target sendiri (validasi 100% saat menyimpan,
    // sebelum ada konteks Periode yang dibuka).
    public function getTotalBobotUntukTahun(int $pegawaiId, int $tahun): float
    {
        $assignedIds = $this->select('id')
            ->where('pegawai_id', $pegawaiId)
            ->where('is_active', 1)
            ->findColumn('id') ?? [];

        if (empty($assignedIds)) {
            return 0.0;
        }

        $bobotIndexed = (new KpiPegawaiBobotTahunanModel())
            ->getIndexedByRefAndTahun($assignedIds, $tahun);

        $total = 0.0;
        foreach ($assignedIds as $id) {
            $total += (float)($bobotIndexed[$id] ?? 0);
        }
        return round($total, 4);
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