<?php
namespace App\Controllers;

use App\Models\PenilaianModel;
use App\Models\PeriodeModel;
use App\Models\DivisiModel;
use App\Models\DirektoratModel;
use App\Services\KpiCalculationService;

class RekapController extends BaseController
{
    protected PenilaianModel        $penilaianModel;
    protected PeriodeModel          $periodeModel;
    protected DivisiModel           $divisiModel;
    protected DirektoratModel       $direktoratModel;
    protected KpiCalculationService $calculator;

    public function __construct()
    {
        $this->penilaianModel  = new PenilaianModel();
        $this->periodeModel    = new PeriodeModel();
        $this->divisiModel     = new DivisiModel();
        $this->direktoratModel = new DirektoratModel();
        $this->calculator      = new KpiCalculationService();
    }

    // ── Rekap semua pegawai ──────────────────────────────────
    public function index(): string
    {
        $check = $this->checkMenuAccess('penilaian');
        if ($check !== true) return $check;
        
        $periodes     = $this->periodeModel->getAllOrdered();
        $periodeAktif = $this->periodeModel->getAktif();
        $divisiList   = $this->divisiModel->getActive();
        $direktoratList = $this->direktoratModel->getActive();

        // Filter dari request
        $periodeId  = $this->request->getGet('periode_id')
                      ?? ($periodeAktif['id'] ?? null);
        $divisiId   = $this->request->getGet('divisi_id')   ?? '';
        $direktoratId = $this->request->getGet('direktorat_id') ?? '';
        $search     = $this->request->getGet('search')      ?? '';

        $rekap = [];
        if ($periodeId) {
            $rekap = $this->penilaianModel->getRekapKombinasi((int)$periodeId);
            // ... filter tetap sama
        }

        // Pagination manual
        $perPage = 25;
        $page    = (int)($this->request->getGet('page') ?? 1);
        $total   = count($rekap);
        $totalPages = (int)ceil($total / $perPage);
        $offset  = ($page - 1) * $perPage;
        $rekapPaged = array_slice($rekap, $offset, $perPage);

        $stats = $this->hitungStatistik($rekap); // statistik dari semua data, bukan yang di-paging

        return view('layouts/main', [
            'title'   => 'Rekap & Ranking KPI',
            'content' => view('rekap/_content', [
                'rekap'          => $rekapPaged, // 1. UBAH KE $rekapPaged AGAR TER-PAGING
                'periodes'       => $periodes,
                'periodeAktif'   => $periodeAktif,
                'periodeId'      => $periodeId,
                'divisiList'     => $divisiList,
                'divisiId'       => $divisiId,
                'direktoratList' => $direktoratList,
                'direktoratId'   => $direktoratId,
                'search'         => $search,
                'stats'          => $stats,
                
                // 2. TAMBAHKAN TIGA VARIABEL PAGINATION INI
                'total'          => $total,
                'page'           => $page,
                'totalPages'     => $totalPages,
            ]),
        ]);
    }

    // ── Detail penilaian satu pegawai ────────────────────────
    public function detail(int $pegawaiId): string
    {
        $periodeId = $this->request->getGet('periode_id');
        if (!$periodeId) {
            $periodeAktif = $this->periodeModel->getAktif();
            $periodeId    = $periodeAktif['id'] ?? null;
        }

        if (!$periodeId) {
            return redirect()->to(base_url('rekap'))
                             ->with('error', 'Pilih periode terlebih dahulu.');
        }

        $pegawai    = (new \App\Models\PegawaiModel())->find($pegawaiId);
        $periode    = $this->periodeModel->find($periodeId);
        $nilaiAkhir = $this->penilaianModel->getNilaiAkhir($pegawaiId, (int)$periodeId);
        $grade      = $nilaiAkhir > 0 ? $this->calculator->getGrade($nilaiAkhir) : null;
        $gradeLabel = $grade ? $this->calculator->getGradeLabel($grade) : '—';

        // Detail per KPI
        $detail = $this->penilaianModel->db->table('penilaian p')
            ->select('p.*, k.nama_kpi, k.kode, k.satuan,
                      k.polarity, k.perubahan_polarity,
                      k.perspektif, kd.bobot')
            ->join('kpi_unit k', 'k.id = p.kpi_id')
            ->join('kpi_divisi kd',
                   'kd.kpi_id = p.kpi_id AND kd.divisi_id = ' . ($pegawai['divisi_id'] ?? 0))
            ->where('p.pegawai_id', $pegawaiId)
            ->where('p.periode_id', $periodeId)
            ->orderBy('k.perspektif', 'ASC')
            ->get()->getResultArray();

        // Kelompokkan per perspektif
        $detailGrouped = [];
        foreach ($detail as $row) {
            $detailGrouped[$row['perspektif']][] = $row;
        }

        // Rekap per perspektif
        $perspektifRekap = $this->penilaianModel->getRekapPerspektif(
            $pegawaiId, (int)$periodeId
        );

        return view('layouts/main', [
            'title'   => 'Detail KPI — ' . $pegawai['nama'],
            'content' => view('rekap/_detail', [
                'pegawai'        => $pegawai,
                'periode'        => $periode,
                'nilaiAkhir'     => $nilaiAkhir,
                'grade'          => $grade,
                'gradeLabel'     => $gradeLabel,
                'detailGrouped'  => $detailGrouped,
                'perspektifRekap'=> $perspektifRekap,
                'periodeId'      => $periodeId,
            ]),
        ]);
    }

    // ── Hitung statistik ─────────────────────────────────────
    private function hitungStatistik(array $rekap): array
    {
        if (empty($rekap)) {
            return [
                'avg'   => 0, 'max' => 0, 'min' => 0,
                'count' => 0,
                'grade_counts' => ['A'=>0,'B'=>0,'C'=>0,'D'=>0,'E'=>0],
            ];
        }

        $values      = array_column($rekap, 'nilai_akhir');
        $gradeCounts = ['A'=>0,'B'=>0,'C'=>0,'D'=>0,'E'=>0];
        foreach ($rekap as $r) {
            $g = $r['grade'] ?? '—';
            if (isset($gradeCounts[$g])) $gradeCounts[$g]++;
        }

        return [
            'avg'          => round(array_sum($values) / count($values), 2),
            'max'          => round(max($values), 2),
            'min'          => round(min($values), 2),
            'count'        => count($rekap),
            'grade_counts' => $gradeCounts,
        ];
    }
}