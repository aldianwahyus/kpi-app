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
    protected PenilaianModel               $penilaianModel;
    protected PegawaiModel                 $pegawaiModel;
    protected KpiPegawaiModel              $kpiPegawaiModel;
    protected KpiPegawaiTurunanModel       $kpiPegawaiTurunanModel;
    protected PenilaianTurunanModel        $penilaianTurunanModel;
    protected PeriodeModel                 $periodeModel;
    protected KpiCalculationService        $calculator;
    protected AuditService                 $auditService;
    protected AuditLogModel                $auditLogModel;

    public function __construct()
    {
        $this->penilaianModel               = new PenilaianModel();
        $this->pegawaiModel                 = new PegawaiModel();
        $this->kpiPegawaiModel              = new KpiPegawaiModel();
        $this->kpiPegawaiTurunanModel       = new KpiPegawaiTurunanModel();
        $this->penilaianTurunanModel        = new PenilaianTurunanModel();
        $this->periodeModel                 = new PeriodeModel();
        $this->calculator                   = new KpiCalculationService();
        $this->auditService                 = new AuditService();
        $this->auditLogModel                = new AuditLogModel();
    }

    public function index()
    {
        $check = $this->checkMenuAccess('penilaian');
        if ($check !== true) return $check;

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

        // Total bobot EFEKTIF (mempertimbangkan override per Periode) per
        // pegawai untuk Periode Aktif — dipakai di view untuk menandai
        // pegawai yang KPI-nya sudah di-setup tapi bobotnya belum genap
        // 100%, supaya terlihat sebelum masuk ke form (yang akan menolak
        // penginputan selama bobot belum 100% ATAU Target periode ini
        // belum di-setup).
        $kpiBobotTotal   = [];
        $kpiTargetBelum  = [];
        foreach (array_keys($kpiSetupCount) as $pid) {
            if ($periodeAktif) {
                $kpiBobotTotal[$pid] = $this->kpiPegawaiModel->getTotalBobotUntukPeriode($pid, $periodeAktif);
                $kpiTargetBelum[$pid] = $this->adaTargetBelumSetup($pid, $periodeAktif);
            } else {
                $kpiBobotTotal[$pid] = 0.0;
                $kpiTargetBelum[$pid] = true;
            }
        }

        return view('layouts/main', [
            'title'   => 'Input Penilaian KPI',
            'content' => view('penilaian/_content', [
                'pegawai'       => $pegawai,
                'rekap'         => $rekap,
                'periodeAktif'  => $periodeAktif,
                'role'          => $role,
                'kpiSetupCount'  => $kpiSetupCount,
                'kpiBobotTotal'  => $kpiBobotTotal,
                'kpiTargetBelum' => $kpiTargetBelum,
            ]),
        ]);
    }

    public function form(int $pegawaiId)
    {
        $check = $this->checkMenuAccess('penilaian');
        if ($check !== true) return $check;

        if (!$this->canAccessPegawai($pegawaiId)) return $this->forbidden();

        $periodeAktif = $this->periodeModel->getAktif();
        if (!$periodeAktif) return redirect()->to(base_url('penilaian'))->with('error', 'Tidak ada periode aktif.');

        $pegawai = $this->pegawaiModel->find($pegawaiId);
        if (!$pegawai) {
            return redirect()->to(base_url('penilaian'))->with('error', 'Pegawai tidak ditemukan.');
        }

        $kpiList = $this->kpiPegawaiModel->getByPegawaiUntukPeriode($pegawaiId, $periodeAktif);

        if (empty($kpiList)) return redirect()->to(base_url('penilaian'))->with('error', "KPI untuk {$pegawai['nama']} belum di-setup.");

        // Penginputan penilaian hanya boleh dilakukan jika total Bobot Tahunan
        // (Master Target) KPI pegawai untuk tahun Periode ini sudah tepat
        // 100% — bobot yang belum lengkap akan membuat Nilai Akhir/Yudisium
        // hasil perhitungan menjadi keliru (skalanya tidak lagi 1.00-4.00
        // penuh), jadi diblokir sejak awal di sini, bukan dibiarkan
        // tersimpan lalu ketahuan salah belakangan.
        $totalBobot = $this->kpiPegawaiModel->getTotalBobotUntukPeriode($pegawaiId, $periodeAktif);
        if (round($totalBobot, 2) != 1.00) {
            return redirect()->to(base_url('penilaian'))
                             ->with('error',
                                 "KPI atas nama {$pegawai['nama']} belum mencapai 100% "
                                 . "(saat ini " . round($totalBobot * 100, 2) . "%), "
                                 . "harap selesaikan setup KPI untuk pegawai tersebut.");
        }

        $existing   = $this->penilaianModel->getIndexedByKpi($pegawaiId, $periodeAktif['id']);
        $nilaiAkhir = $this->penilaianModel->getNilaiAkhir($pegawaiId, $periodeAktif['id']);
        $approval   = $this->getStatusApproval($pegawaiId, $periodeAktif['id']);

        // Ambil Parameter Turunan untuk setiap KPI Induk (dengan Target/Bobot
        // sudah diresolve untuk Periode ini), dikelompokkan berdasarkan kp.id
        // (id baris kpi_pegawai), serta realisasi Turunan yang sudah pernah
        // diisi (jika baris penilaian Induk untuk periode ini sudah ada).
        // Sekaligus kumpulkan KPI/Turunan yang Target-nya BELUM di-setup
        // untuk Periode ini — Target kini wajib disiapkan lebih dulu di
        // menu "Master Target" sebelum Penilaian bisa diisi.
        $turunanByInduk    = [];
        $realisasiTurunan  = [];
        $targetBelumSetup  = [];
        foreach ($kpiList as $kpi) {
            $listTurunan = $this->kpiPegawaiTurunanModel->getByKpiPegawaiUntukPeriode($kpi['id'], $periodeAktif);
            $turunanByInduk[$kpi['id']] = $listTurunan;

            if (!empty($listTurunan)) {
                foreach ($listTurunan as $t) {
                    if (($t['polarity'] ?? 'max') !== 'special' && $t['target'] === null) {
                        $targetBelumSetup[] = "{$kpi['nama_kpi']} > {$t['nama_turunan']}";
                    }
                }

                $existingInduk = $existing[$kpi['kpi_id']] ?? null;
                if ($existingInduk) {
                    $realisasiTurunan[$kpi['id']] = $this->penilaianTurunanModel
                        ->getIndexedByTurunan($existingInduk['id']);
                } else {
                    $realisasiTurunan[$kpi['id']] = [];
                }
            } elseif (($kpi['polarity'] ?? 'max') !== 'special' && $kpi['target'] === null) {
                $targetBelumSetup[] = $kpi['nama_kpi'];
            }
        }

        if (!empty($targetBelumSetup)) {
            return redirect()->to(base_url('penilaian'))
                             ->with('error',
                                 "Target untuk Periode \"{$periodeAktif['nama']}\" belum disiapkan pada KPI: "
                                 . implode(', ', $targetBelumSetup)
                                 . ". Silakan lengkapi terlebih dahulu di Master Target.");
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
        $check = $this->checkMenuAccess('penilaian');
        if ($check !== true) return $check;

        if (!$this->canAccessPegawai($pegawaiId)) return $this->forbidden();

        $periodeAktif = $this->periodeModel->getAktif();
        if (!$periodeAktif) {
            return redirect()->to(base_url('penilaian'))->with('error', 'Tidak ada periode aktif.');
        }

        // Pertahanan tambahan (selain di form()): tolak penyimpanan jika
        // bobot EFEKTIF KPI pegawai belum tepat 100% untuk Periode Aktif
        // ini, ATAU jika ada Target yang belum di-setup — seandainya
        // store() dipanggil langsung tanpa melalui form() (mis. bobot
        // berubah setelah form dibuka, atau permintaan POST manual).
        $totalBobot = $this->kpiPegawaiModel->getTotalBobotUntukPeriode($pegawaiId, $periodeAktif);
        if (round($totalBobot, 2) != 1.00) {
            $pegawaiNama = $this->pegawaiModel->find($pegawaiId)['nama'] ?? 'pegawai ini';
            return redirect()->to(base_url('penilaian'))
                             ->with('error',
                                 "KPI atas nama {$pegawaiNama} belum mencapai 100% "
                                 . "(saat ini " . round($totalBobot * 100, 2) . "%), "
                                 . "harap selesaikan setup KPI untuk pegawai tersebut.");
        }

        if ($this->adaTargetBelumSetup($pegawaiId, $periodeAktif)) {
            return redirect()->to(base_url('penilaian'))
                             ->with('error',
                                 "Target untuk Periode \"{$periodeAktif['nama']}\" belum lengkap. "
                                 . "Silakan lengkapi terlebih dahulu di Master Target.");
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

        $kpiList                = $this->kpiPegawaiModel->getByPegawaiUntukPeriode($pegawaiId, $periodeAktif);
        $realisasi              = $this->request->getPost('realisasi')                ?? [];
        $realisasiHarian        = $this->request->getPost('realisasi_harian')          ?? [];
        $catatan                = $this->request->getPost('catatan')                   ?? [];
        $realisasiTurunan       = $this->request->getPost('realisasi_turunan')         ?? [];
        $realisasiTurunanHarian = $this->request->getPost('realisasi_turunan_harian')  ?? [];
        $catatanTurunan         = $this->request->getPost('catatan_turunan')           ?? [];

        $saved = 0;
        foreach ($kpiList as $kpi) {
            $kpiId       = (int)$kpi['kpi_id'];
            $kpiPegawaiId = (int)$kpi['id'];

            $listTurunan = $this->kpiPegawaiTurunanModel->getByKpiPegawaiUntukPeriode($kpiPegawaiId, $periodeAktif);
            $punyaTurunan = !empty($listTurunan);

            if ($punyaTurunan) {
                // ── KPI dengan Parameter Turunan: Alternatif 1 ──────────
                // Skor dihitung per Turunan berdasarkan Target & Polarity
                // masing-masing (yang bisa berbeda antar Turunan), lalu
                // Skor Induk = rata-rata tertimbang menggunakan Cara B:
                //   Skor_Induk = Σ(Skor_T × Bobot_T) / Bobot_Induk
                $turunanInput       = $realisasiTurunan[$kpiPegawaiId]       ?? [];
                $turunanInputHarian = $realisasiTurunanHarian[$kpiPegawaiId] ?? [];
                $sumKontribusiT = 0.0;
                $skorPerTurunan = [];

                // Validasi ALL-OR-NOTHING: semua Turunan harus diisi.
                // Jika ada yang benar-benar belum diisi (kosong), KPI Induk ini
                // dilewati seluruhnya — skor parsial tidak boleh disimpan karena
                // akan menghasilkan nilai yang menyesatkan. Realisasi = 0 yang
                // SENGAJA diisi (bukan dikosongkan) dianggap terisi — untuk KPI
                // ber-polaritas 'min', 0 adalah capaian valid (bahkan terbaik).
                // Untuk polarity 'tertimbang', KEDUA indikator (Posisi Akhir &
                // Rata-rata Harian) harus terisi — salah satu kosong dianggap
                // belum lengkap juga.
                $turunanTidakLengkap = false;
                foreach ($listTurunan as $t) {
                    $rt = $turunanInput[$t['id']] ?? null;
                    if ($rt === null || $rt === '') {
                        $turunanTidakLengkap = true;
                        break;
                    }
                    if (($t['polarity'] ?? 'max') === 'tertimbang') {
                        $rtHarian = $turunanInputHarian[$t['id']] ?? null;
                        if ($rtHarian === null || $rtHarian === '') {
                            $turunanTidakLengkap = true;
                            break;
                        }
                    }
                }

                if ($turunanTidakLengkap) continue;

                foreach ($listTurunan as $t) {
                    $rtFloat      = (float)($turunanInput[$t['id']]);
                    $rtHarianFloat = isset($turunanInputHarian[$t['id']]) ? (float)$turunanInputHarian[$t['id']] : null;
                    $bobotT       = (float)($t['bobot'] ?? 0);

                    $skorT        = $this->calculator->hitungSkor($t, [
                        'realisasi'        => $rtFloat,
                        'realisasi_harian' => $rtHarianFloat,
                    ]);
                    $kontribusiT  = $bobotT > 0 ? $skorT * $bobotT : 0;

                    $sumKontribusiT += $kontribusiT;
                    $skorPerTurunan[$t['id']] = [
                        'realisasi'        => $rtFloat,
                        'realisasi_harian' => $rtHarianFloat,
                        'skor'             => round($skorT, 2),
                        'nilai_kontribusi' => round($kontribusiT, 4),
                    ];
                }

                // Skor Induk = Σ(Skor_T × Bobot_T) / Bobot_Induk (Cara B)
                // Hanya di-cap ke atas (maks 4, batas tertinggi yang mungkin
                // dari polarity mana pun). TIDAK di-floor ke 1 — Turunan
                // ber-polarity 'tertimbang' bisa menghasilkan Skor_T serendah
                // 0,85 (Skor Indikator 1 x Pengkali 0,85), jadi rata-rata
                // tertimbangnya pun sah bernilai di bawah 1. Meng-floor ke 1
                // akan diam-diam menaikkan Skor Induk yang sebenarnya rendah.
                $bobotInduk = (float)$kpi['bobot'];
                $skorInduk  = $bobotInduk > 0 ? $sumKontribusiT / $bobotInduk : 0;
                $skorInduk  = min(4, $skorInduk);

                $real       = null; // tidak relevan untuk kasus Turunan
                $realHarian = null; // tidak relevan untuk kasus Turunan
                $skor       = $skorInduk;
                $kontribusi = $this->calculator->hitungKontribusi($skor, $bobotInduk);

            } else {
                // ── KPI tanpa Parameter Turunan ──
                // Hanya lewati KPI yang benar-benar belum diisi (kosong).
                // Realisasi = 0 yang sengaja diisi tetap disimpan & dihitung —
                // untuk KPI ber-polaritas 'min', 0 adalah capaian valid.
                // Untuk polarity 'tertimbang', KEDUA indikator harus terisi
                // (all-or-nothing) — salah satu kosong dianggap belum diisi.
                $polarity   = $kpi['polarity'] ?? 'max';
                $real       = $realisasi[$kpiId] ?? null;
                $realHarianRaw = $realisasiHarian[$kpiId] ?? null;

                if ($real === null || $real === '') continue;
                if ($polarity === 'tertimbang' && ($realHarianRaw === null || $realHarianRaw === '')) continue;

                $real       = (float)$real;
                $realHarian = $realHarianRaw !== null ? (float)$realHarianRaw : null;
                $skor       = $this->calculator->hitungSkor($kpi, [
                    'realisasi'        => $real,
                    'realisasi_harian' => $realHarian,
                ]);
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
                'realisasi_harian' => $punyaTurunan ? null : $realHarian,
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
                    // Hanya field yang benar-benar belum diisi yang dilewati.
                    // Realisasi = 0 yang sengaja diisi TETAP disimpan — sama
                    // seperti aturan di atas (min-polarity 0 = capaian valid;
                    // special-polarity "Tidak Ada" juga sah-sah saja bernilai
                    // 0). Sebelumnya baris ini keliru melewati SEMUA realisasi
                    // bernilai 0, membuat detail per-Turunan hilang senyap
                    // walau Skor Induk agregatnya sudah benar dihitung di atas.
                    $rt = ($realisasiTurunan[$kpiPegawaiId] ?? [])[$t['id']] ?? null;
                    if ($rt === null || $rt === '') continue;

                    $skorData = $skorPerTurunan[$t['id']] ?? null;
                    if (!$skorData) continue;

                    $this->penilaianTurunanModel->upsert($recordId, (int)$t['id'], [
                        'realisasi'        => $skorData['realisasi'],
                        'realisasi_harian' => $skorData['realisasi_harian'],
                        'skor'             => $skorData['skor'],
                        'nilai_kontribusi' => $skorData['nilai_kontribusi'],
                        'catatan'          => ($catatanTurunan[$kpiPegawaiId] ?? [])[$t['id']] ?? null,
                    ]);
                }
            }

            if ($recordId) {
                if ($punyaTurunan) {
                    $keterangan = 'Input Realisasi per Turunan, Skor Induk = rata-rata tertimbang (' . round($skor, 2) . ')';
                } else {
                    // Representasi realisasi yang mudah dibaca di log audit —
                    // 'special' bersifat biner (bukan angka mentah 0/1), dan
                    // 'tertimbang' punya dua indikator sekaligus.
                    $realDisplay = match ($polarity) {
                        'special'    => ((float)$real == 1.0) ? 'Ada' : 'Tidak Ada',
                        'tertimbang' => "{$real} (Harian: {$realHarian}%)",
                        default      => $real,
                    };
                    $keterangan = $oldData ? "Update Realisasi menjadi $realDisplay" : "Input Realisasi awal $realDisplay";
                }

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
        $check = $this->checkMenuAccess('penilaian');
        if ($check !== true) return $check;

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
        $pegawaiId      = (int)$this->request->getPost('pegawai_id');
        $kpiId          = (int)$this->request->getPost('kpi_id');
        $realisasi      = $this->request->getPost('realisasi');
        $realisasiHarianRaw = $this->request->getPost('realisasi_harian');

        if (!$this->canAccessPegawai($pegawaiId)) {
            return $this->response->setJSON(['valid' => false, 'message' => 'Tidak memiliki akses.']);
        }

        $periodeAktif = $this->periodeModel->getAktif();
        if (!$periodeAktif) {
            return $this->response->setJSON(['valid' => false, 'message' => 'Tidak ada periode aktif.']);
        }

        // 1. Ambil list KPI pegawai dengan Target/Bobot yang sudah diresolve
        //    untuk Periode Aktif ini.
        $kpiList = $this->kpiPegawaiModel->getByPegawaiUntukPeriode($pegawaiId, $periodeAktif);

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

        $polarity = $currentKpi['polarity'] ?? 'max';

        // Target untuk Periode ini belum di-setup -> tidak bisa dihitung
        // sama sekali (kecuali 'special' yang tidak pernah memakai Target).
        if ($polarity !== 'special' && $currentKpi['target'] === null) {
            return $this->response->setJSON(['valid' => false, 'message' => 'Target untuk Periode ini belum di-setup.']);
        }

        // Field belum diisi sama sekali -> jangan tampilkan preview skor
        // (berbeda dari realisasi = 0 yang sengaja diisi, yang tetap valid
        // untuk KPI ber-polaritas 'min'/'special' Tidak Ada). Untuk
        // 'tertimbang', KEDUA indikator harus terisi sebelum preview tampil.
        if ($realisasi === null || $realisasi === '') {
            return $this->response->setJSON(['valid' => false, 'message' => 'Realisasi belum diisi.']);
        }
        if ($polarity === 'tertimbang' && ($realisasiHarianRaw === null || $realisasiHarianRaw === '')) {
            return $this->response->setJSON(['valid' => false, 'message' => 'Realisasi Rata-rata Harian belum diisi.']);
        }

        // 3. Konversi tipe data untuk dikalkulasi
        $realisasi      = (float)$realisasi;
        $realisasiHarian = $realisasiHarianRaw !== null && $realisasiHarianRaw !== '' ? (float)$realisasiHarianRaw : null;
        $target    = (float)($currentKpi['target'] ?? 100);
        $bobot     = (float)($currentKpi['bobot'] ?? 0);

        // 4. Lakukan perhitungan menggunakan Service (dispatcher tunggal
        //    berdasarkan polarity, mencakup max/min/precise/special/tertimbang)
        $skor       = $this->calculator->hitungSkor($currentKpi, [
            'realisasi'        => $realisasi,
            'realisasi_harian' => $realisasiHarian,
        ]);
        $kontribusi = $this->calculator->hitungKontribusi($skor, $bobot);

        // Pencapaian mentah (Realisasi/Target atau Target/Realisasi) untuk
        // kolom "Pencapaian" — hanya bermakna untuk polarity max/min/precise
        // (rasio tunggal). 'special' bersifat biner (Ada/Tidak Ada, tidak
        // ada rasio) dan 'tertimbang' punya DUA rasio terpisah, jadi kolom
        // "Pencapaian" untuk keduanya ditampilkan null di sini — front-end
        // menampilkan representasi lain (badge Ada/Tidak Ada, atau rincian
        // Skor Dasar x Pengkali) alih-alih persentase tunggal.
        // is_infinite() WAJIB diperiksa sebelum masuk JSON: json_encode()
        // gagal/rusak jika diberi nilai INF mentah (kasus realisasi=0 pada
        // KPI 'min' — capaian tak terhingga secara matematis).
        $pencapaianInf    = false;
        $pencapaianPersen = null;
        if (in_array($polarity, ['max', 'min', 'precise'], true) && $target > 0) {
            $pencapaianRaw    = $this->calculator->hitungPencapaianPersen($realisasi, $target, $polarity === 'precise' ? 'max' : $polarity);
            $pencapaianInf    = is_infinite($pencapaianRaw);
            $pencapaianPersen = $pencapaianInf ? null : round($pencapaianRaw, 2);
        }

        // 5. Tentukan warna badge (memakai service yang sama dengan tampilan lain,
        //    agar tidak ada duplikasi logika ambang batas warna)
        $bootstrapColor = $this->calculator->getColorBySkor($skor);

        // 6. Kembalikan response JSON beserta token CSRF baru
        return $this->response->setJSON([
            'valid'              => true,
            'skor'               => round($skor, 2),
            'nilai'              => round($skor, 2), // Nilai = Skor (identik, sesuai skema kriteria pencapaian)
            'kontribusi'         => round($kontribusi, 2),
            'pencapaian'         => $pencapaianPersen,
            'pencapaian_tak_terhingga' => $pencapaianInf,
            'color'      => $bootstrapColor,
            'csrf_hash'  => csrf_hash()
        ]);
    }

    /**
     * Cek apakah pegawai ini punya KPI (Induk atau Turunan) yang Target-nya
     * belum di-setup untuk Periode tertentu — dipakai sebagai gate sebelum
     * Penilaian bisa diisi (form()/store()) maupun sebagai indikator visual
     * di halaman daftar (index()). 'special' dikecualikan karena tidak
     * pernah memakai Target.
     */
    private function adaTargetBelumSetup(int $pegawaiId, array $periode): bool
    {
        $kpiList = $this->kpiPegawaiModel->getByPegawaiUntukPeriode($pegawaiId, $periode);
        foreach ($kpiList as $kpi) {
            $listTurunan = $this->kpiPegawaiTurunanModel->getByKpiPegawaiUntukPeriode($kpi['id'], $periode);
            if (!empty($listTurunan)) {
                foreach ($listTurunan as $t) {
                    if (($t['polarity'] ?? 'max') !== 'special' && $t['target'] === null) return true;
                }
            } elseif (($kpi['polarity'] ?? 'max') !== 'special' && $kpi['target'] === null) {
                return true;
            }
        }
        return false;
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
        $turunanId          = (int)$this->request->getPost('turunan_id');
        $pegawaiId          = (int)$this->request->getPost('pegawai_id');
        $realisasi          = $this->request->getPost('realisasi');
        $realisasiHarianRaw = $this->request->getPost('realisasi_harian');

        if (!$this->canAccessPegawai($pegawaiId)) {
            return $this->response->setJSON(['valid' => false, 'csrf_hash' => csrf_hash()]);
        }

        $periodeAktif = $this->periodeModel->getAktif();
        if (!$periodeAktif) {
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

        // Timpa Target/Bobot dengan yang sudah diresolve dari Master Target
        // untuk Periode Aktif ini (Target = rata-rata Target Bulanan sesuai
        // rentang bulan Periode, NULL jika ada bulan yang belum diisi;
        // Bobot = Bobot Tahunan untuk tahun Periode ini, NULL jika belum
        // diisi).
        $listTurunanResolved = $this->kpiPegawaiTurunanModel
            ->getByKpiPegawaiUntukPeriode((int)$turunan['kpi_pegawai_id'], $periodeAktif);
        $resolved = null;
        foreach ($listTurunanResolved as $row) {
            if ((int)$row['id'] === $turunanId) {
                $resolved = $row;
                break;
            }
        }
        if (!$resolved) {
            return $this->response->setJSON(['valid' => false, 'csrf_hash' => csrf_hash()]);
        }
        $turunan['target'] = $resolved['target'];
        $turunan['bobot']  = $resolved['bobot'];

        $polarity = $turunan['polarity'] ?? 'max';

        // Target untuk Periode ini belum di-setup -> tidak bisa dihitung
        // (kecuali 'special' yang tidak pernah memakai Target).
        if ($polarity !== 'special' && $turunan['target'] === null) {
            return $this->response->setJSON(['valid' => false, 'csrf_hash' => csrf_hash()]);
        }

        // Field belum diisi sama sekali -> jangan tampilkan preview skor
        // (berbeda dari realisasi = 0 yang sengaja diisi, yang tetap valid
        // untuk Turunan ber-polaritas 'min'/'special' Tidak Ada). Untuk
        // 'tertimbang', KEDUA indikator harus terisi.
        if ($realisasi === null || $realisasi === '') {
            return $this->response->setJSON(['valid' => false, 'csrf_hash' => csrf_hash()]);
        }
        if ($polarity === 'tertimbang' && ($realisasiHarianRaw === null || $realisasiHarianRaw === '')) {
            return $this->response->setJSON(['valid' => false, 'csrf_hash' => csrf_hash()]);
        }
        $realisasi       = (float)$realisasi;
        $realisasiHarian = $realisasiHarianRaw !== null && $realisasiHarianRaw !== '' ? (float)$realisasiHarianRaw : null;

        $target = (float)($turunan['target'] ?? 0);
        $bobot  = (float)($turunan['bobot']  ?? 0);

        // Dispatcher tunggal berdasarkan polarity (sama seperti ajaxHitung()
        // & store()) — is_capped selalu true untuk Turunan sesuai keputusan
        // desain, sudah tercakup di dalam hitungSkorCapaian() untuk max/min.
        $skor        = $this->calculator->hitungSkor($turunan, [
            'realisasi'        => $realisasi,
            'realisasi_harian' => $realisasiHarian,
        ]);
        $kontribusiT = $bobot > 0 ? $skor * $bobot : 0;
        $color       = $this->calculator->getColorBySkor($skor);

        // Sama seperti ajaxHitung(): pencapaian tunggal hanya bermakna untuk
        // max/min/precise; is_infinite() WAJIB diperiksa sebelum masuk JSON
        // (realisasi=0 pada Turunan 'min' = capaian tak terhingga).
        $pencapaianInf    = false;
        $pencapaianPersen = null;
        if (in_array($polarity, ['max', 'min', 'precise'], true) && $target > 0) {
            $pencapaianRaw    = $this->calculator->hitungPencapaianPersen($realisasi, $target, $polarity === 'precise' ? 'max' : $polarity);
            $pencapaianInf    = is_infinite($pencapaianRaw);
            $pencapaianPersen = $pencapaianInf ? null : round($pencapaianRaw, 2);
        }

        return $this->response->setJSON([
            'valid'        => true,
            'skor'         => round($skor, 2),
            'nilai'        => round($skor, 2), // Nilai = Skor (identik)
            'kontribusi_t' => round($kontribusiT, 4),
            'pencapaian'   => $pencapaianPersen,
            'pencapaian_tak_terhingga' => $pencapaianInf,
            'bobot'        => $bobot,
            'color'        => $color,
            'csrf_hash'    => csrf_hash(),
        ]);
    }
}