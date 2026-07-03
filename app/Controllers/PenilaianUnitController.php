<?php
namespace App\Controllers;

use App\Models\PenilaianUnitModel;
use App\Models\DivisiModel;
use App\Models\KpiUnitModel;
use App\Models\PeriodeModel;
use App\Services\KpiCalculationService;

class PenilaianUnitController extends BaseController
{
    protected PenilaianUnitModel    $penilaianUnitModel;
    protected DivisiModel           $divisiModel;
    protected KpiUnitModel          $kpiUnitModel;
    protected PeriodeModel          $periodeModel;
    protected KpiCalculationService $calculator;

    public function __construct()
    {
        $this->penilaianUnitModel = new PenilaianUnitModel();
        $this->divisiModel        = new DivisiModel();
        $this->kpiUnitModel       = new KpiUnitModel();
        $this->periodeModel       = new PeriodeModel();
        $this->calculator         = new KpiCalculationService();
    }

    // ── Daftar Divisi ────────────────────────────────────────
    public function index(): string
    { 
        $periodeAktif = $this->periodeModel->getAktif();
        $grouped      = $this->divisiModel->getGroupedByDirektorat();
        $rekap        = [];

        // Drafter & Approver HANYA boleh melihat divisinya sendiri di
        // daftar ini — tidak terkecuali. Sebelumnya seluruh divisi
        // perusahaan ditampilkan di sini meski form input sudah dilindungi
        // checkDivisiAccess(), sehingga nama dan rekap ringkas divisi lain
        // tetap terlihat oleh role yang seharusnya dibatasi.
        $role = session()->get('role');
        if (in_array($role, ['drafter', 'approver'])) {
            $myPegawaiId = session()->get('pegawai_id');
            $myDivisiId  = $myPegawaiId
                ? ($this->divisiModel->db->table('pegawai')->where('id', $myPegawaiId)->get()->getRowArray()['divisi_id'] ?? null)
                : null;

            $filteredGrouped = [];
            foreach ($grouped as $direktoratNama => $divisiList) {
                $matching = array_values(array_filter($divisiList, fn($d) => $d['id'] == $myDivisiId));
                if (!empty($matching)) {
                    $filteredGrouped[$direktoratNama] = $matching;
                }
            }
            $grouped = $filteredGrouped;
        }

        if ($periodeAktif) {
            $rows = $this->penilaianUnitModel->getRekapPeriode($periodeAktif['id']);
            foreach ($rows as $row) {
                $rekap[$row['divisi_id']] = $row;
            }
        }

        return view('layouts/main', [
            'title'   => 'Penilaian KPI Unit',
            'content' => view('penilaian_unit/_content', [
                'grouped'      => $grouped,
                'rekap'        => $rekap,
                'periodeAktif' => $periodeAktif,
            ]),
        ]);
    }

    // ── Form Input KPI Unit ──────────────────────────────────
    public function form(int $divisiId): string
    {
        $authCheck = $this->checkDivisiAccess($divisiId);
        if ($authCheck !== true) return $authCheck;

        $periodeAktif = $this->periodeModel->getAktif();
        if (!$periodeAktif) {
            return redirect()->to(base_url('penilaian-unit'))
                             ->with('error', 'Tidak ada periode aktif.');
        }

        $divisi = $this->divisiModel->find($divisiId);
        if (!$divisi) {
            return redirect()->to(base_url('penilaian-unit'))
                             ->with('error', 'Divisi tidak ditemukan.');
        }

        // Ambil KPI Unit sesuai direktorat divisi
        $kpiList = $this->kpiUnitModel->getByDirektorat($divisi['direktorat_id']);
        if (empty($kpiList)) {
            return redirect()->to(base_url('penilaian-unit'))
                             ->with('error',
                                 "Direktorat divisi ini belum memiliki KPI Unit. "
                                 . "Setup KPI Unit di Master → Direktorat & KPI Unit.");
        }

        $existing   = $this->penilaianUnitModel->getIndexedByKpi($divisiId, $periodeAktif['id']);
        $nilaiAkhir = $this->penilaianUnitModel->getNilaiAkhir($divisiId, $periodeAktif['id']);

        // Kelompokkan KPI per perspektif
        $kpiGrouped = [];
        foreach ($kpiList as $kpi) {
            $kpiGrouped[$kpi['perspektif']][] = $kpi;
        }

        return view('layouts/main', [
            'title'   => 'KPI Unit — ' . $divisi['nama'],
            'content' => view('penilaian_unit/_form', [
                'divisi'       => $divisi,
                'kpiGrouped'   => $kpiGrouped,
                'kpiList'      => $kpiList,
                'existing'     => $existing,
                'periodeAktif' => $periodeAktif,
                'nilaiAkhir'   => $nilaiAkhir,
                'grade'        => $nilaiAkhir > 0
                    ? $this->calculator->getGrade($nilaiAkhir)
                    : null,
                'totalKpi'     => count($kpiList),
            ]),
        ]);
    }

    // ── Simpan KPI Unit — tanpa bobot ────────────────────────
    public function store(int $divisiId)
    {
        $authCheck = $this->checkDivisiAccess($divisiId);
        if ($authCheck !== true) return $authCheck;

        $periodeAktif = $this->periodeModel->getAktif();
        if (!$periodeAktif) {
            return redirect()->to(base_url('penilaian-unit'))
                            ->with('error', 'Tidak ada periode aktif.');
        }

        $divisi = $this->divisiModel->find($divisiId);
        if (!$divisi) {
            return redirect()->to(base_url('penilaian-unit'))
                            ->with('error', 'Divisi tidak ditemukan.');
        }

        $kpiList = $this->kpiUnitModel->getByDirektorat($divisi['direktorat_id']);

        $targets = $this->request->getPost('target')    ?? [];
        $reals   = $this->request->getPost('realisasi') ?? [];
        $catatan = $this->request->getPost('catatan')   ?? [];

        foreach ($kpiList as $kpi) {
            $kpiId        = $kpi['id'];
            $targetRaw    = $targets[$kpiId] ?? null;
            $realisasiRaw = $reals[$kpiId]   ?? null;

            // Lewati hanya jika baris ini benar-benar belum diisi sama sekali.
            // Realisasi = 0 yang sengaja diisi tetap disimpan & dihitung —
            // untuk KPI ber-polaritas 'min', 0 adalah capaian valid (bahkan
            // terbaik), bukan tanda "belum diisi".
            $targetKosong    = ($targetRaw === null || $targetRaw === '');
            $realisasiKosong = ($realisasiRaw === null || $realisasiRaw === '');
            if ($targetKosong && $realisasiKosong) continue;

            $target    = (float)($targetRaw ?? 0);
            $realisasi = (float)($realisasiRaw ?? 0);

            $capaian = $this->calculator->hitungCapaian(
                $target, $realisasi,
                $kpi['polarity'],
                $kpi['perubahan_polarity']
            );

            // Simpan tanpa nilai_kontribusi
            $this->penilaianUnitModel->upsert($divisiId, $kpiId, $periodeAktif['id'], [
                'target'    => $target,
                'realisasi' => $realisasi,
                'capaian'   => $capaian,
                'catatan'   => $catatan[$kpiId] ?? null,
                'input_by'  => session()->get('user_id'),
            ]);
        }

        return redirect()->to(base_url("penilaian-unit/form/$divisiId"))
                        ->with('success', 'KPI Unit berhasil disimpan!');
    }

    // ── AJAX Hitung Capaian ──────────────────────────────────
    public function ajaxHitung()
    {
        $kpiId        = (int)$this->request->getPost('kpi_id');
        $targetRaw    = $this->request->getPost('target');
        $realisasiRaw = $this->request->getPost('realisasi');
        $target       = (float)$targetRaw;
        $realisasi    = (float)$realisasiRaw;

        $kpi = $this->kpiUnitModel->find($kpiId);
        if (!$kpi) {
            return $this->response->setJSON(['capaian'=>0,'pct'=>'0%','csrf_hash'=>csrf_hash()]);
        }

        // Realisasi = 0 yang sengaja diisi tetap dihitung (valid untuk KPI
        // ber-polaritas 'min') — hanya field yang benar-benar kosong yang
        // tidak dihitung.
        $realisasiKosong = ($realisasiRaw === null || $realisasiRaw === '');
        $capaian = ($target > 0 && !$realisasiKosong)
            ? $this->calculator->hitungCapaian(
                $target, $realisasi,
                $kpi['polarity'],
                $kpi['perubahan_polarity']
              )
            : 0;

        $kontribusi = $capaian * (float)$kpi['bobot'];

        return $this->response->setJSON([
            'capaian'    => $capaian,
            'pct'        => round($capaian * 100, 2) . '%',
            'kontribusi' => round($kontribusi * 100, 2),
            'csrf_hash'  => csrf_hash(),
            'color'      => $capaian >= 1
                ? 'success'
                : ($capaian >= 0.76
                    ? 'primary'
                    : ($capaian >= 0.61 ? 'warning' : 'danger')),
        ]);
    }

    // ── Helper: Otorisasi akses divisi (dipakai form() & store()) ──
    private function checkDivisiAccess(int $divisiId)
    {
        $role = session()->get('role');
        if (!in_array($role, ['admin', 'hr', 'drafter', 'approver'])) {
            return $this->forbidden('Anda tidak memiliki kewenangan untuk mengakses KPI Unit.');
        }

        if (in_array($role, ['drafter', 'approver'])) {
            $myPegawaiId = session()->get('pegawai_id');
            $myPegawai   = $myPegawaiId
                ? $this->divisiModel->db->table('pegawai')->where('id', $myPegawaiId)->get()->getRowArray()
                : null;
            if (!$myPegawai || (int)$myPegawai['divisi_id'] !== $divisiId) {
                return $this->forbidden('Anda hanya dapat mengakses KPI Unit untuk divisi Anda sendiri.');
            }
        }

        return true;
    }
}