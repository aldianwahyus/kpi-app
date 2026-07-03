<?php

namespace App\Controllers;

use App\Models\PenilaianModel;
use App\Models\PegawaiModel;
use App\Models\KpiPegawaiModel;
use App\Models\KpiPegawaiTurunanModel;
use App\Models\PenilaianTurunanModel;
use App\Models\PeriodeModel;
use App\Models\AuditLogModel;
use App\Services\KpiCalculationService;
use App\Services\AuditService;

class PenilaianController extends BaseController
{
    protected PenilaianModel         $penilaianModel;
    protected PegawaiModel           $pegawaiModel;
    protected KpiPegawaiModel        $kpiPegawaiModel;
    protected KpiPegawaiTurunanModel $kpiPegawaiTurunanModel;
    protected PenilaianTurunanModel  $penilaianTurunanModel;
    protected PeriodeModel           $periodeModel;
    protected KpiCalculationService  $calculator;
    protected AuditService           $auditService;
    protected AuditLogModel          $auditLogModel;

    public function __construct()
    {
        $this->penilaianModel         = new PenilaianModel();
        $this->pegawaiModel           = new PegawaiModel();
        $this->kpiPegawaiModel        = new KpiPegawaiModel();
        $this->kpiPegawaiTurunanModel = new KpiPegawaiTurunanModel();
        $this->penilaianTurunanModel  = new PenilaianTurunanModel();
        $this->periodeModel           = new PeriodeModel();
        $this->calculator             = new KpiCalculationService();
        $this->auditService           = new AuditService();
        $this->auditLogModel   = new AuditLogModel();
    }

    public function index(): string
    {   
        $periodeAktif = $this->periodeModel->getAktif();
        $role         = session()->get('role');
        $userPegawaiId= session()->get('pegawai_id');

        // Drafter & Approver HANYA boleh melihat pegawai di divisinya
        // sendiri — tidak terkecuali. Sebelumnya hanya role 'approver'
        // yang difilter, sehingga Drafter melihat seluruh pegawai
        // perusahaan. Filter ini sekarang berlaku konsisten untuk
        // kedua role, sesuai dengan canAccessPegawai().
        $divisiScope = null;
        $pegawai = [];
        if (in_array($role, ['drafter', 'approver']) && $userPegawaiId) {
            $userPegawai = $this->pegawaiModel->find($userPegawaiId);
            $divisiScope = $userPegawai['divisi_id'] ?? null;
            if ($divisiScope) {
                $all = $this->pegawaiModel->getAllWithDivisi();
                $pegawai = array_values(array_filter($all, fn($p) => $p['divisi_id'] == $divisiScope && $p['id'] != $userPegawaiId));
            }
        } else {
            $pegawai = $this->pegawaiModel->getAllWithDivisi();
        }

        $rekap = [];
        if ($periodeAktif) {
            // Filter divisi diterapkan langsung di level SQL — baris data
            // dari divisi lain tidak pernah dimuat ke memori sama sekali
            // untuk role Drafter/Approver, bukan hanya disembunyikan di
            // tampilan setelah seluruh data perusahaan diambil.
            foreach ($this->penilaianModel->getRekapKombinasi($periodeAktif['id'], $divisiScope) as $row) {
                $rekap[$row['pegawai_id']] = $row;
            }
        }

        // Hitung jumlah KPI yang sudah di-setup per pegawai (dari tabel
        // kpi_pegawai) — dipakai di view untuk menampilkan indikator
        // apakah KPI pegawai sudah siap (ada setup) atau belum sama sekali.
        // Difilter dengan divisiScope yang sama agar konsisten dengan
        // prinsip filter di level query untuk Drafter/Approver.
        $kpiSetupCount = [];
        $countBuilder = $this->kpiPegawaiModel->db->table('kpi_pegawai kp')
            ->select('kp.pegawai_id, COUNT(*) as jumlah')
            ->where('kp.is_active', 1);
        if ($divisiScope) {
            $countBuilder->join('pegawai pg', 'pg.id = kp.pegawai_id')
                         ->where('pg.divisi_id', $divisiScope);
        }
        $rawCounts = $countBuilder->groupBy('kp.pegawai_id')->get()->getResultArray();
        foreach ($rawCounts as $r) {
            $kpiSetupCount[(int)$r['pegawai_id']] = (int)$r['jumlah'];
        }

        return view('layouts/main', [
            'title'   => 'Input Penilaian KPI',
            'content' => view('penilaian/_content', [
                'pegawai'       => $pegawai,
                'rekap'         => $rekap,
                'periodeAktif'  => $periodeAktif,
                'role'          => $role,
                'kpiSetupCount' => $kpiSetupCount,
            ]),
        ]);
    }

    public function form(int $pegawaiId)
    {
        if (!$this->canAccessPegawai($pegawaiId)) return $this->forbidden();

        $periodeAktif = $this->periodeModel->getAktif();
        if (!$periodeAktif) return redirect()->to(base_url('penilaian'))->with('error', 'Tidak ada periode aktif.');

        $pegawai = $this->pegawaiModel->find($pegawaiId);
        if (!$pegawai) {
            return redirect()->to(base_url('penilaian'))->with('error', 'Pegawai tidak ditemukan.');
        }

        $kpiList = $this->kpiPegawaiModel->getByPegawai($pegawaiId);
        
        if (empty($kpiList)) return redirect()->to(base_url('penilaian'))->with('error', "KPI untuk {$pegawai['nama']} belum di-setup.");

        $existing   = $this->penilaianModel->getIndexedByKpi($pegawaiId, $periodeAktif['id']);
        $nilaiAkhir = $this->penilaianModel->getNilaiAkhir($pegawaiId, $periodeAktif['id']);
        $approval   = $this->getStatusApproval($pegawaiId, $periodeAktif['id']);

        // Ambil Parameter Turunan untuk setiap KPI Induk, dikelompokkan
        // berdasarkan kp.id (id baris kpi_pegawai), serta realisasi
        // Turunan yang sudah pernah diisi (jika baris penilaian Induk
        // untuk periode ini sudah ada).
        $turunanByInduk    = [];
        $realisasiTurunan  = [];
        foreach ($kpiList as $kpi) {
            $listTurunan = $this->kpiPegawaiTurunanModel->getByKpiPegawai($kpi['id']);
            $turunanByInduk[$kpi['id']] = $listTurunan;

            if (!empty($listTurunan)) {
                $existingInduk = $existing[$kpi['kpi_id']] ?? null;
                if ($existingInduk) {
                    $realisasiTurunan[$kpi['id']] = $this->penilaianTurunanModel
                        ->getIndexedByTurunan($existingInduk['id']);
                } else {
                    $realisasiTurunan[$kpi['id']] = [];
                }
            }
        }

        $kpiGrouped = [];
        foreach ($kpiList as $kpi) { $kpiGrouped[$kpi['perspektif']][] = $kpi; }

        return view('layouts/main', [
            'title'   => 'Input Penilaian — ' . $pegawai['nama'],
            'content' => view('penilaian/_form', [
                'pegawai' => $pegawai, 'kpiList' => $kpiList, 'kpiGrouped' => $kpiGrouped,
                'existing' => $existing, 'periodeAktif' => $periodeAktif, 'nilaiAkhir' => $nilaiAkhir,
                'statusApproval' => $approval, 'rejectNote' => $approval['reject_note'],
                'histori' => $this->auditLogModel->getPenilaianHistory($pegawaiId, $periodeAktif['id']),
                'role' => session()->get('role'),
                'turunanByInduk'   => $turunanByInduk,
                'realisasiTurunan' => $realisasiTurunan,
            ]),
        ]);
    }

    public function store(int $pegawaiId)
    {
        if (!$this->canAccessPegawai($pegawaiId)) return $this->forbidden();

        $periodeAktif = $this->periodeModel->getAktif();
        if (!$periodeAktif) {
            return redirect()->to(base_url('penilaian'))->with('error', 'Tidak ada periode aktif.');
        }

        $role = session()->get('role');

        // Cek status saat ini
        $statusCheck = $this->getStatusApproval($pegawaiId, $periodeAktif['id']);
        $currentStatus = $statusCheck['current'];

        // Matriks akses Realisasi berdasarkan Role & Status — harus konsisten
        // dengan logika readonly pada tampilan form (_form.php):
        // - Admin    : selalu dapat mengedit.
        // - Approver : dapat mengedit HANYA saat status submitted (Review).
        // - Drafter  : dapat mengedit saat status draft atau rejected.
        if ($role === 'admin') {
            $isEditAllowed = true;
        } elseif ($role === 'approver') {
            $isEditAllowed = ($currentStatus === 'submitted');
        } else {
            $isEditAllowed = in_array($currentStatus, ['draft', 'rejected']);
        }

        if (!$isEditAllowed) {
            return redirect()->to(base_url("penilaian/form/$pegawaiId"))
                            ->with('error', 'Penilaian tidak dapat diedit pada status saat ini.');
        }

        $kpiList          = $this->kpiPegawaiModel->getByPegawai($pegawaiId);
        $realisasi        = $this->request->getPost('realisasi')         ?? [];
        $catatan          = $this->request->getPost('catatan')           ?? [];
        $realisasiTurunan = $this->request->getPost('realisasi_turunan') ?? [];
        $catatanTurunan   = $this->request->getPost('catatan_turunan')   ?? [];

        $saved = 0;
        foreach ($kpiList as $kpi) {
            $kpiId       = (int)$kpi['kpi_id'];
            $kpiPegawaiId = (int)$kpi['id'];

            $listTurunan = $this->kpiPegawaiTurunanModel->getByKpiPegawai($kpiPegawaiId);
            $punyaTurunan = !empty($listTurunan);

            if ($punyaTurunan) {
                // ── KPI dengan Parameter Turunan: Alternatif 1 ──────────
                // Skor dihitung per Turunan berdasarkan Target & Polarity
                // masing-masing (yang bisa berbeda antar Turunan), lalu
                // Skor Induk = rata-rata tertimbang menggunakan Cara B:
                //   Skor_Induk = Σ(Skor_T × Bobot_T) / Bobot_Induk
                $turunanInput   = $realisasiTurunan[$kpiPegawaiId] ?? [];
                $sumKontribusiT = 0.0;
                $skorPerTurunan = [];

                // Validasi ALL-OR-NOTHING: semua Turunan harus diisi.
                // Jika ada yang benar-benar belum diisi (kosong), KPI Induk ini
                // dilewati seluruhnya — skor parsial tidak boleh disimpan karena
                // akan menghasilkan nilai yang menyesatkan. Realisasi = 0 yang
                // SENGAJA diisi (bukan dikosongkan) dianggap terisi — untuk KPI
                // ber-polaritas 'min', 0 adalah capaian valid (bahkan terbaik).
                $turunanTidakLengkap = false;
                foreach ($listTurunan as $t) {
                    $rt = $turunanInput[$t['id']] ?? null;
                    if ($rt === null || $rt === '') {
                        $turunanTidakLengkap = true;
                        break;
                    }
                }

                if ($turunanTidakLengkap) continue;

                foreach ($listTurunan as $t) {
                    $rtFloat      = (float)($turunanInput[$t['id']]);
                    $targetT      = (float)($t['target'] ?? 100);
                    $polarityT    = $t['polarity'] ?? 'max';
                    $bobotT       = (float)($t['bobot'] ?? 0);

                    // is_capped selalu true sesuai keputusan desain
                    $skorT        = $this->calculator->hitungSkorCapaian($rtFloat, $targetT, $polarityT, true);
                    $kontribusiT  = $bobotT > 0 ? $skorT * $bobotT : 0;

                    $sumKontribusiT += $kontribusiT;
                    $skorPerTurunan[$t['id']] = [
                        'realisasi'        => $rtFloat,
                        'skor'             => round($skorT, 2),
                        'nilai_kontribusi' => round($kontribusiT, 4),
                    ];
                }

                // Skor Induk = Σ(Skor_T × Bobot_T) / Bobot_Induk (Cara B)
                $bobotInduk = (float)$kpi['bobot'];
                $skorInduk  = $bobotInduk > 0 ? $sumKontribusiT / $bobotInduk : 0;
                $skorInduk  = max(10, min(100, $skorInduk));

                $real       = null; // tidak relevan untuk kasus Turunan
                $skor       = $skorInduk;
                $kontribusi = $this->calculator->hitungKontribusi($skor, $bobotInduk);

            } else {
                // ── KPI tanpa Parameter Turunan: alur asli, tidak diubah ──
                // Hanya lewati KPI yang benar-benar belum diisi (kosong).
                // Realisasi = 0 yang sengaja diisi tetap disimpan & dihitung —
                // untuk KPI ber-polaritas 'min', 0 adalah capaian valid.
                $real = $realisasi[$kpiId] ?? null;
                if ($real === null || $real === '') continue;
                $real       = (float)$real;
                $target     = (float)($kpi['target'] ?? 100);
                $polarity   = $kpi['polarity'] ?? 'max';
                $isCapped   = (bool)($kpi['is_capped'] ?? true);
                $skor       = $this->calculator->hitungSkorCapaian($real, $target, $polarity, $isCapped);
                $kontribusi = $this->calculator->hitungKontribusi($skor, (float)$kpi['bobot']);
            }

            // 1. Ambil data lama untuk pembanding di Histori Log
            $oldData = $this->penilaianModel
                ->where('pegawai_id', $pegawaiId)
                ->where('kpi_id', $kpiId)
                ->where('periode_id', $periodeAktif['id'])
                ->first();

            $newData = [
                'realisasi'        => $punyaTurunan ? null : $real,
                'skor'             => round($skor, 2),
                'nilai_kontribusi' => round($kontribusi, 2),
                'catatan'          => $punyaTurunan ? null : ($catatan[$kpiId] ?? null),
                'input_by'         => session()->get('user_id'),
                'status'           => 'draft',
            ];

            // 2. Simpan Data ke Database
            $this->penilaianModel->upsert($pegawaiId, $kpiId, $periodeAktif['id'], $newData);

            // 3. Dapatkan ID record
            $action   = $oldData ? 'update' : 'create';
            $recordId = $oldData['id'] ?? null;
            if (!$recordId) {
                $savedRecord = $this->penilaianModel
                    ->where('pegawai_id', $pegawaiId)->where('kpi_id', $kpiId)->where('periode_id', $periodeAktif['id'])->first();
                $recordId = $savedRecord['id'] ?? null;
            }

            // 4. Simpan skor & kontribusi per Turunan
            if ($punyaTurunan && $recordId) {
                foreach ($listTurunan as $t) {
                    $rt = ($realisasiTurunan[$kpiPegawaiId] ?? [])[$t['id']] ?? null;
                    if ($rt === null || $rt === '' || (float)$rt == 0.0) continue;

                    $skorData = $skorPerTurunan[$t['id']] ?? null;
                    if (!$skorData) continue;

                    $this->penilaianTurunanModel->upsert($recordId, (int)$t['id'], [
                        'realisasi'        => $skorData['realisasi'],
                        'skor'             => $skorData['skor'],
                        'nilai_kontribusi' => $skorData['nilai_kontribusi'],
                        'catatan'          => ($catatanTurunan[$kpiPegawaiId] ?? [])[$t['id']] ?? null,
                    ]);
                }
            }

            if ($recordId) {
                $keterangan = $punyaTurunan
                    ? 'Input Realisasi per Turunan, Skor Induk = rata-rata tertimbang (' . round($skor, 2) . ')'
                    : ($oldData ? "Update Realisasi menjadi $real" : "Input Realisasi awal $real");

                $this->auditService->log(
                    'penilaian', $recordId, $action,
                    $oldData ? ['realisasi' => $oldData['realisasi'], 'skor' => $oldData['skor']] : null,
                    ['skor' => $newData['skor'], 'nilai_kontribusi' => $newData['nilai_kontribusi']],
                    $keterangan
                );
            }

            $saved++;
        }

        return redirect()->to(base_url("penilaian/form/$pegawaiId"))
                        ->with('success', "Penilaian berhasil disimpan! ($saved KPI)");
    }

    public function submit(int $pegawaiId)
    {
        if (!$this->canAccessPegawai($pegawaiId)) return $this->forbidden();

        $periode = $this->periodeModel->getAktif();
        if (!$periode) {
            return redirect()->to(base_url("penilaian/form/$pegawaiId"))
                            ->with('error', 'Tidak ada periode aktif.');
        }

        $record = $this->penilaianModel->where(['pegawai_id' => $pegawaiId, 'periode_id' => $periode['id']])->first();

        if (!$record) {
            return redirect()->to(base_url("penilaian/form/$pegawaiId"))
                            ->with('error', 'Belum ada penilaian yang diisi untuk pegawai ini. Isi realisasi terlebih dahulu sebelum submit.');
        }

        $this->penilaianModel->db->table('penilaian')
            ->where(['pegawai_id' => $pegawaiId, 'periode_id' => $periode['id']])
            ->update(['status' => 'submitted', 'submitted_at' => date('Y-m-d H:i:s')]);

        // Catat log
        $this->auditService->log('penilaian', $record['id'], 'submit', ['status' => $record['status']], ['status' => 'submitted'], 'Penilaian disubmit untuk approval');

        return redirect()->to(base_url("penilaian/form/$pegawaiId"))->with('success', 'Berhasil disubmit.');
    }

    public function approve(int $pegawaiId)
    {
        $role = session()->get('role');
        if (!in_array($role, ['admin', 'hr', 'approver'])) {
            return $this->forbidden('Anda tidak memiliki kewenangan untuk meng-approve penilaian.');
        }

        $periode = $this->periodeModel->getAktif();
        if (!$periode) {
            return redirect()->to(base_url("penilaian/form/$pegawaiId"))
                            ->with('error', 'Tidak ada periode aktif.');
        }

        $record = $this->penilaianModel->where(['pegawai_id' => $pegawaiId, 'periode_id' => $periode['id']])->first();

        if (!$record) {
            return redirect()->to(base_url("penilaian/form/$pegawaiId"))
                            ->with('error', 'Belum ada penilaian yang dapat diapprove untuk pegawai ini.');
        }

        $this->penilaianModel->db->table('penilaian')
            ->where(['pegawai_id' => $pegawaiId, 'periode_id' => $periode['id']])
            ->update(['status' => 'approved', 'approved_by' => session()->get('user_id'), 'approved_at' => date('Y-m-d H:i:s'), 'reject_note' => null]);

        // Catat log
        $this->auditService->log('penilaian', $record['id'], 'approve', ['status' => $record['status']], ['status' => 'approved'], 'Penilaian disetujui');

        return redirect()->to(base_url("penilaian/form/$pegawaiId"))->with('success', 'Berhasil diapprove.');
    }

    public function reject(int $pegawaiId)
    {
        $role = session()->get('role');
        if (!in_array($role, ['admin', 'hr', 'approver'])) {
            return $this->forbidden('Anda tidak memiliki kewenangan untuk menolak penilaian.');
        }

        $note = trim($this->request->getPost('reject_note') ?? '');
        if (empty($note)) return redirect()->back()->with('error', 'Catatan wajib diisi.');

        $periode = $this->periodeModel->getAktif();
        if (!$periode) {
            return redirect()->to(base_url("penilaian/form/$pegawaiId"))
                            ->with('error', 'Tidak ada periode aktif.');
        }

        $record = $this->penilaianModel->where(['pegawai_id' => $pegawaiId, 'periode_id' => $periode['id']])->first();

        if (!$record) {
            return redirect()->to(base_url("penilaian/form/$pegawaiId"))
                            ->with('error', 'Belum ada penilaian yang dapat ditolak untuk pegawai ini.');
        }

        $this->penilaianModel->db->table('penilaian')
            ->where(['pegawai_id' => $pegawaiId, 'periode_id' => $periode['id']])
            ->update(['status' => 'rejected', 'reject_note' => $note]);

        // Catat log
        $this->auditService->log('penilaian', $record['id'], 'reject', ['status' => $record['status']], ['status' => 'rejected', 'reject_note' => $note], "Penilaian ditolak: $note");

        return redirect()->to(base_url("penilaian/form/$pegawaiId"))->with('error', "Ditolak: $note");
    }

    public function ajaxHitung()
    {
        $pegawaiId = (int)$this->request->getPost('pegawai_id');
        $kpiId     = (int)$this->request->getPost('kpi_id');
        $realisasi = $this->request->getPost('realisasi');

        if (!$this->canAccessPegawai($pegawaiId)) {
            return $this->response->setJSON(['valid' => false, 'message' => 'Tidak memiliki akses.']);
        }

        // 1. Ambil list KPI pegawai
        $kpiList = $this->kpiPegawaiModel->getByPegawai($pegawaiId);

        // 2. Cari KPI yang sedang diketik (menggunakan foreach standar CI4)
        $currentKpi = null;
        foreach ($kpiList as $item) {
            if ($item['kpi_id'] == $kpiId) {
                $currentKpi = $item;
                break;
            }
        }

        // Jika tidak ketemu, hentikan
        if (!$currentKpi) {
            return $this->response->setJSON(['valid' => false, 'message' => 'KPI tidak ditemukan']);
        }

        // Field belum diisi sama sekali -> jangan tampilkan preview skor
        // (berbeda dari realisasi = 0 yang sengaja diisi, yang tetap valid
        // untuk KPI ber-polaritas 'min').
        if ($realisasi === null || $realisasi === '') {
            return $this->response->setJSON(['valid' => false, 'message' => 'Realisasi belum diisi.']);
        }

        // 3. Konversi tipe data untuk dikalkulasi
        $realisasi = (float)$realisasi;
        $target    = (float)($currentKpi['target'] ?? 100);
        $bobot     = (float)($currentKpi['bobot'] ?? 0);
        $polarity  = $currentKpi['polarity'] ?? 'max';
        $isCapped  = isset($currentKpi['is_capped']) ? (bool)$currentKpi['is_capped'] : true;

        // 4. Lakukan perhitungan menggunakan Service
        $skor       = $this->calculator->hitungSkorCapaian($realisasi, $target, $polarity, $isCapped);
        $kontribusi = $this->calculator->hitungKontribusi($skor, $bobot);

        // 5. Tentukan warna badge (memakai service yang sama dengan tampilan lain,
        //    agar tidak ada duplikasi logika ambang batas warna)
        $bootstrapColor = $this->calculator->getColorBySkor($skor);

        // 6. Kembalikan response JSON beserta token CSRF baru
        return $this->response->setJSON([
            'valid'      => true,
            'skor'       => round($skor, 2),
            'kontribusi' => round($kontribusi, 2),
            'color'      => $bootstrapColor,
            'csrf_hash'  => csrf_hash()
        ]);
    }

    protected function getStatusApproval(int $pegawaiId, int $periodeId): array
    {
        $rows = $this->penilaianModel->db->table('penilaian')
            ->select('status, COUNT(*) as jumlah')
            ->where('pegawai_id', $pegawaiId)
            ->where('periode_id', $periodeId)
            ->groupBy('status')
            ->get()->getResultArray();

        $result = ['draft'=>0,'submitted'=>0,'approved'=>0,'rejected'=>0];
        foreach ($rows as $row) {
            $result[$row['status']] = (int)$row['jumlah'];
        }

        if ($result['approved'] > 0 && $result['draft']==0 && $result['submitted']==0) {
            $result['current'] = 'approved';
        } elseif ($result['rejected'] > 0) {
            $result['current'] = 'rejected';
        } elseif ($result['submitted'] > 0) {
            $result['current'] = 'submitted';
        } else {
            $result['current'] = 'draft';
        }

        $rejectRecord = $this->penilaianModel
            ->where('pegawai_id', $pegawaiId)
            ->where('periode_id', $periodeId)
            ->where('status', 'rejected')
            ->first();
        $result['reject_note'] = $rejectRecord['reject_note'] ?? null;

        // Cek apakah ada permintaan draft ulang pending
        $draftUlangModel = new \App\Models\DraftUlangRequestModel();
        $result['has_pending_draft_request'] = $draftUlangModel->hasPendingRequest(
            $pegawaiId, $periodeId, 'pegawai'
        );

        // Permintaan draft ulang yang ditolak Administrator (untuk notifikasi ke Approver)
        $result['latest_declined_draft_request'] = $draftUlangModel->getLatestDeclined(
            $pegawaiId, $periodeId
        );

        return $result;
    }

    // ── AJAX: Hitung skor satu Parameter Turunan secara real-time ──
    // Dipanggil JS saat Drafter mengetik Realisasi di baris Turunan.
    // Berbeda dari ajaxHitung() yang menerima kpi_id — endpoint ini
    // menerima turunan_id karena setiap Turunan punya polarity &
    // target sendiri yang independen dari KPI Induk.
    public function ajaxHitungTurunan()
    {
        $turunanId = (int)$this->request->getPost('turunan_id');
        $pegawaiId = (int)$this->request->getPost('pegawai_id');
        $realisasi = $this->request->getPost('realisasi');

        if (!$this->canAccessPegawai($pegawaiId)) {
            return $this->response->setJSON(['valid' => false, 'csrf_hash' => csrf_hash()]);
        }

        $turunan = $this->kpiPegawaiTurunanModel->find($turunanId);
        if (!$turunan) {
            return $this->response->setJSON(['valid' => false, 'csrf_hash' => csrf_hash()]);
        }

        // Verifikasi Turunan ini memang milik pegawai yang bersangkutan
        $induk = $this->kpiPegawaiModel->find($turunan['kpi_pegawai_id']);
        if (!$induk || (int)$induk['pegawai_id'] !== $pegawaiId) {
            return $this->response->setJSON(['valid' => false, 'csrf_hash' => csrf_hash()]);
        }

        // Field belum diisi sama sekali -> jangan tampilkan preview skor
        // (berbeda dari realisasi = 0 yang sengaja diisi, yang tetap valid
        // untuk Turunan ber-polaritas 'min').
        if ($realisasi === null || $realisasi === '') {
            return $this->response->setJSON(['valid' => false, 'csrf_hash' => csrf_hash()]);
        }
        $realisasi = (float)$realisasi;

        $target   = (float)($turunan['target']  ?? 100);
        $polarity = $turunan['polarity']          ?? 'max';
        $bobot    = (float)($turunan['bobot']    ?? 0);

        // is_capped selalu true sesuai keputusan desain
        $skor        = $this->calculator->hitungSkorCapaian($realisasi, $target, $polarity, true);
        $kontribusiT = $bobot > 0 ? $skor * $bobot : 0;
        $color       = $this->calculator->getColorBySkor($skor);

        return $this->response->setJSON([
            'valid'        => true,
            'skor'         => round($skor, 2),
            'kontribusi_t' => round($kontribusiT, 4),
            'bobot'        => $bobot,
            'color'        => $color,
            'csrf_hash'    => csrf_hash(),
        ]);
    }
}