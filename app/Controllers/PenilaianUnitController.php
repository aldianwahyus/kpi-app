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
        $periodeAktif = $this->periodeModel->getAktif();
        if (!$periodeAktif) {
            return redirect()->to(base_url('penilaian-unit'))
                            ->with('error', 'Tidak ada periode aktif.');
        }

        $divisi  = $this->divisiModel->find($divisiId);
        $kpiList = $this->kpiUnitModel->getByDirektorat($divisi['direktorat_id']);

        $targets = $this->request->getPost('target')    ?? [];
        $reals   = $this->request->getPost('realisasi') ?? [];
        $catatan = $this->request->getPost('catatan')   ?? [];

        foreach ($kpiList as $kpi) {
            $kpiId     = $kpi['id'];
            $target    = (float)($targets[$kpiId]  ?? 0);
            $realisasi = (float)($reals[$kpiId]    ?? 0);

            if ($target == 0 && $realisasi == 0) continue;

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
        $kpiId     = $this->request->getPost('kpi_id');
        $target    = (float)$this->request->getPost('target');
        $realisasi = (float)$this->request->getPost('realisasi');

        $kpi = $this->kpiUnitModel->find($kpiId);
        if (!$kpi) {
            return $this->response->setJSON(['capaian'=>0,'pct'=>'0%']);
        }

        $capaian = ($target > 0 && $realisasi > 0)
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
            'color'      => $capaian >= 1
                ? 'success'
                : ($capaian >= 0.76
                    ? 'primary'
                    : ($capaian >= 0.61 ? 'warning' : 'danger')),
        ]);
    }
}