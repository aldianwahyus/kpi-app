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
    public function index()
    {
        $check = $this->checkMenuAccess('penilaian');
        if ($check !== true) return $check;
        
        $role      = session()->get('role');
        $myPegawaiId = session()->get('pegawai_id');

        // Drafter & Approver HANYA boleh melihat rekap untuk divisinya
        // sendiri — scope ini bersifat WAJIB dan diterapkan SEBELUM filter
        // dropdown opsional (divisi_id/direktorat_id) dipertimbangkan, agar
        // memilih divisi lain dari dropdown tidak pernah bisa menampilkan
        // data divisi tersebut untuk role yang dibatasi.
        $divisiScope = null;
        if (in_array($role, ['drafter', 'approver']) && $myPegawaiId) {
            $myPegawai   = (new \App\Models\PegawaiModel())->find($myPegawaiId);
            $divisiScope = $myPegawai['divisi_id'] ?? null;
        }

        $periodes     = $this->periodeModel->getAllOrdered();
        $periodeAktif = $this->periodeModel->getAktif();
        $divisiList   = $this->divisiModel->getActive();
        $direktoratList = $this->direktoratModel->getActive();

        // Untuk Drafter/Approver, dropdown filter divisi/direktorat tidak
        // relevan lagi (mereka hanya punya satu divisi), sehingga daftar
        // pilihan dropdown juga dipersempit agar tidak menyesatkan secara
        // visual — meski filter sebenarnya tetap dipaksa di level query.
        if ($divisiScope) {
            $divisiList = array_values(array_filter($divisiList, fn($d) => $d['id'] == $divisiScope));
            $myDivisiDirektoratId = $divisiList[0]['direktorat_id'] ?? null;
            $direktoratList = array_values(array_filter($direktoratList, fn($d) => $d['id'] == $myDivisiDirektoratId));
        }

        // Filter dari request
        $periodeId  = $this->request->getGet('periode_id')
                      ?? ($periodeAktif['id'] ?? null);
        $divisiId   = $this->request->getGet('divisi_id')   ?? '';
        $direktoratId = $this->request->getGet('direktorat_id') ?? '';
        $search     = $this->request->getGet('search')      ?? '';

        $rekap = [];
        if ($periodeId) {
            // Scope WAJIB divisi diterapkan langsung di level SQL untuk
            // Drafter/Approver — baris data divisi lain tidak pernah
            // dimuat ke memori sama sekali.
            $rekap = $this->penilaianModel->getRekapKombinasi((int)$periodeId, $divisiScope);

            // Filter berdasarkan Divisi (dropdown opsional — hanya relevan
            // untuk Admin/HR karena Drafter/Approver sudah dikunci di atas)
            if ($divisiId !== '' && !$divisiScope) {
                $rekap = array_values(array_filter($rekap,
                    fn($r) => (string)($r['divisi_id'] ?? '') === (string)$divisiId
                ));
            }

            // Filter berdasarkan Direktorat — divisi mana saja yang berada
            // di bawah direktorat terpilih (dropdown opsional, sama seperti
            // di atas — tidak relevan jika divisiScope sudah dikunci)
            if ($direktoratId !== '' && !$divisiScope) {
                $divisiIdsInDirektorat = array_column(
                    array_filter($divisiList,
                        fn($d) => (string)($d['direktorat_id'] ?? '') === (string)$direktoratId
                    ),
                    'id'
                );
                $rekap = array_values(array_filter($rekap,
                    fn($r) => in_array($r['divisi_id'] ?? null, $divisiIdsInDirektorat)
                ));
            }

            // Filter berdasarkan nama pegawai (pencarian)
            if ($search !== '') {
                $rekap = array_values(array_filter($rekap,
                    fn($r) => stripos($r['nama'] ?? '', $search) !== false
                ));
            }
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
    public function detail(int $pegawaiId)
    {
        $check = $this->checkMenuAccess('penilaian');
        if ($check !== true) return $check;

        if (!$this->canAccessPegawai($pegawaiId)) return $this->forbidden();

        $periodeId = $this->request->getGet('periode_id');
        if (!$periodeId) {
            $periodeAktif = $this->periodeModel->getAktif();
            $periodeId    = $periodeAktif['id'] ?? null;
        }

        if (!$periodeId) {
            return redirect()->to(base_url('rekap'))
                             ->with('error', 'Pilih periode terlebih dahulu.');
        }

        $pegawai = (new \App\Models\PegawaiModel())->find($pegawaiId);
        if (!$pegawai) {
            return redirect()->to(base_url('rekap'))
                             ->with('error', 'Pegawai tidak ditemukan.');
        }

        $periode    = $this->periodeModel->find($periodeId);
        $nilaiAkhir = $this->penilaianModel->getNilaiAkhir($pegawaiId, (int)$periodeId);
        $grade      = $nilaiAkhir > 0 ? $this->calculator->getGrade($nilaiAkhir) : null;
        $gradeLabel = $grade ? $this->calculator->getGradeLabel($grade) : '—';

        // Detail per KPI
        $detail = $this->penilaianModel->db->table('penilaian p')
            ->select('p.*, k.nama_kpi, k.kode, k.satuan,
                      k.polarity, k.perubahan_polarity,
                      k.perspektif, kp.bobot')
            ->join('kpi_unit k', 'k.id = p.kpi_id')
            ->join('kpi_pegawai kp',
                   'kp.kpi_id = p.kpi_id AND kp.pegawai_id = p.pegawai_id')
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
                'grade_counts' => ['IS'=>0,'SB'=>0,'B'=>0,'C'=>0],
            ];
        }

        $values      = array_column($rekap, 'nilai_akhir');
        $gradeCounts = ['IS'=>0,'SB'=>0,'B'=>0,'C'=>0];
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