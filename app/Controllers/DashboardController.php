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
        
        $periodeAktif = $this->periodeModel->getAktif();
        $role         = session()->get('role');
        $pegawaiId    = session()->get('pegawai_id');

        // Drafter & Approver HANYA boleh melihat statistik Dashboard untuk
        // divisinya sendiri — tidak terkecuali. Hanya Admin (dan HR, yang
        // memiliki kewenangan lintas-divisi yang sama dengan Admin di
        // seluruh modul lain) yang melihat data perusahaan secara utuh.
        $divisiScope = null;
        if (in_array($role, ['drafter', 'approver']) && $pegawaiId) {
            $myPegawai   = $this->pegawaiModel->find($pegawaiId);
            $divisiScope = $myPegawai['divisi_id'] ?? null;
        }

        // ── Stat cards ────────────────────────────────────────
        $totalPegawaiQuery = $this->pegawaiModel->where('is_active', 1);
        if ($divisiScope) {
            $totalPegawaiQuery->where('divisi_id', $divisiScope);
        }
        $totalPegawai = $totalPegawaiQuery->countAllResults();

        $sudahDinilai  = 0;
        $belumDinilai  = 0;
        $topPegawai    = [];
        
        // Grade sesuai skema kriteria pencapaian (Istimewa/Baik/Cukup/Kurang)
        $gradeCounts   = ['IS' => 0, 'SB' => 0, 'B' => 0, 'C' => 0];
        
        $avgFinancial  = 0;
        $avgCustomer   = 0;
        $avgInternal   = 0;
        $avgLearning   = 0;
        $nilaiSendiri  = null;
        $gradeSendiri  = null;

        if ($periodeAktif) {
            $periodeId = $periodeAktif['id'];

            // Rekap semua pegawai (difilter divisi untuk Drafter/Approver)
            $rekap = $this->penilaianModel->getRekapKombinasi($periodeId, $divisiScope);

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

            // Rata-rata per perspektif (difilter divisi untuk Drafter/Approver)
            $perspektifData = $this->getRataPerPerspektif($periodeId, $divisiScope);
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

        // ── Daftar lengkap status penilaian per pegawai ───────
        // Mengambil seluruh pegawai aktif (difilter divisi untuk
        // Drafter/Approver) beserta status penilaiannya pada periode
        // aktif — dipakai untuk tabel "Belum Dinilai / Sudah Dinilai"
        // di Dashboard agar ketahuan siapa saja yang belum dinilai.
        $daftarStatusPegawai = [];
        if ($periodeAktif) {
            $semuaPegawaiQuery = $this->pegawaiModel->db->table('pegawai p')
                ->select('p.id, p.nama, p.jabatan, p.unit,
                          d.nama as divisi,
                          SUM(pn.nilai_kontribusi) as nilai_akhir,
                          COUNT(pn.id) as jumlah_kpi_dinilai,
                          MAX(pn.status) as status_penilaian')
                ->join('divisi d', 'd.id = p.divisi_id', 'left')
                ->join('penilaian pn',
                       'pn.pegawai_id = p.id AND pn.periode_id = ' . (int)$periodeAktif['id'],
                       'left')
                ->where('p.is_active', 1);

            if ($divisiScope) {
                $semuaPegawaiQuery->where('p.divisi_id', $divisiScope);
            }

            $daftarStatusPegawai = $semuaPegawaiQuery
                ->groupBy('p.id')
                ->orderBy('d.nama', 'ASC')
                ->orderBy('p.nama', 'ASC')
                ->get()->getResultArray();
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
                'daftar_status_pegawai' => $daftarStatusPegawai,
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
    private function getRataPerPerspektif(int $periodeId, ?int $divisiId = null): array
    {
        $builder = $this->penilaianModel->db->table('penilaian p')
            ->select('k.perspektif, AVG(p.skor) as avg_capaian')
            ->join('kpi_unit k', 'k.id = p.kpi_id')
            ->where('p.periode_id', $periodeId);

        if ($divisiId !== null) {
            $builder->join('pegawai pg', 'pg.id = p.pegawai_id')
                    ->where('pg.divisi_id', $divisiId);
        }

        $rows = $builder
            ->groupBy('k.perspektif')
            ->get()->getResultArray();

        $result = [];
        foreach ($rows as $row) {
            $result[$row['perspektif']] = (float)$row['avg_capaian'];
        }
        return $result;
    }
}