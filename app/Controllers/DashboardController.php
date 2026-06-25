<?php
namespace App\Controllers;

use App\Models\PenilaianModel;
use App\Models\PegawaiModel;
use App\Models\PeriodeModel;
use App\Models\DivisiModel;
use App\Services\KpiCalculationService;

class DashboardController extends BaseController
{
    protected PenilaianModel        $penilaianModel;
    protected PegawaiModel          $pegawaiModel;
    protected PeriodeModel          $periodeModel;
    protected DivisiModel           $divisiModel;
    protected KpiCalculationService $calculator;

    public function __construct()
    {
        $this->penilaianModel = new PenilaianModel();
        $this->pegawaiModel   = new PegawaiModel();
        $this->periodeModel   = new PeriodeModel();
        $this->divisiModel    = new DivisiModel();
        $this->calculator     = new KpiCalculationService();
    }

    public function index(): string
    {
        $check = $this->checkMenuAccess('penilaian');
        if ($check !== true) return $check;
        
        $periodeAktif = $this->periodeModel->getAktif();
        $role         = session()->get('role');
        $pegawaiId    = session()->get('pegawai_id');

        // ── Stat cards ────────────────────────────────────────
        $totalPegawai  = $this->pegawaiModel->where('is_active', 1)->countAllResults();

        $sudahDinilai  = 0;
        $belumDinilai  = 0;
        $topPegawai    = [];
        
        // PERBAIKAN: Inisialisasi array menggunakan format Grade Baru (M, SB, B, C)
        $gradeCounts   = ['M' => 0, 'SB' => 0, 'B' => 0, 'C' => 0];
        
        $avgFinancial  = 0;
        $avgCustomer   = 0;
        $avgInternal   = 0;
        $avgLearning   = 0;
        $nilaiSendiri  = null;
        $gradeSendiri  = null;

        if ($periodeAktif) {
            $periodeId = $periodeAktif['id'];

            // Rekap semua pegawai
            $rekap = $this->penilaianModel->getRekapKombinasi($periodeId);

            $sudahDinilai = count($rekap);
            $belumDinilai = $totalPegawai - $sudahDinilai;

            // Top 5 pegawai
            $topPegawai = array_slice($rekap, 0, 5);

            // Hitung distribusi grade
            foreach ($rekap as $row) {
                $grade = $this->calculator->getGrade((float)$row['nilai_akhir']);
                // Memastikan grade yang dihasilkan service cocok dengan key grade baru
                if (isset($gradeCounts[$grade])) {
                    $gradeCounts[$grade]++;
                }
            }

            // Rata-rata per perspektif
            $perspektifData = $this->getRataPerPerspektif($periodeId);
            $avgFinancial   = $perspektifData['Financial']        ?? 0;
            $avgCustomer    = $perspektifData['Customer']         ?? 0;
            $avgInternal    = $perspektifData['Internal Process'] ?? 0;
            $avgLearning    = $perspektifData['Learning & Growth']?? 0;

            // Nilai sendiri jika role pegawai
            if ($role === 'pegawai' && $pegawaiId) {
                $nilaiSendiri = $this->penilaianModel->getNilaiAkhir($pegawaiId, $periodeId);
                $gradeSendiri = $nilaiSendiri > 0
                    ? $this->calculator->getGrade($nilaiSendiri)
                    : null;
            }
        }

        return view('layouts/main', [
            'title'    => 'Dashboard',
            'content'  => view('dashboard/_content', [
                'total_pegawai'  => $totalPegawai,
                'sudah_dinilai'  => $sudahDinilai,
                'belum_dinilai'  => $belumDinilai,
                'periode_aktif'  => $periodeAktif ? $periodeAktif['nama'] : 'Belum ada',
                'top_pegawai'    => $topPegawai,
                'grade_counts'   => $gradeCounts,
                'avg_financial'  => round($avgFinancial, 2),
                'avg_customer'   => round($avgCustomer, 2),
                'avg_internal'   => round($avgInternal, 2),
                'avg_learning'   => round($avgLearning, 2),
                'periode_aktif_obj' => $periodeAktif,
                'role'           => $role,
                'nilai_sendiri'  => $nilaiSendiri,
                'grade_sendiri'  => $gradeSendiri,
            ]),
            'extra_js' => view('dashboard/_scripts', [
                'avg_financial' => round($avgFinancial, 2),
                'avg_customer'  => round($avgCustomer, 2),
                'avg_internal'  => round($avgInternal, 2),
                'avg_learning'  => round($avgLearning, 2),
                'grade_counts'  => $gradeCounts,
            ]),
        ]);
    }

    // ── Hitung rata-rata capaian per perspektif ───────────────
    private function getRataPerPerspektif(int $periodeId): array
    {
        $rows = $this->penilaianModel->db->table('penilaian p')
            ->select('k.perspektif, AVG(p.capaian) * 100 as avg_capaian')
            ->join('kpi_unit k', 'k.id = p.kpi_id')
            ->where('p.periode_id', $periodeId)
            ->groupBy('k.perspektif')
            ->get()->getResultArray();

        $result = [];
        foreach ($rows as $row) {
            $result[$row['perspektif']] = (float)$row['avg_capaian'];
        }
        return $result;
    }
}