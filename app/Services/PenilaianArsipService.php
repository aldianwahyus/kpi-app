<?php

namespace App\Services;

use App\Models\PenilaianArsipModel;
use App\Models\PenilaianTurunanArsipModel;
use App\Models\PeriodeModel;
use App\Models\KpiPegawaiModel;
use App\Models\KpiPegawaiTargetBulananModel;
use App\Models\KpiPegawaiBobotTahunanModel;
use App\Models\KpiPegawaiTurunanTargetBulananModel;
use App\Models\KpiPegawaiTurunanBobotTahunanModel;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * Mengarsipkan (snapshot beku) seluruh data Penilaian — beserta konfigurasi
 * KPI Induk & Parameter Turunan pada saat itu — untuk satu Periode yang
 * ditutup. Tujuannya supaya laporan periode yang sudah ditutup tidak ikut
 * berubah jika konfigurasi KPI (bobot, target, atau bahkan penghapusan KPI
 * dari pegawai) diubah di kemudian hari untuk periode berikutnya.
 */
class PenilaianArsipService
{
    private BaseConnection $db;
    private PenilaianArsipModel $arsipModel;
    private PenilaianTurunanArsipModel $arsipTurunanModel;

    public function __construct()
    {
        $this->db                = Database::connect();
        $this->arsipModel        = new PenilaianArsipModel();
        $this->arsipTurunanModel = new PenilaianTurunanArsipModel();
    }

    /**
     * Arsipkan seluruh Penilaian untuk satu Periode. Idempotent — jika
     * periode ini sebelumnya pernah diarsipkan (mis. sempat dibuka lagi
     * lalu ditutup ulang), arsip lama dihapus dulu supaya snapshot baru
     * selalu mencerminkan kondisi TERKINI persis di saat penutupan ini.
     *
     * @return int Jumlah baris KPI Induk yang berhasil diarsipkan.
     */
    public function arsipkanPeriode(int $periodeId, ?int $dibuatOleh = null): int
    {
        $periode = $this->db->table('periode')->where('id', $periodeId)->get()->getRowArray();
        if (!$periode) {
            throw new \InvalidArgumentException("Periode #{$periodeId} tidak ditemukan.");
        }

        // Rentang bulan Master Target yang dicakup Periode ini — dipakai
        // untuk meresolve Target (rata-rata bulanan) & Bobot (tahunan) yang
        // SESUNGGUHNYA dipakai saat skor dihitung, sama seperti resolver di
        // KpiPegawaiModel::getByPegawaiUntukPeriode().
        $periodeModel   = new PeriodeModel();
        $bulanTahunList = $periodeModel->getBulanTahunList($periode);
        $tahunList      = array_values(array_unique(array_column($bulanTahunList, 'tahun')));
        $tahunAnchor    = (int)date('Y', strtotime($periode['tgl_mulai']));

        // Hapus arsip lama (jika ada) — anak (turunan) dihapus lebih dulu
        // karena tidak ada FK constraint yang otomatis melakukan cascade.
        $arsipLamaIds = $this->arsipModel->where('periode_id', $periodeId)->findColumn('id') ?? [];
        if (!empty($arsipLamaIds)) {
            $this->arsipTurunanModel->whereIn('penilaian_arsip_id', $arsipLamaIds)->delete();
            $this->arsipModel->hapusByPeriode($periodeId);
        }

        // Ambil seluruh baris Penilaian pada periode ini, dengan konfigurasi
        // KPI/pegawai/divisi/direktorat TERKINI (inilah kondisi yang akan
        // dibekukan) — LEFT JOIN dipakai untuk kpi_pegawai/divisi/direktorat
        // karena data itu bisa saja sudah dihapus/diubah, tapi Penilaian itu
        // sendiri (dan detail Turunannya) tetap harus diarsipkan apa adanya.
        $rows = $this->db->table('penilaian p')
            ->select('p.id as penilaian_id, p.pegawai_id, p.kpi_id,
                      p.realisasi, p.realisasi_harian,
                      p.skor, p.nilai_kontribusi, p.catatan, p.status,
                      p.submitted_at, p.approved_by, p.approved_at, p.reject_note,
                      p.input_by,
                      pg.nama as pegawai_nama, pg.nip as pegawai_nip, pg.jabatan as pegawai_jabatan,
                      pg.divisi_id,
                      d.nama as divisi_nama, dir.nama as direktorat_nama,
                      k.kode as kpi_kode, k.nama_kpi as kpi_nama, k.satuan as kpi_satuan,
                      k.perspektif as kpi_perspektif,
                      k.polarity, k.perubahan_polarity, k.sifat_khusus,
                      k.toleransi_skor4, k.toleransi_skor3, k.toleransi_skor2,
                      kp.id as kpi_pegawai_id')
            ->join('kpi_unit k', 'k.id = p.kpi_id')
            ->join('pegawai pg', 'pg.id = p.pegawai_id', 'left')
            ->join('divisi d', 'd.id = pg.divisi_id', 'left')
            ->join('direktorat dir', 'dir.id = d.direktorat_id', 'left')
            ->join('kpi_pegawai kp', 'kp.kpi_id = p.kpi_id AND kp.pegawai_id = p.pegawai_id', 'left')
            ->where('p.periode_id', $periodeId)
            ->get()->getResultArray();

        if (empty($rows)) {
            return 0;
        }

        // Resolve Bobot (Tahunan) & Target (rata-rata Bulanan) dari Master
        // Target untuk seluruh KPI Induk yang muncul di baris Penilaian ini
        // — dilakukan satu kali secara batch, bukan per-baris.
        $kpiPegawaiIds = array_values(array_unique(array_filter(array_column($rows, 'kpi_pegawai_id'))));
        $targetIndukIndexed = (new KpiPegawaiTargetBulananModel())
            ->getIndexedByRefAndTahunList($kpiPegawaiIds, $tahunList);
        $bobotIndukIndexed = (new KpiPegawaiBobotTahunanModel())
            ->getIndexedByRefAndTahun($kpiPegawaiIds, $tahunAnchor);

        foreach ($rows as &$row) {
            $kpId = $row['kpi_pegawai_id'];
            $row['kp_bobot']  = $kpId ? ($bobotIndukIndexed[$kpId] ?? null) : null;
            $row['kp_target'] = $kpId
                ? KpiPegawaiModel::hitungTargetEfektif($targetIndukIndexed[$kpId] ?? [], $bulanTahunList)
                : null;
        }
        unset($row);

        // Kumpulkan nama user (approved_by/input_by) sekali di awal, alih-alih
        // query berulang per baris.
        $userIds = array_unique(array_filter(array_merge(
            array_column($rows, 'approved_by'),
            array_column($rows, 'input_by')
        )));
        $userNames = [];
        if (!empty($userIds)) {
            $userRows = $this->db->table('users')->whereIn('id', $userIds)->get()->getResultArray();
            foreach ($userRows as $u) {
                $userNames[$u['id']] = $u['nama'];
            }
        }

        $now = date('Y-m-d H:i:s');
        $jumlahDiarsipkan = 0;

        foreach ($rows as $row) {
            $arsipId = $this->arsipModel->insert([
                'periode_id'         => $periodeId,
                'periode_nama'       => $periode['nama'],
                'periode_kode'       => $periode['kode'],
                'penilaian_id'       => $row['penilaian_id'],
                'pegawai_id'         => $row['pegawai_id'],
                'pegawai_nama'       => $row['pegawai_nama'] ?? '(pegawai dihapus)',
                'pegawai_nip'        => $row['pegawai_nip'] ?? null,
                'pegawai_jabatan'    => $row['pegawai_jabatan'] ?? null,
                'divisi_id'          => $row['divisi_id'] ?? null,
                'divisi_nama'        => $row['divisi_nama'] ?? null,
                'direktorat_nama'    => $row['direktorat_nama'] ?? null,
                'kpi_id'             => $row['kpi_id'],
                'kpi_kode'           => $row['kpi_kode'],
                'kpi_nama'           => $row['kpi_nama'],
                'kpi_satuan'         => $row['kpi_satuan'],
                'kpi_perspektif'     => $row['kpi_perspektif'],
                'polarity'           => $row['polarity'],
                'perubahan_polarity' => $row['perubahan_polarity'],
                'sifat_khusus'       => $row['sifat_khusus'],
                'toleransi_skor4'    => $row['toleransi_skor4'],
                'toleransi_skor3'    => $row['toleransi_skor3'],
                'toleransi_skor2'    => $row['toleransi_skor2'],
                // Bobot & Target Induk memakai nilai EFEKTIF dari Master
                // Target pada saat Periode ini ditutup — Bobot Tahunan untuk
                // tahun Periode ini, Target rata-rata Bulanan untuk rentang
                // bulan yang dicakup Periode ini (inilah yang sesungguhnya
                // dipakai saat skor dihitung).
                'bobot'              => $row['kp_bobot'] ?? 0,
                'target'             => $row['kp_target'],
                'realisasi'          => $row['realisasi'],
                'realisasi_harian'   => $row['realisasi_harian'],
                'skor'               => $row['skor'],
                'nilai_kontribusi'   => $row['nilai_kontribusi'],
                'catatan'            => $row['catatan'],
                'status'             => $row['status'],
                'submitted_at'       => $row['submitted_at'],
                'approved_by_nama'   => $userNames[$row['approved_by']] ?? null,
                'approved_at'        => $row['approved_at'],
                'reject_note'        => $row['reject_note'],
                'input_by_nama'      => $userNames[$row['input_by']] ?? null,
                'arsip_dibuat_oleh'  => $dibuatOleh,
                'created_at'         => $now,
            ]);

            $jumlahDiarsipkan++;

            // Arsipkan Parameter Turunan (jika ada) untuk baris Penilaian ini
            // — Bobot & Target EFEKTIF dari Master Target, sama seperti Induk.
            $turunanRows = $this->db->table('penilaian_turunan pt')
                ->select('pt.realisasi, pt.realisasi_harian, pt.skor, pt.nilai_kontribusi, pt.catatan,
                          kpt.id as kpi_pegawai_turunan_id,
                          kpt.nama_turunan, kpt.satuan, kpt.polarity, kpt.perubahan_polarity,
                          kpt.sifat_khusus, kpt.toleransi_skor4, kpt.toleransi_skor3, kpt.toleransi_skor2, kpt.urutan')
                ->join('kpi_pegawai_turunan kpt', 'kpt.id = pt.kpi_pegawai_turunan_id')
                ->where('pt.penilaian_id', $row['penilaian_id'])
                ->orderBy('kpt.urutan', 'ASC')
                ->get()->getResultArray();

            if (!empty($turunanRows)) {
                $turunanIds = array_column($turunanRows, 'kpi_pegawai_turunan_id');
                $targetTurunanIndexed = (new KpiPegawaiTurunanTargetBulananModel())
                    ->getIndexedByRefAndTahunList($turunanIds, $tahunList);
                $bobotTurunanIndexed = (new KpiPegawaiTurunanBobotTahunanModel())
                    ->getIndexedByRefAndTahun($turunanIds, $tahunAnchor);

                foreach ($turunanRows as &$t) {
                    $tId = $t['kpi_pegawai_turunan_id'];
                    $t['bobot']  = $bobotTurunanIndexed[$tId] ?? null;
                    $t['target'] = KpiPegawaiModel::hitungTargetEfektif($targetTurunanIndexed[$tId] ?? [], $bulanTahunList);
                }
                unset($t);
            }

            foreach ($turunanRows as $t) {
                $this->arsipTurunanModel->insert([
                    'penilaian_arsip_id' => $arsipId,
                    'nama_turunan'       => $t['nama_turunan'],
                    'satuan'             => $t['satuan'],
                    'polarity'           => $t['polarity'],
                    'perubahan_polarity' => $t['perubahan_polarity'],
                    'sifat_khusus'       => $t['sifat_khusus'],
                    'toleransi_skor4'    => $t['toleransi_skor4'],
                    'toleransi_skor3'    => $t['toleransi_skor3'],
                    'toleransi_skor2'    => $t['toleransi_skor2'],
                    'bobot'              => $t['bobot'] ?? 0,
                    'target'             => $t['target'],
                    'realisasi'          => $t['realisasi'],
                    'realisasi_harian'   => $t['realisasi_harian'],
                    'skor'               => $t['skor'],
                    'nilai_kontribusi'   => $t['nilai_kontribusi'],
                    'catatan'            => $t['catatan'],
                    'urutan'             => $t['urutan'],
                ]);
            }
        }

        return $jumlahDiarsipkan;
    }
}
