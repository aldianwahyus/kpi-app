<?php

namespace App\Controllers;

use App\Models\KpiPegawaiModel;
use App\Models\KpiPegawaiTurunanModel;
use App\Models\KpiPegawaiTargetBulananModel;
use App\Models\KpiPegawaiBobotTahunanModel;
use App\Models\KpiPegawaiTurunanTargetBulananModel;
use App\Models\KpiPegawaiTurunanBobotTahunanModel;
use App\Models\PegawaiModel;

/**
 * Master Target — isi Target (per bulan, 1 tahun penuh) & Bobot (satu nilai
 * per tahun) untuk seluruh parameter KPI yang sudah di-assign ke pegawai di
 * layar "KPI Per Pegawai". Menggantikan sepenuhnya layar "Setup Target per
 * Periode" — Periode kini hanya menentukan RENTANG BULAN mana yang dipakai
 * (lihat PeriodeModel::getBulanTahunList()), bukan tempat mengisi Target.
 *
 * Bobot KPI Induk yang sudah punya Parameter Turunan tidak diinput langsung
 * di sini — dihitung otomatis sebagai SUM Bobot seluruh Turunannya, karena
 * Bobot Induk pada dasarnya adalah agregat dari Turunan-turunannya.
 */
class MasterTargetController extends BaseController
{
    protected KpiPegawaiModel                       $kpiPegawaiModel;
    protected KpiPegawaiTurunanModel                 $kpiPegawaiTurunanModel;
    protected KpiPegawaiTargetBulananModel           $targetBulananModel;
    protected KpiPegawaiBobotTahunanModel            $bobotTahunanModel;
    protected KpiPegawaiTurunanTargetBulananModel    $turunanTargetBulananModel;
    protected KpiPegawaiTurunanBobotTahunanModel     $turunanBobotTahunanModel;
    protected PegawaiModel                           $pegawaiModel;
    private string                                   $pesanErrorTerakhir = '';
    private string                                   $fieldIdErrorTerakhir = '';

    private const NAMA_BULAN_KOLOM = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function __construct()
    {
        $this->kpiPegawaiModel           = new KpiPegawaiModel();
        $this->kpiPegawaiTurunanModel    = new KpiPegawaiTurunanModel();
        $this->targetBulananModel        = new KpiPegawaiTargetBulananModel();
        $this->bobotTahunanModel         = new KpiPegawaiBobotTahunanModel();
        $this->turunanTargetBulananModel = new KpiPegawaiTurunanTargetBulananModel();
        $this->turunanBobotTahunanModel  = new KpiPegawaiTurunanBobotTahunanModel();
        $this->pegawaiModel              = new PegawaiModel();
    }

    // ── Daftar pegawai yang sudah memiliki KPI Per Pegawai ───────────
    public function index()
    {
        $check = $this->checkMenuAccess('master_target');
        if ($check !== true) return $check;

        $pegawaiList = $this->pegawaiModel->getAllWithDivisi();

        $role = session()->get('role');
        if (in_array($role, ['drafter', 'approver'])) {
            $myPegawaiId = session()->get('pegawai_id');
            $myDivisiId  = $myPegawaiId
                ? ($this->pegawaiModel->find($myPegawaiId)['divisi_id'] ?? null)
                : null;
            $pegawaiList = array_values(array_filter(
                $pegawaiList,
                fn($p) => $myDivisiId && $p['divisi_id'] == $myDivisiId
            ));
        }

        $grouped = [];
        foreach ($pegawaiList as $p) {
            $jumlahKpi = count($this->kpiPegawaiModel->getByPegawai($p['id']));
            if ($jumlahKpi === 0) {
                continue;
            }
            $p['jumlah_kpi'] = $jumlahKpi;
            $key = $p['nama_divisi'] ?? 'Belum Ada Divisi';
            $grouped[$key][] = $p;
        }

        return view('layouts/main', [
            'title'   => 'Master Target',
            'content' => view('master_target/_list', ['grouped' => $grouped]),
        ]);
    }

    // ── Form isi Target Bulanan & Bobot Tahunan satu pegawai ─────────
    public function edit(int $pegawaiId)
    {
        $check = $this->checkMenuAccess('master_target');
        if ($check !== true) return $check;

        if (!$this->canAccessPegawai($pegawaiId)) return $this->forbidden();

        $pegawai = $this->pegawaiModel->find($pegawaiId);
        if (!$pegawai) {
            return redirect()->to(base_url('master-target'))
                             ->with('error', 'Pegawai tidak ditemukan.');
        }

        $tahun = (int)($this->request->getGet('tahun') ?: date('Y'));

        $assigned = $this->kpiPegawaiModel->getByPegawai($pegawaiId);
        if (empty($assigned)) {
            return redirect()->to(base_url('master-target'))
                             ->with('error', "Pegawai {$pegawai['nama']} belum memiliki KPI. Setup KPI Per Pegawai terlebih dahulu.");
        }

        $kpiIds       = array_column($assigned, 'id');
        $bobotIndexed = $this->bobotTahunanModel->getIndexedByRefAndTahun($kpiIds, $tahun);

        $turunanByInduk         = [];
        $allTurunanIds          = [];
        $targetBulananByInduk   = [];
        foreach ($assigned as $a) {
            $listT = $this->kpiPegawaiTurunanModel->getByKpiPegawai($a['id']);
            $turunanByInduk[$a['id']] = $listT;
            foreach ($listT as $t) {
                $allTurunanIds[] = $t['id'];
            }
            if (empty($listT)) {
                $targetBulananByInduk[$a['id']] = $this->targetBulananModel->getTahunPenuh($a['id'], $tahun);
            }
        }

        $turunanBobotIndexed    = empty($allTurunanIds) ? [] : $this->turunanBobotTahunanModel->getIndexedByRefAndTahun($allTurunanIds, $tahun);
        $targetBulananByTurunan = [];
        foreach ($allTurunanIds as $tId) {
            $targetBulananByTurunan[$tId] = $this->turunanTargetBulananModel->getTahunPenuh($tId, $tahun);
        }

        $assignedGrouped = [];
        foreach ($assigned as $row) {
            $assignedGrouped[$row['perspektif']][] = $row;
        }

        // Daftar pegawai lain yang sudah punya KPI — sumber untuk tombol
        // "Copy Target dari Pegawai Lain" (dikelompokkan per divisi, sama
        // seperti dropdown "Copy dari pegawai lain" di KPI Per Pegawai).
        $groupedPegawaiSumber = [];
        foreach ($this->pegawaiModel->getAllWithDivisi() as $p) {
            if ((int)$p['id'] === $pegawaiId) continue;
            if (count($this->kpiPegawaiModel->getByPegawai($p['id'])) === 0) continue;
            $key = $p['nama_divisi'] ?? 'Belum Ada Divisi';
            $groupedPegawaiSumber[$key][] = $p;
        }

        return view('layouts/main', [
            'title'   => 'Master Target — ' . $pegawai['nama'],
            'content' => view('master_target/_form', [
                'pegawai'                => $pegawai,
                'tahun'                  => $tahun,
                'assignedGrouped'        => $assignedGrouped,
                'turunanByInduk'         => $turunanByInduk,
                'bobotIndexed'           => $bobotIndexed,
                'turunanBobotIndexed'    => $turunanBobotIndexed,
                'targetBulananByInduk'   => $targetBulananByInduk,
                'targetBulananByTurunan' => $targetBulananByTurunan,
                'groupedPegawaiSumber'   => $groupedPegawaiSumber,
            ]),
        ]);
    }

    // ── Simpan Target Bulanan & Bobot Tahunan ────────────────────────
    public function save(int $pegawaiId)
    {
        $check = $this->checkMenuEdit('master_target');
        if ($check !== true) return $check;

        if (!$this->canAccessPegawai($pegawaiId)) return $this->forbidden();

        $tahun = (int)$this->request->getPost('tahun');
        if (!$tahun) {
            return redirect()->back()->withInput()->with('error', 'Tahun wajib diisi.');
        }

        $assigned = $this->kpiPegawaiModel->getByPegawai($pegawaiId);
        if (empty($assigned)) {
            return redirect()->to(base_url('master-target'))
                             ->with('error', 'Pegawai belum memiliki KPI.');
        }

        $targetPost        = $this->request->getPost('target')          ?? [];
        $bobotPost         = $this->request->getPost('bobot')           ?? [];
        $turunanTargetPost = $this->request->getPost('turunan_target')  ?? [];
        $turunanBobotPost  = $this->request->getPost('turunan_bobot')   ?? [];

        // ── Validasi ALL-OR-NOTHING — kumpulkan dulu seluruh rencana
        // penyimpanan, tolak semuanya jika ada satu saja yang tidak valid,
        // supaya tidak ada data yang tersimpan sebagian. Setiap kegagalan
        // di-redirect dengan withInput() (supaya seluruh isian yang sudah
        // diketik Admin TIDAK hilang) dan menandai field id yang bermasalah
        // (supaya browser langsung fokus & sorot ke situ, bukan Admin harus
        // mencari sendiri parameter mana yang belum lengkap). ──
        $planInduk   = [];
        $planTurunan = [];

        foreach ($assigned as $kpi) {
            $kpId        = (int)$kpi['id'];
            $listTurunan = $this->kpiPegawaiTurunanModel->getByKpiPegawai($kpId);

            if (empty($listTurunan)) {
                $bobotVal = $bobotPost[$kpId] ?? '';
                if ($bobotVal === '' || (float)$bobotVal <= 0 || (float)$bobotVal > 1) {
                    return redirect()->back()->withInput()
                                     ->with('error', 'Bobot KPI "' . $kpi['nama_kpi'] . '" wajib diisi, lebih besar dari 0, dan tidak lebih dari 1 (100%).')
                                     ->with('highlight_id', "bobot-{$kpId}");
                }

                $bulanTarget = $this->validasiTargetBulanan(
                    $targetPost[$kpId] ?? [], $kpi['polarity'] ?? 'max', 'KPI "' . $kpi['nama_kpi'] . '"', "target-{$kpId}"
                );
                if ($bulanTarget === false) {
                    return redirect()->back()->withInput()
                                     ->with('error', $this->pesanErrorTerakhir)
                                     ->with('highlight_id', $this->fieldIdErrorTerakhir);
                }

                $planInduk[$kpId] = ['bobot' => (float)$bobotVal, 'target' => $bulanTarget];
            } else {
                $sumBobotTurunan = 0.0;
                foreach ($listTurunan as $t) {
                    $tId      = (int)$t['id'];
                    $bobotVal = $turunanBobotPost[$tId] ?? '';
                    if ($bobotVal === '' || (float)$bobotVal <= 0 || (float)$bobotVal > 1) {
                        return redirect()->back()->withInput()
                                         ->with('error', 'Bobot Parameter Turunan "' . $t['nama_turunan'] . '" wajib diisi, lebih besar dari 0, dan tidak lebih dari 1 (100%).')
                                         ->with('highlight_id', "turunan-bobot-{$tId}");
                    }
                    $sumBobotTurunan += (float)$bobotVal;

                    $bulanTarget = $this->validasiTargetBulanan(
                        $turunanTargetPost[$tId] ?? [], $t['polarity'] ?? 'max', 'Parameter Turunan "' . $t['nama_turunan'] . '"', "turunan-target-{$tId}"
                    );
                    if ($bulanTarget === false) {
                        return redirect()->back()->withInput()
                                         ->with('error', $this->pesanErrorTerakhir)
                                         ->with('highlight_id', $this->fieldIdErrorTerakhir);
                    }

                    $planTurunan[$tId] = ['bobot' => (float)$bobotVal, 'target' => $bulanTarget];
                }

                // Bobot KPI Induk yang punya Turunan = SUM Bobot Turunannya
                // (bukan diinput terpisah) — lihat catatan kelas di atas.
                $planInduk[$kpId] = ['bobot' => round($sumBobotTurunan, 4), 'target' => null];
            }
        }

        $totalBobot = array_sum(array_column($planInduk, 'bobot'));
        if (round($totalBobot, 2) != 1.00) {
            return redirect()->back()->withInput()
                             ->with('error', 'Total Bobot seluruh KPI pegawai ini harus tepat 100%. Saat ini: ' . round($totalBobot * 100, 2) . '%.');
        }

        foreach ($planInduk as $kpId => $data) {
            $this->bobotTahunanModel->upsert($kpId, $tahun, $data['bobot']);
            if ($data['target'] !== null) {
                foreach ($data['target'] as $bulan => $val) {
                    $this->targetBulananModel->upsert($kpId, $tahun, $bulan, $val);
                }
            }
        }
        foreach ($planTurunan as $tId => $data) {
            $this->turunanBobotTahunanModel->upsert($tId, $tahun, $data['bobot']);
            foreach ($data['target'] as $bulan => $val) {
                $this->turunanTargetBulananModel->upsert($tId, $tahun, $bulan, $val);
            }
        }

        return redirect()->to(base_url("master-target/edit/{$pegawaiId}?tahun={$tahun}"))
                         ->with('success', 'Master Target berhasil disimpan.');
    }

    /**
     * Validasi 12 bulan Target untuk satu parameter (Induk tanpa Turunan,
     * atau satu Turunan). Polarity 'special' tidak memakai Target sama
     * sekali — dikembalikan sebagai array 12 bulan bernilai null tanpa
     * divalidasi. Mengembalikan `false` (dan mengisi $pesanErrorTerakhir +
     * $fieldIdErrorTerakhir, agar Admin langsung diarahkan fokus ke input
     * bulan yang bermasalah) jika ada bulan yang belum diisi/≤0.
     *
     * @return array<int,float|null>|false
     */
    private function validasiTargetBulanan(array $postBulan, string $polarity, string $label, string $fieldIdPrefix)
    {
        $hasil = [];
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            if ($polarity === 'special') {
                $hasil[$bulan] = null;
                continue;
            }
            $v = $postBulan[$bulan] ?? '';
            if ($v === '' || (float)$v <= 0) {
                $namaBulan = $this->namaBulan($bulan);
                $this->pesanErrorTerakhir    = "Target bulan {$namaBulan} untuk {$label} wajib diisi dan lebih besar dari 0.";
                $this->fieldIdErrorTerakhir  = "{$fieldIdPrefix}-{$bulan}";
                return false;
            }
            $hasil[$bulan] = (float)$v;
        }
        return $hasil;
    }

    private function namaBulan(int $bulan): string
    {
        return self::NAMA_BULAN_KOLOM[$bulan] ?? (string)$bulan;
    }

    // ── Salin Target/Bobot dari Pegawai Lain ─────────────────────────
    // Mencocokkan parameter KPI Induk berdasarkan kpi_id (KPI Unit yang
    // sama), dan Parameter Turunan berdasarkan nama_turunan (persis) di
    // bawah Induk yang sudah cocok — karena baris kpi_pegawai/kpi_pegawai_
    // turunan pegawai sumber & tujuan adalah baris yang BERBEDA walau
    // merujuk ke KPI/Turunan yang sama secara konsep.
    // TIDAK MENIMPA nilai yang sudah ada di pegawai tujuan (hanya mengisi
    // bulan/Bobot yang masih kosong) — supaya aman dipanggil berulang dan
    // tidak menghapus pekerjaan yang sudah dilakukan Admin sebelumnya.
    public function copyDariPegawai(int $pegawaiId)
    {
        $check = $this->checkMenuEdit('master_target');
        if ($check !== true) return $check;

        if (!$this->canAccessPegawai($pegawaiId)) return $this->forbidden();

        $sourceId = (int)$this->request->getPost('source_pegawai_id');
        $tahun    = (int)$this->request->getPost('tahun');

        if (!$sourceId || $sourceId === $pegawaiId) {
            return redirect()->back()->with('error', 'Pilih pegawai sumber yang valid.');
        }
        if (!$tahun) {
            return redirect()->back()->with('error', 'Tahun wajib diisi.');
        }
        if (!$this->canAccessPegawai($sourceId)) return $this->forbidden();

        $sourceKpiList = $this->kpiPegawaiModel->getByPegawai($sourceId);
        $targetKpiList = $this->kpiPegawaiModel->getByPegawai($pegawaiId);

        if (empty($sourceKpiList)) {
            return redirect()->back()->with('error', 'Pegawai sumber belum memiliki KPI.');
        }
        if (empty($targetKpiList)) {
            return redirect()->to(base_url('master-target'))->with('error', 'Pegawai tujuan belum memiliki KPI.');
        }

        // Indeks KPI sumber berdasarkan kpi_id (KPI Unit) — bukan kpi_pegawai_id,
        // karena itu berbeda antara pegawai sumber & tujuan.
        $sourceByKpiUnitId = [];
        foreach ($sourceKpiList as $s) {
            $sourceByKpiUnitId[$s['kpi_id']] = $s;
        }

        $indukTersalin    = 0;
        $turunanTersalin  = 0;
        $indukPerluDihitungUlang = [];

        foreach ($targetKpiList as $target) {
            $sourceMatch = $sourceByKpiUnitId[$target['kpi_id']] ?? null;
            if (!$sourceMatch) {
                continue; // KPI ini tidak ada di pegawai sumber, dilewati
            }

            $targetKpId = (int)$target['id'];
            $sourceKpId = (int)$sourceMatch['id'];
            $polarity   = $target['polarity'] ?? 'max';

            $targetTurunanList = $this->kpiPegawaiTurunanModel->getByKpiPegawai($targetKpId);
            $sourceTurunanList = $this->kpiPegawaiTurunanModel->getByKpiPegawai($sourceKpId);

            $adaPerubahanIndukIni = false;

            if (empty($targetTurunanList)) {
                // Bobot Induk diisi langsung (bukan auto-compute) hanya jika
                // Induk tujuan tidak punya Turunan.
                if ($this->bobotTahunanModel->getByRefTahun($targetKpId, $tahun) === null) {
                    $sourceBobot = $this->bobotTahunanModel->getByRefTahun($sourceKpId, $tahun);
                    if ($sourceBobot !== null && $sourceBobot['bobot'] !== null) {
                        $this->bobotTahunanModel->upsert($targetKpId, $tahun, (float)$sourceBobot['bobot']);
                        $adaPerubahanIndukIni = true;
                    }
                }
            }

            if ($polarity !== 'special') {
                $existingTarget = $this->targetBulananModel->getTahunPenuh($targetKpId, $tahun);
                $sourceTarget   = $this->targetBulananModel->getTahunPenuh($sourceKpId, $tahun);
                for ($b = 1; $b <= 12; $b++) {
                    if (array_key_exists($b, $existingTarget)) continue; // sudah ada, tidak ditimpa
                    if (!array_key_exists($b, $sourceTarget) || $sourceTarget[$b] === null) continue;
                    $this->targetBulananModel->upsert($targetKpId, $tahun, $b, (float)$sourceTarget[$b]);
                    $adaPerubahanIndukIni = true;
                }
            }

            if ($adaPerubahanIndukIni) {
                $indukTersalin++;
            }

            if (empty($targetTurunanList) || empty($sourceTurunanList)) {
                continue;
            }

            $sourceTurunanByNama = [];
            foreach ($sourceTurunanList as $st) {
                $sourceTurunanByNama[$st['nama_turunan']] = $st;
            }

            foreach ($targetTurunanList as $tt) {
                $stMatch = $sourceTurunanByNama[$tt['nama_turunan']] ?? null;
                if (!$stMatch) continue; // nama Turunan tidak cocok, dilewati

                $ttId      = (int)$tt['id'];
                $stId      = (int)$stMatch['id'];
                $polarityT = $tt['polarity'] ?? 'max';
                $adaPerubahanTurunanIni = false;

                if ($this->turunanBobotTahunanModel->getByRefTahun($ttId, $tahun) === null) {
                    $sourceBobotT = $this->turunanBobotTahunanModel->getByRefTahun($stId, $tahun);
                    if ($sourceBobotT !== null && $sourceBobotT['bobot'] !== null) {
                        $this->turunanBobotTahunanModel->upsert($ttId, $tahun, (float)$sourceBobotT['bobot']);
                        $adaPerubahanTurunanIni = true;
                        $indukPerluDihitungUlang[$targetKpId][$tahun] = true;
                    }
                }

                if ($polarityT !== 'special') {
                    $existingTargetT = $this->turunanTargetBulananModel->getTahunPenuh($ttId, $tahun);
                    $sourceTargetT   = $this->turunanTargetBulananModel->getTahunPenuh($stId, $tahun);
                    for ($b = 1; $b <= 12; $b++) {
                        if (array_key_exists($b, $existingTargetT)) continue;
                        if (!array_key_exists($b, $sourceTargetT) || $sourceTargetT[$b] === null) continue;
                        $this->turunanTargetBulananModel->upsert($ttId, $tahun, $b, (float)$sourceTargetT[$b]);
                        $adaPerubahanTurunanIni = true;
                    }
                }

                if ($adaPerubahanTurunanIni) $turunanTersalin++;
            }
        }

        // Bobot Induk yang Turunannya ikut disalin perlu dihitung ULANG
        // sebagai SUM seluruh Turunan saat ini (bukan cuma yang barusan
        // disalin) — sama seperti pola di processImportRows().
        foreach ($indukPerluDihitungUlang as $kpId => $tahunList) {
            foreach (array_keys($tahunList) as $th) {
                $turunanIds = array_column($this->kpiPegawaiTurunanModel->getByKpiPegawai($kpId), 'id');
                $sumBobot   = 0.0;
                if (!empty($turunanIds)) {
                    $indexed = $this->turunanBobotTahunanModel->getIndexedByRefAndTahun($turunanIds, $th);
                    foreach ($indexed as $b) $sumBobot += (float)($b ?? 0);
                }
                $this->bobotTahunanModel->upsert($kpId, $th, round($sumBobot, 4));
            }
        }

        return redirect()->to(base_url("master-target/edit/{$pegawaiId}?tahun={$tahun}"))
                         ->with('success', "Berhasil menyalin {$indukTersalin} KPI Induk dan {$turunanTersalin} Parameter Turunan. Data yang sudah terisi tidak ditimpa.");
    }

    // ══ IMPORT MASTER TARGET DARI EXCEL ══════════════════════════════

    public function importForm()
    {
        $check = $this->checkMenuAccess('master_target');
        if ($check !== true) return $check;

        $pegawaiId = (int)($this->request->getGet('pegawai_id') ?: 0);
        $tahun     = (int)($this->request->getGet('tahun') ?: date('Y'));
        $pegawai   = null;

        if ($pegawaiId) {
            if (!$this->canAccessPegawai($pegawaiId)) return $this->forbidden();
            $pegawai = $this->pegawaiModel->find($pegawaiId);
        }

        return view('layouts/main', [
            'title'   => 'Import Master Target dari Excel',
            'content' => view('master_target/_import', [
                'pegawai'   => $pegawai,
                'pegawaiId' => $pegawaiId,
                'tahun'     => $tahun,
            ]),
        ]);
    }

    public function importTemplate()
    {
        $check = $this->checkMenuAccess('master_target');
        if ($check !== true) return $check;

        $pegawaiId = (int)($this->request->getGet('pegawai_id') ?: 0);
        $tahun     = (int)($this->request->getGet('tahun') ?: date('Y'));

        $pegawai = null;
        if ($pegawaiId) {
            if (!$this->canAccessPegawai($pegawaiId)) return $this->forbidden();
            $pegawai = $this->pegawaiModel->find($pegawaiId);
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Import Master Target');

        $headers = [
            'A' => 'Tipe *', 'B' => 'NIP/Email Pegawai *', 'C' => 'Kode KPI *',
            'D' => 'Nama Parameter KPI (info, tidak wajib)',
            'E' => 'Nama Turunan', 'F' => 'Tahun *', 'G' => 'Bobot (desimal)',
        ];
        $col = 'H';
        foreach (self::NAMA_BULAN_KOLOM as $bulan => $nama) {
            $headers[$col] = "Target {$nama}";
            $col++;
        }
        foreach ($headers as $c => $h) {
            $sheet->setCellValue("{$c}1", $h);
            $sheet->getStyle("{$c}1")->getFont()->setBold(true);
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        // Jika dibuka dari konteks satu pegawai (tombol "Import Excel" di
        // layar Master Target per-pegawai), baris diisi otomatis sesuai
        // parameter KPI/Turunan yang SUDAH di-assign ke pegawai itu — Admin
        // tidak perlu lagi mengetik ulang Tipe/NIP/Kode KPI/Nama Turunan,
        // cukup meninjau/mengisi kolom Bobot & Target. Tanpa konteks
        // pegawai (akses generik), tetap tampilkan contoh seperti semula.
        $rows = $pegawai
            ? $this->buildTemplateRowsUntukPegawai($pegawaiId, $tahun)
            : $this->contohBarisTemplateUmum();

        foreach ($rows as $i => $row) {
            foreach ($row as $j => $val) {
                $c = chr(ord('A') + $j);
                $sheet->setCellValue("{$c}" . ($i + 2), $val);
            }
        }

        $noteCol = 'U';
        $notes = [
            "{$noteCol}1" => 'CATATAN:',
            "{$noteCol}2" => 'Tipe: INDUK atau TURUNAN',
            "{$noteCol}3" => 'NIP/Email & Kode KPI di baris TURUNAN boleh kosong (mengikuti INDUK di atasnya)',
            "{$noteCol}4" => 'Kode KPI harus sudah di-assign ke pegawai tsb di menu "KPI Per Pegawai"',
            "{$noteCol}5" => 'Nama Turunan harus sudah ada di menu "KPI Per Pegawai" (baris TURUNAN tidak membuat parameter baru)',
            "{$noteCol}6" => 'Bobot untuk KPI Induk yang SUDAH punya Turunan tidak perlu diisi — dihitung otomatis dari SUM Bobot Turunannya',
            "{$noteCol}7" => 'Kolom Target boleh dikosongkan sebagian (hanya bulan yang diisi yang akan diperbarui)',
            "{$noteCol}8" => 'Target diabaikan untuk KPI/Turunan berpolarity Special Scoring',
            "{$noteCol}9" => 'Kolom "Nama Parameter KPI" hanya informasi — tidak dibaca saat import, boleh diubah/dikosongkan bebas',
        ];
        if ($pegawai) {
            $notes["{$noteCol}10"] = 'Baris di bawah sudah otomatis diisi sesuai parameter KPI pegawai ' . $pegawai['nama'] . ' — tinggal lengkapi/sesuaikan kolom Bobot & Target.';
        }
        foreach ($notes as $cell => $val) $sheet->setCellValue($cell, $val);
        $sheet->getColumnDimension($noteCol)->setWidth(70);

        $filenamePart = $pegawai ? ('_' . preg_replace('/[^A-Za-z0-9]+/', '_', $pegawai['nama'])) : '';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="Template_Master_Target' . $filenamePart . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

    /**
     * Contoh baris template generik (tanpa konteks pegawai) — dipakai saat
     * layar Import diakses tanpa memilih pegawai tertentu, misalnya untuk
     * kebutuhan import banyak pegawai sekaligus dalam satu file.
     */
    private function contohBarisTemplateUmum(): array
    {
        return [
            ['INDUK',   '198501012020', 'MR-F1', 'Kepuasan Nasabah', '',                  '2026', '0.15',  100, 105, 110, 100, 100, 100, 100, 100, 100, 110, 110, 120],
            ['TURUNAN', '',             '',       'Kepuasan Nasabah', 'Kepuasan Cabang A', '2026', '0.075', 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80],
            ['TURUNAN', '',             '',       'Kepuasan Nasabah', 'Kepuasan Cabang B', '2026', '0.075', 70, 70, 70, 70, 70, 70, 70, 70, 70, 70, 70, 70],
            ['INDUK',   '198501012020', 'MR-C1', 'Nasabah Baru',     '',                  '2026', '0.10',  50, 50, 50, 50, 55, 55, 55, 55, 60, 60, 60, 60],
        ];
    }

    /**
     * Bangun baris template yang sudah diisi otomatis sesuai parameter KPI
     * Induk & Parameter Turunan yang SUDAH di-assign ke satu pegawai (di
     * menu KPI Per Pegawai) — termasuk Bobot/Target yang SUDAH tersimpan
     * untuk tahun ini (jika ada), supaya Admin tinggal meninjau/menyesuaikan
     * alih-alih mengetik ulang dari nol. Dipisah dari importTemplate() agar
     * bisa diuji langsung tanpa perlu menghasilkan file .xlsx sungguhan.
     *
     * @return list<list<int|float|string>>
     */
    protected function buildTemplateRowsUntukPegawai(int $pegawaiId, int $tahun): array
    {
        $pegawai = $this->pegawaiModel->find($pegawaiId);
        if (!$pegawai) {
            return [];
        }

        $nikEmail = trim((string)($pegawai['nip'] ?? ''));
        if ($nikEmail === '') {
            $user = $this->pegawaiModel->db->table('users')
                ->select('email')
                ->where('pegawai_id', $pegawaiId)
                ->get()->getRowArray();
            $nikEmail = $user['email'] ?? '';
        }

        $rows = [];

        foreach ($this->kpiPegawaiModel->getByPegawai($pegawaiId) as $kpi) {
            $kpId        = (int)$kpi['id'];
            $polarity    = $kpi['polarity'] ?? 'max';
            $listTurunan = $this->kpiPegawaiTurunanModel->getByKpiPegawai($kpId);
            $targetBulan = $this->targetBulananModel->getTahunPenuh($kpId, $tahun);
            $bobotRow    = $this->bobotTahunanModel->getByRefTahun($kpId, $tahun);

            $bobotIndukVal = '';
            if (empty($listTurunan) && $bobotRow !== null && $bobotRow['bobot'] !== null) {
                $bobotIndukVal = $this->formatAngkaTemplate($bobotRow['bobot']);
            }

            // Kolom "Nama Parameter KPI" murni informasi (tidak dibaca saat
            // import) — diisi di setiap baris, termasuk baris Turunannya,
            // supaya Admin langsung tahu KPI Induk mana yang sedang diisi
            // tanpa perlu menerka dari Kode KPI saja.
            $rowInduk = ['INDUK', $nikEmail, $kpi['kode'], $kpi['nama_kpi'], '', $tahun, $bobotIndukVal];
            for ($b = 1; $b <= 12; $b++) {
                $rowInduk[] = $polarity === 'special' ? '' : $this->formatAngkaTemplate($targetBulan[$b] ?? '');
            }
            $rows[] = $rowInduk;

            foreach ($listTurunan as $t) {
                $tId          = (int)$t['id'];
                $polarityT    = $t['polarity'] ?? 'max';
                $bobotT       = $this->turunanBobotTahunanModel->getByRefTahun($tId, $tahun);
                $targetBulanT = $this->turunanTargetBulananModel->getTahunPenuh($tId, $tahun);

                $bobotTVal = ($bobotT !== null && $bobotT['bobot'] !== null) ? $this->formatAngkaTemplate($bobotT['bobot']) : '';

                $rowT = ['TURUNAN', '', '', $kpi['nama_kpi'], $t['nama_turunan'], $tahun, $bobotTVal];
                for ($b = 1; $b <= 12; $b++) {
                    $rowT[] = $polarityT === 'special' ? '' : $this->formatAngkaTemplate($targetBulanT[$b] ?? '');
                }
                $rows[] = $rowT;
            }
        }

        return $rows;
    }

    /** Buang trailing zero dari nilai desimal (kolom DB) agar rapi di Excel. */
    private function formatAngkaTemplate($v)
    {
        if ($v === null || $v === '') {
            return '';
        }
        return rtrim(rtrim(sprintf('%.4f', (float)$v), '0'), '.');
    }

    public function importProcess()
    {
        $check = $this->checkMenuEdit('master_target');
        if ($check !== true) return $check;

        $file = $this->request->getFile('file_excel');
        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return redirect()->back()->with('error', 'File tidak valid. Harap unggah file Excel (.xlsx).');
        }

        $ext = strtolower($file->getClientExtension());
        if (!in_array($ext, ['xlsx', 'xls'])) {
            return redirect()->back()->with('error', 'Format file harus .xlsx atau .xls.');
        }

        try {
            $reader      = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file->getTempName());
            $spreadsheet = $reader->load($file->getTempName());
            $rows        = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membaca file: ' . esc($e->getMessage()));
        }

        ['berhasil' => $berhasil, 'dilewati' => $dilewati, 'errors' => $errors] = $this->processImportRows($rows);

        $pesan = "$berhasil baris berhasil diimport.";
        if ($dilewati > 0) $pesan .= " $dilewati baris dilewati (tidak ada nilai untuk disimpan).";
        if (!empty($errors)) {
            $pesan .= ' ' . count($errors) . ' baris bermasalah: '
                    . implode('; ', array_slice($errors, 0, 5));
            if (count($errors) > 5) $pesan .= '... (dan ' . (count($errors) - 5) . ' lainnya)';
        }

        return redirect()->to(base_url('master-target'))
                         ->with($berhasil > 0 ? 'success' : 'warning', $pesan);
    }

    /**
     * Proses baris-baris hasil parse Excel (format `toArray(null, true, true,
     * true)` dari PhpSpreadsheet — key kolom huruf A, B, C, ... dan baris 1
     * adalah header). Dipisah dari importProcess() supaya bisa diuji
     * langsung dengan array baris buatan tanpa perlu upload file sungguhan.
     *
     * @return array{berhasil:int,dilewati:int,errors:list<string>}
     */
    protected function processImportRows(array $rows): array
    {
        $berhasil = 0;
        $dilewati = 0;
        $errors   = [];

        // State parser: melacak konteks INDUK terakhir, sama seperti pola
        // import KPI Per Pegawai (KpiPegawaiController::importProcess()).
        $currentKpId      = null;
        $currentSkipInduk = false;

        // Kumpulan (kpId => [tahun,...]) yang Turunan-nya ikut disentuh di
        // import ini — Bobot Induk-nya perlu dihitung ULANG di akhir sebagai
        // SUM seluruh Turunan (termasuk Turunan lama yang tidak ikut
        // disentuh baris ini), bukan cuma dari baris yang diproses barusan.
        $indukPerluDihitungUlang = [];

        foreach ($rows as $i => $row) {
            if ($i === 1) continue; // Skip header

            $tipe     = strtoupper(trim($row['A'] ?? ''));
            $nikEmail = trim($row['B'] ?? '');
            $kodeKpi  = strtoupper(trim($row['C'] ?? ''));
            // $row['D'] = Nama Parameter KPI — murni informasi, sengaja
            // tidak dibaca/divalidasi di sini.
            $namaTurunan = trim($row['E'] ?? '');
            $tahun    = trim($row['F'] ?? '');
            $bobot    = trim($row['G'] ?? '');

            $bulanKolom = ['H','I','J','K','L','M','N','O','P','Q','R','S'];
            $targetBulan = [];
            foreach ($bulanKolom as $idx => $col) {
                $bulanKe = $idx + 1;
                $v = trim($row[$col] ?? '');
                if ($v !== '') $targetBulan[$bulanKe] = $v;
            }

            if ($tipe === '' && $nikEmail === '' && $kodeKpi === '' && $namaTurunan === '') continue;

            if ($tahun === '' || !is_numeric($tahun)) {
                $errors[] = "Baris $i: Tahun wajib diisi dan berupa angka.";
                if ($tipe === 'INDUK') $currentSkipInduk = true;
                continue;
            }
            $tahun = (int)$tahun;

            if ($tipe === 'INDUK') {
                $currentSkipInduk = false;
                $currentKpId      = null;

                if ($nikEmail === '') {
                    $errors[] = "Baris $i: NIP/Email wajib diisi untuk baris INDUK.";
                    $currentSkipInduk = true; continue;
                }

                $pegawai = $this->pegawaiModel->db->table('pegawai p')
                    ->select('p.*')
                    ->join('users u', 'u.pegawai_id = p.id', 'left')
                    ->groupStart()
                        ->where('p.nip', $nikEmail)
                        ->orWhere('u.email', $nikEmail)
                    ->groupEnd()
                    ->where('p.is_active', 1)
                    ->get()->getRowArray();

                if (!$pegawai) {
                    $errors[] = "Baris $i: Pegawai '$nikEmail' tidak ditemukan.";
                    $currentSkipInduk = true; continue;
                }

                if (!$this->canAccessPegawai((int)$pegawai['id'])) {
                    $errors[] = "Baris $i: Tidak memiliki akses ke pegawai '$nikEmail'.";
                    $currentSkipInduk = true; continue;
                }

                if ($kodeKpi === '') {
                    $errors[] = "Baris $i: Kode KPI wajib diisi untuk baris INDUK.";
                    $currentSkipInduk = true; continue;
                }

                $kpiRow = $this->kpiPegawaiModel->db->table('kpi_pegawai kp')
                    ->select('kp.id, k.polarity, k.nama_kpi')
                    ->join('kpi_unit k', 'k.id = kp.kpi_id')
                    ->where('kp.pegawai_id', (int)$pegawai['id'])
                    ->where('k.kode', $kodeKpi)
                    ->where('kp.is_active', 1)
                    ->get()->getRowArray();

                if (!$kpiRow) {
                    $errors[] = "Baris $i: KPI '$kodeKpi' belum di-assign ke pegawai '$nikEmail'. Assign dulu di menu KPI Per Pegawai.";
                    $currentSkipInduk = true; continue;
                }

                $currentKpId  = (int)$kpiRow['id'];
                $punyaTurunan = !empty($this->kpiPegawaiTurunanModel->getByKpiPegawai($currentKpId));

                $adaPerubahan = false;

                if (!$punyaTurunan) {
                    if ($bobot !== '') {
                        if (!is_numeric($bobot) || (float)$bobot <= 0 || (float)$bobot > 1) {
                            $errors[] = "Baris $i: Bobot untuk KPI '$kodeKpi' harus angka desimal antara 0 (tidak termasuk) dan 1.";
                            $currentSkipInduk = true; continue;
                        }
                        $this->bobotTahunanModel->upsert($currentKpId, $tahun, (float)$bobot);
                        $adaPerubahan = true;
                    }
                } else {
                    // Bobot Induk yang sudah punya Turunan diabaikan jika
                    // terisi di baris ini — akan dihitung otomatis di akhir.
                    $indukPerluDihitungUlang[$currentKpId][$tahun] = true;
                }

                $errTarget = $this->importValidasiSimpanBulanan(
                    $targetBulan, $kpiRow['polarity'] ?? 'max', $this->targetBulananModel, $currentKpId, $tahun, "Baris $i: Target KPI '$kodeKpi'"
                );
                if ($errTarget !== null) {
                    $errors[] = $errTarget;
                    $currentSkipInduk = true; continue;
                }
                if (!empty($targetBulan)) $adaPerubahan = true;

                if ($adaPerubahan) $berhasil++; else $dilewati++;

            } elseif ($tipe === 'TURUNAN') {
                if ($currentSkipInduk || !$currentKpId) continue;

                if ($namaTurunan === '') {
                    $errors[] = "Baris $i: Nama Turunan wajib diisi."; continue;
                }

                $turunanRow = $this->kpiPegawaiTurunanModel
                    ->where('kpi_pegawai_id', $currentKpId)
                    ->where('nama_turunan', $namaTurunan)
                    ->where('is_active', 1)
                    ->first();

                if (!$turunanRow) {
                    $errors[] = "Baris $i: Parameter Turunan '$namaTurunan' tidak ditemukan untuk KPI ini. Tambahkan dulu di menu KPI Per Pegawai.";
                    continue;
                }

                $tId = (int)$turunanRow['id'];
                $adaPerubahan = false;

                if ($bobot !== '') {
                    if (!is_numeric($bobot) || (float)$bobot <= 0 || (float)$bobot > 1) {
                        $errors[] = "Baris $i: Bobot untuk Turunan '$namaTurunan' harus angka desimal antara 0 (tidak termasuk) dan 1.";
                        continue;
                    }
                    $this->turunanBobotTahunanModel->upsert($tId, $tahun, (float)$bobot);
                    $adaPerubahan = true;
                    $indukPerluDihitungUlang[$currentKpId][$tahun] = true;
                }

                $errTarget = $this->importValidasiSimpanBulanan(
                    $targetBulan, $turunanRow['polarity'] ?? 'max', $this->turunanTargetBulananModel, $tId, $tahun, "Baris $i: Target Turunan '$namaTurunan'"
                );
                if ($errTarget !== null) {
                    $errors[] = $errTarget;
                    continue;
                }
                if (!empty($targetBulan)) $adaPerubahan = true;

                if ($adaPerubahan) $berhasil++; else $dilewati++;

            } elseif ($tipe !== '') {
                $errors[] = "Baris $i: Tipe '$tipe' tidak dikenal. Gunakan INDUK atau TURUNAN.";
            }
        }

        // Hitung ulang Bobot Induk = SUM seluruh Bobot Turunannya (bukan
        // hanya yang tersentuh baris import ini), untuk setiap (KPI, Tahun)
        // yang Turunannya ikut disentuh.
        foreach ($indukPerluDihitungUlang as $kpId => $tahunList) {
            foreach (array_keys($tahunList) as $tahun) {
                $turunanIds = array_column($this->kpiPegawaiTurunanModel->getByKpiPegawai($kpId), 'id');
                $sumBobot   = 0.0;
                if (!empty($turunanIds)) {
                    $indexed = $this->turunanBobotTahunanModel->getIndexedByRefAndTahun($turunanIds, $tahun);
                    foreach ($indexed as $b) $sumBobot += (float)($b ?? 0);
                }
                $this->bobotTahunanModel->upsert($kpId, $tahun, round($sumBobot, 4));
            }
        }

        return ['berhasil' => $berhasil, 'dilewati' => $dilewati, 'errors' => $errors];
    }

    /**
     * Validasi & simpan Target Bulanan (parsial — hanya bulan yang benar-benar
     * diisi di file Excel) untuk satu baris import (Induk atau Turunan).
     * Polarity 'special' selalu dilewati tanpa validasi/penyimpanan Target
     * sama sekali. Mengembalikan pesan error (string) jika ada nilai bulan
     * yang tidak valid (bukan angka atau ≤0), atau null jika semua valid
     * (termasuk kalau tidak ada satu bulan pun yang diisi).
     *
     * @param KpiPegawaiTargetBulananModel|KpiPegawaiTurunanTargetBulananModel $model
     */
    private function importValidasiSimpanBulanan(array $targetBulan, string $polarity, $model, int $refId, int $tahun, string $label): ?string
    {
        if ($polarity === 'special' || empty($targetBulan)) {
            return null;
        }

        foreach ($targetBulan as $bulan => $v) {
            if (!is_numeric($v) || (float)$v <= 0) {
                $namaBulan = $this->namaBulan($bulan);
                return "{$label} bulan {$namaBulan} harus angka lebih besar dari 0.";
            }
        }

        foreach ($targetBulan as $bulan => $v) {
            $model->upsert($refId, $tahun, $bulan, (float)$v);
        }

        return null;
    }
}
