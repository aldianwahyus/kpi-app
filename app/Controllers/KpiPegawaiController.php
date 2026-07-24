<?php
namespace App\Controllers;

use App\Models\KpiPegawaiModel;
use App\Models\KpiPegawaiTurunanModel;
use App\Models\KpiDivisiModel;
use App\Models\PegawaiModel;
use App\Models\DivisiModel;
use App\Models\PeriodeModel;

class KpiPegawaiController extends BaseController
{
    protected KpiPegawaiModel               $kpiPegawaiModel;
    protected KpiPegawaiTurunanModel        $kpiPegawaiTurunanModel;
    protected KpiDivisiModel                $kpiDivisiModel;
    protected PegawaiModel                  $pegawaiModel;
    protected DivisiModel                   $divisiModel;
    protected PeriodeModel                  $periodeModel;

    public function __construct()
    {
        $this->kpiPegawaiModel        = new KpiPegawaiModel();
        $this->kpiPegawaiTurunanModel = new KpiPegawaiTurunanModel();
        $this->kpiDivisiModel         = new KpiDivisiModel();
        $this->pegawaiModel           = new PegawaiModel();
        $this->divisiModel            = new DivisiModel();
        $this->periodeModel           = new PeriodeModel();
    }

    /**
     * Field tambahan Parameter Turunan yang bergantung pada Polarity
     * (identik dengan skema di KPI Unit/Modul Direktorat) — dipusatkan di
     * sini karena dipakai sama persis oleh addTurunan() & updateTurunan().
     */
    private function buildTurunanPolarityData(string $polarity, string $perubahanPolarityRaw): array
    {
        return [
            'perubahan_polarity' => in_array($polarity, ['max', 'min'], true)
                ? (in_array($perubahanPolarityRaw, ['pos', 'neg'], true) ? $perubahanPolarityRaw : 'pos')
                : 'pos',
            'toleransi_skor4' => $polarity === 'precise' ? $this->request->getPost('toleransi_skor4') : null,
            'toleransi_skor3' => $polarity === 'precise' ? $this->request->getPost('toleransi_skor3') : null,
            'toleransi_skor2' => $polarity === 'precise' ? $this->request->getPost('toleransi_skor2') : null,
            'sifat_khusus'    => $polarity === 'special'
                ? (in_array($this->request->getPost('sifat_khusus'), ['maximize', 'minimize'], true)
                    ? $this->request->getPost('sifat_khusus') : 'maximize')
                : null,
        ];
    }

    /**
     * Validasi field tambahan Parameter Turunan yang bergantung pada
     * Polarity. Mengembalikan pesan error, atau null jika valid.
     */
    private function validateTurunanPolarityData(string $polarity): ?string
    {
        if ($polarity === 'precise') {
            $t4 = $this->request->getPost('toleransi_skor4');
            $t3 = $this->request->getPost('toleransi_skor3');
            $t2 = $this->request->getPost('toleransi_skor2');
            if ($t4 === null || $t4 === '' || $t3 === null || $t3 === '' || $t2 === null || $t2 === '') {
                return 'Toleransi Skor 4/3/2 wajib diisi untuk polarity Precise is Better.';
            }
            if (!((float)$t4 < (float)$t3 && (float)$t3 < (float)$t2)) {
                return 'Toleransi harus menaik: Toleransi Skor 4 < Toleransi Skor 3 < Toleransi Skor 2.';
            }
        }
        // 'tertimbang' tidak butuh field tambahan — Rata-rata Harian
        // (Indikator 2) dimasukkan langsung sebagai persentase saat
        // penginputan penilaian, bukan konfigurasi per-Turunan.

        return null;
    }

    // ── Daftar pegawai untuk setup KPI ──────────────────────
    public function index()
    {
        $check = $this->checkMenuAccess('kpi_pegawai');
        if ($check !== true) return $check;
        
        $pegawaiList = $this->pegawaiModel->getAllWithDivisi();

        // Drafter & Approver hanya boleh melihat pegawai di divisinya
        // sendiri — sebelumnya daftar ini menampilkan seluruh pegawai
        // perusahaan tanpa filter apa pun, terlepas dari role pengguna.
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

        $divisi      = $this->divisiModel->getActive();

        // Hitung status KPI per pegawai — Bobot & Target TIDAK LAGI dikelola
        // di sini (lihat menu "Master Target"), jadi status di layar ini
        // hanya mencakup jumlah KPI yang sudah di-assign & kesiapan KPI
        // Unit Kerja divisinya.
        $status = [];
        foreach ($pegawaiList as $p) {
            $jumlahKpi    = count($this->kpiPegawaiModel->getByPegawai($p['id']));

            // Cek apakah KPI Unit Kerja sudah 100%
            $bobotDivisi  = $p['divisi_id']
                ? $this->kpiDivisiModel->getTotalBobot($p['divisi_id'])
                : 0;

            $status[$p['id']] = [
                'jumlah_kpi'    => $jumlahKpi,
                'divisi_ok'     => round($bobotDivisi * 100, 2) == 100,
            ];
        }

        // Kelompokkan per divisi
        $grouped = [];
        foreach ($pegawaiList as $p) {
            $key = $p['nama_divisi'] ?? 'Belum Ada Divisi';
            $grouped[$key][] = $p;
        }

        return view('layouts/main', [
            'title'   => 'KPI Per Pegawai',
            'content' => view('kpi_pegawai/_list', [
                'grouped' => $grouped,
                'status'  => $status,
            ]),
        ]);
    }

    // ── Form Setup KPI Per Pegawai ───────────────────────────
    public function edit(int $pegawaiId)
    {
        $check = $this->checkMenuAccess('kpi_pegawai');
        if ($check !== true) return $check;

        if (!$this->canAccessPegawai($pegawaiId)) return $this->forbidden();

        $pegawai  = $this->pegawaiModel->find($pegawaiId);
        if (!$pegawai) {
            return redirect()->to(base_url('kpi-pegawai'))
                             ->with('error', 'Pegawai tidak ditemukan.');
        }

        // Cek KPI Unit Kerja harus sudah 100%
        $bobotDivisi = $pegawai['divisi_id']
            ? $this->kpiDivisiModel->getTotalBobot($pegawai['divisi_id'])
            : 0;

        if (round($bobotDivisi * 100, 2) < 100) {
            return redirect()->to(base_url('kpi-pegawai'))
                             ->with('error',
                               "KPI Unit Kerja divisi <strong>{$pegawai['nama']}</strong>
                                belum mencapai 100%. Setup KPI Per Unit Kerja
                                terlebih dahulu.");
        }

        // KPI yang sudah di-assign ke pegawai ini
        $assigned    = $this->kpiPegawaiModel->getByPegawai($pegawaiId);
        $assignedIds = $this->kpiPegawaiModel->getAssignedKpiIds($pegawaiId);

        // Ambil seluruh Parameter Turunan untuk setiap KPI Induk yang
        // sudah di-assign, dikelompokkan berdasarkan id baris kpi_pegawai
        // (bukan kpi_id), agar setiap Induk dapat menampilkan daftar
        // Turunannya sendiri pada form.
        $turunanByInduk = [];
        foreach ($assigned as $a) {
            $turunanByInduk[$a['id']] = $this->kpiPegawaiTurunanModel->getByKpiPegawai($a['id']);
        }

        // Jika ada Periode Aktif, tampilkan preview Bobot Penilaian & Target
        // (read-only) yang diambil dari Master Target — Bobot/Target TIDAK
        // LAGI diisi/diedit di layar ini sama sekali (lihat menu terpisah
        // "Master Target").
        $periodeAktif      = $this->periodeModel->getAktif();
        $previewIndukById  = [];
        $previewTurunanById= [];
        if ($periodeAktif) {
            foreach ($this->kpiPegawaiModel->getByPegawaiUntukPeriode($pegawaiId, $periodeAktif) as $row) {
                $previewIndukById[$row['id']] = $row;
            }
            foreach ($turunanByInduk as $kpId => $listT) {
                if (empty($listT)) {
                    continue;
                }
                foreach ($this->kpiPegawaiTurunanModel->getByKpiPegawaiUntukPeriode($kpId, $periodeAktif) as $row) {
                    $previewTurunanById[$row['id']] = $row;
                }
            }
        }

        // Pool KPI dari KPI Unit Kerja divisi pegawai
        $kpiPool = $pegawai['divisi_id']
            ? $this->kpiDivisiModel->getByDivisi($pegawai['divisi_id'])
            : [];

        // Kelompokkan assigned per perspektif
        $assignedGrouped = [];
        foreach ($assigned as $row) {
            $assignedGrouped[$row['perspektif']][] = $row;
        }

        // Kelompokkan pool per perspektif
        $poolGrouped = [];
        foreach ($kpiPool as $row) {
            $poolGrouped[$row['perspektif']][] = $row;
        }

        // Daftar seluruh pegawai dikelompokkan per divisi — dipakai untuk
        // mengisi dropdown "Pilih Pegawai Sumber" pada modal Copy KPI.
        // Sebelumnya variabel ini tidak pernah dikirim ke view sehingga
        // dropdown tersebut selalu kosong tanpa ada pesan error apa pun.
        $allPegawai = $this->pegawaiModel->getAllWithDivisi();
        $grouped = [];
        foreach ($allPegawai as $p) {
            $key = $p['nama_divisi'] ?? 'Belum Ada Divisi';
            $grouped[$key][] = $p;
        }

        return view('layouts/main', [
            'title'   => 'KPI Per Pegawai — ' . $pegawai['nama'],
            'content' => view('kpi_pegawai/_form', [
                'pegawai'        => $pegawai,
                'assigned'       => $assigned,
                'assignedIds'    => $assignedIds,
                'assignedGrouped'=> $assignedGrouped,
                'turunanByInduk' => $turunanByInduk,
                'poolGrouped'    => $poolGrouped,
                'grouped'        => $grouped,
                'periodeAktif'      => $periodeAktif,
                'previewIndukById'  => $previewIndukById,
                'previewTurunanById'=> $previewTurunanById,
            ]),
        ]);
    }

    // ── Tambah satu KPI ke Pegawai ───────────────────────────
    public function add(int $pegawaiId)
    {
        $check = $this->checkMenuAccess('kpi_pegawai');
        if ($check !== true) return $check;

        if (!$this->canAccessPegawai($pegawaiId)) return $this->forbidden();

        $pegawai = $this->pegawaiModel->find($pegawaiId);
        if (!$pegawai) {
            return redirect()->to(base_url('kpi-pegawai'))
                             ->with('error', 'Pegawai tidak ditemukan.');
        }

        $kpiId   = (int)$this->request->getPost('kpi_id');

        if ($this->kpiPegawaiModel->isAssigned($pegawaiId, $kpiId)) {
            return redirect()->back()
                             ->with('error', 'KPI sudah ada untuk pegawai ini.');
        }

        // Target 100 hanya bermakna sebagai default awal untuk polarity yang
        // benar-benar memakainya (max/min/precise/tertimbang). Untuk
        // 'special', Target sama sekali tidak dipakai oleh hitungSkorSpecial()
        // — menampilkan angka 100 di form setup hanya akan menyesatkan Admin
        // seolah perlu diisi. Diset 0 (bukan NULL, karena kolom NOT NULL)
        // supaya form setup menampilkannya kosong/nihil.
        $kpiUnit    = (new \App\Models\KpiUnitModel())->find($kpiId);
        $targetAwal = (($kpiUnit['polarity'] ?? 'max') === 'special') ? 0 : 100.00;

        $this->kpiPegawaiModel->insert([
            'pegawai_id' => $pegawaiId,
            'kpi_id'     => $kpiId,
            'divisi_id'  => $pegawai['divisi_id'],
            'bobot'      => 0,
            'target'     => $targetAwal,
            'urutan'     => (int)$this->request->getPost('urutan') ?: 99,
            'is_active'  => 1,
        ]);

        return redirect()->to(base_url("kpi-pegawai/edit/$pegawaiId"))
                         ->with('success', 'KPI berhasil ditambahkan.');
    }

    // ── Simpan Deskripsi Target (batch update) ────────────────
    // Bobot & Target TIDAK LAGI dikelola di layar ini sama sekali — lihat
    // menu terpisah "Master Target". Endpoint ini hanya menyimpan teks
    // panduan pengisian Realisasi ("Deskripsi Target") per KPI.
    public function saveDeskripsi(int $pegawaiId)
    {
        $check = $this->checkMenuAccess('kpi_pegawai');
        if ($check !== true) return $check;

        if (!$this->canAccessPegawai($pegawaiId)) return $this->forbidden();

        $ids              = $this->request->getPost('kp_id')            ?? [];
        $deskripsiTargets = $this->request->getPost('deskripsi_target') ?? [];

        foreach ($ids as $i => $kpId) {
            $this->kpiPegawaiModel->update((int)$kpId, [
                'deskripsi_target' => $deskripsiTargets[$i] ?? null,
            ]);
        }

        return redirect()->to(base_url("kpi-pegawai/edit/$pegawaiId"))
                         ->with('success', 'Deskripsi Target berhasil disimpan.');
    }

    // ── Hapus KPI dari Pegawai ───────────────────────────────
    public function delete(int $id)
    {
        $check = $this->checkMenuAccess('kpi_pegawai');
        if ($check !== true) return $check;

        $row = $this->kpiPegawaiModel->find($id);
        if (!$row) {
            return redirect()->to(base_url('kpi-pegawai'))
                             ->with('error', 'Data KPI tidak ditemukan atau sudah dihapus.');
        }

        if (!$this->canAccessPegawai($row['pegawai_id'])) return $this->forbidden();

        // Hapus seluruh Parameter Turunan terlebih dahulu agar tidak
        // menjadi data yatim setelah Parameter Induknya dihapus.
        $this->kpiPegawaiTurunanModel->deleteByKpiPegawai($id);
        $this->kpiPegawaiModel->delete($id);

        return redirect()->to(base_url("kpi-pegawai/edit/{$row['pegawai_id']}"))
                         ->with('success', 'KPI berhasil dihapus.');
    }

    // ── Tambah Parameter Turunan ke suatu Parameter Induk ────
    public function addTurunan(int $kpiPegawaiId)
    {
        $check = $this->checkMenuAccess('kpi_pegawai');
        if ($check !== true) return $check;

        $induk = $this->kpiPegawaiModel->find($kpiPegawaiId);
        if (!$induk) {
            return redirect()->back()
                             ->with('error', 'Parameter Induk tidak ditemukan.');
        }

        if (!$this->canAccessPegawai($induk['pegawai_id'])) return $this->forbidden();

        $nama              = trim($this->request->getPost('nama_turunan')      ?? '');
        $deskripsiTarget   = trim($this->request->getPost('deskripsi_target')  ?? '') ?: null;
        $polarity          = $this->request->getPost('polarity')           ?? 'max';
        $perubahanPolarityRaw = $this->request->getPost('perubahan_polarity')  ?? 'pos';
        $satuan            = trim($this->request->getPost('satuan')         ?? '') ?: null;

        // Validasi enum agar tidak bisa dimanipulasi lewat POST
        if (!in_array($polarity, ['max', 'min', 'precise', 'special', 'tertimbang'], true)) $polarity = 'max';

        if ($nama === '') {
            return redirect()->to(base_url("kpi-pegawai/edit/{$induk['pegawai_id']}"))
                             ->with('error', 'Nama Parameter Turunan wajib diisi.');
        }

        // Bobot & Target TIDAK diisi/divalidasi di sini — Bobot & Target
        // Parameter Turunan sepenuhnya dikelola di menu "Master Target"
        // (per tahun/per bulan), bukan saat Parameter Turunan dibuat.

        if ($errPolarity = $this->validateTurunanPolarityData($polarity)) {
            return redirect()->to(base_url("kpi-pegawai/edit/{$induk['pegawai_id']}"))
                             ->with('error', $errPolarity);
        }

        $this->kpiPegawaiTurunanModel->insert(array_merge([
            'kpi_pegawai_id'   => $kpiPegawaiId,
            'nama_turunan'     => $nama,
            'deskripsi_target' => $deskripsiTarget,
            'polarity'         => $polarity,
            'satuan'           => $satuan,
            'urutan'           => (int)$this->request->getPost('urutan') ?: 99,
            'is_active'        => 1,
        ], $this->buildTurunanPolarityData($polarity, $perubahanPolarityRaw)));

        return redirect()->to(base_url("kpi-pegawai/edit/{$induk['pegawai_id']}"))
                         ->with('success', 'Parameter Turunan berhasil ditambahkan.');
    }

    // ── Hapus satu Parameter Turunan ──────────────────────────
    public function deleteTurunan(int $id)
    {
        $check = $this->checkMenuAccess('kpi_pegawai');
        if ($check !== true) return $check;

        $turunan = $this->kpiPegawaiTurunanModel->find($id);
        if (!$turunan) {
            return redirect()->to(base_url('kpi-pegawai'))
                             ->with('error', 'Parameter Turunan tidak ditemukan atau sudah dihapus.');
        }

        $induk = $this->kpiPegawaiModel->find($turunan['kpi_pegawai_id']);

        if ($induk && !$this->canAccessPegawai($induk['pegawai_id'])) return $this->forbidden();

        $this->kpiPegawaiTurunanModel->delete($id);

        $pegawaiId = $induk['pegawai_id'] ?? null;
        if (!$pegawaiId) {
            return redirect()->to(base_url('kpi-pegawai'))
                             ->with('success', 'Parameter Turunan berhasil dihapus.');
        }

        return redirect()->to(base_url("kpi-pegawai/edit/$pegawaiId"))
                         ->with('success', 'Parameter Turunan berhasil dihapus.');
    }

    // ── Update satu Parameter Turunan ─────────────────────────
    public function updateTurunan(int $id)
    {
        $check = $this->checkMenuAccess('kpi_pegawai');
        if ($check !== true) return $check;

        $turunan = $this->kpiPegawaiTurunanModel->find($id);
        if (!$turunan) {
            return redirect()->to(base_url('kpi-pegawai'))
                             ->with('error', 'Parameter Turunan tidak ditemukan atau sudah dihapus.');
        }

        $induk = $this->kpiPegawaiModel->find($turunan['kpi_pegawai_id']);
        if (!$induk) {
            return redirect()->to(base_url('kpi-pegawai'))
                             ->with('error', 'Parameter Induk tidak ditemukan.');
        }

        if (!$this->canAccessPegawai($induk['pegawai_id'])) return $this->forbidden();

        $nama              = trim($this->request->getPost('nama_turunan')      ?? '');
        $deskripsiTarget   = trim($this->request->getPost('deskripsi_target')  ?? '') ?: null;
        $polarity          = $this->request->getPost('polarity')           ?? 'max';
        $perubahanPolarityRaw = $this->request->getPost('perubahan_polarity')  ?? 'pos';
        $satuan            = trim($this->request->getPost('satuan')         ?? '') ?: null;

        // Validasi enum agar tidak bisa dimanipulasi lewat POST
        if (!in_array($polarity, ['max', 'min', 'precise', 'special', 'tertimbang'], true)) $polarity = 'max';

        if ($nama === '') {
            return redirect()->to(base_url("kpi-pegawai/edit/{$induk['pegawai_id']}"))
                             ->with('error', 'Nama Parameter Turunan wajib diisi.');
        }

        // Bobot & Target TIDAK diisi/divalidasi di sini — dikelola di menu
        // "Master Target" (per tahun/per bulan).

        if ($errPolarity = $this->validateTurunanPolarityData($polarity)) {
            return redirect()->to(base_url("kpi-pegawai/edit/{$induk['pegawai_id']}"))
                             ->with('error', $errPolarity);
        }

        $this->kpiPegawaiTurunanModel->update($id, array_merge([
            'nama_turunan'     => $nama,
            'deskripsi_target' => $deskripsiTarget,
            'polarity'         => $polarity,
            'satuan'           => $satuan,
        ], $this->buildTurunanPolarityData($polarity, $perubahanPolarityRaw)));

        return redirect()->to(base_url("kpi-pegawai/edit/{$induk['pegawai_id']}"))
                         ->with('success', 'Parameter Turunan berhasil diperbarui.');
    }

    // ── Copy KPI dari pegawai lain (same jabatan) ────────────
    // Mengubah nama method menjadi copy() agar cocok dengan route form action `kpi-pegawai/copy/...`
    public function copy(int $pegawaiId)
    {
        $check = $this->checkMenuAccess('kpi_pegawai');
        if ($check !== true) return $check;

        if (!$this->canAccessPegawai($pegawaiId)) return $this->forbidden();

        $sourceId = (int)$this->request->getPost('source_pegawai_id');
        $pegawai  = $this->pegawaiModel->find($pegawaiId);

        if (!$pegawai) {
            return redirect()->to(base_url('kpi-pegawai'))
                             ->with('error', 'Pegawai tujuan tidak ditemukan.');
        }

        if (!$sourceId || $sourceId === $pegawaiId) {
            return redirect()->back()
                             ->with('error', 'Pilih pegawai sumber yang valid.');
        }

        if (!$this->canAccessPegawai($sourceId)) return $this->forbidden();

        $sourceKpi = $this->kpiPegawaiModel->getByPegawai($sourceId);
        if (empty($sourceKpi)) {
            return redirect()->back()
                             ->with('error', 'Pegawai sumber belum memiliki KPI.');
        }

        // Hapus KPI lama pegawai tujuan (perilaku copy satu pegawai — replace)
        $this->kpiPegawaiModel->deleteByPegawai($pegawaiId);

        $now = date('Y-m-d H:i:s');
        foreach ($sourceKpi as $kpi) {
            $newKpId = $this->kpiPegawaiModel->insert([
                'pegawai_id'       => $pegawaiId,
                'kpi_id'           => $kpi['kpi_id'],
                'divisi_id'        => $pegawai['divisi_id'],
                'bobot'            => $kpi['bobot'],
                'target'           => $kpi['target'] ?? 100.00,
                'deskripsi_target' => $kpi['deskripsi_target'] ?? null,
                'urutan'           => $kpi['urutan'],
                'is_active'        => 1,
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);

            // Salin Parameter Turunan jika ada (perbaikan: sebelumnya Turunan
            // tidak ikut disalin sehingga konfigurasi Turunan hilang)
            if ($newKpId) {
                $turunans = $this->kpiPegawaiTurunanModel
                                 ->getByKpiPegawai($kpi['id']);
                foreach ($turunans as $t) {
                    $this->kpiPegawaiTurunanModel->insert([
                        'kpi_pegawai_id'     => $newKpId,
                        'nama_turunan'       => $t['nama_turunan'],
                        'bobot'              => $t['bobot'],
                        'target'             => $t['target'],
                        'deskripsi_target'   => $t['deskripsi_target'] ?? null,
                        'polarity'           => $t['polarity']           ?? 'max',
                        'perubahan_polarity' => $t['perubahan_polarity'] ?? 'pos',
                        // Field konfigurasi polarity baru (Precise/Special)
                        // — WAJIB ikut disalin, karena Turunan tidak punya
                        // fallback lain (tidak seperti KPI Unit, Turunan
                        // sepenuhnya independen). Tanpa ini, Turunan hasil
                        // salin akan kehilangan konfigurasinya dan skornya
                        // akan salah dihitung senyap.
                        'toleransi_skor4'    => $t['toleransi_skor4']    ?? null,
                        'toleransi_skor3'    => $t['toleransi_skor3']    ?? null,
                        'toleransi_skor2'    => $t['toleransi_skor2']    ?? null,
                        'sifat_khusus'       => $t['sifat_khusus']       ?? null,
                        'satuan'             => $t['satuan']             ?? null,
                        'urutan'             => $t['urutan'],
                        'is_active'          => 1,
                        'created_at'         => $now,
                        'updated_at'         => $now,
                    ]);
                }
            }
        }

        return redirect()->to(base_url("kpi-pegawai/edit/$pegawaiId"))
                         ->with('success', 'KPI beserta Parameter Turunan berhasil disalin dari pegawai lain.');
    }

    // ══ SALIN MASSAL KE SELURUH PEGAWAI DI SATU DIVISI ═══════

    public function copyMassalForm()
    {
        $check = $this->checkMenuAccess('kpi_pegawai');
        if ($check !== true) return $check;

        $pegawaiList = $this->pegawaiModel->getAllWithDivisi();
        $divisiList  = $this->divisiModel->getActive();

        return view('layouts/main', [
            'title'   => 'Salin KPI Massal per Divisi',
            'content' => view('kpi_pegawai/_copy_massal', [
                'pegawaiList' => $pegawaiList,
                'divisiList'  => $divisiList,
            ]),
        ]);
    }

    public function copyMassal()
    {
        $check = $this->checkMenuAccess('kpi_pegawai');
        if ($check !== true) return $check;

        $sourceId = (int)$this->request->getPost('source_pegawai_id');
        $divisiId = (int)$this->request->getPost('divisi_id');

        if (!$sourceId || !$divisiId) {
            return redirect()->back()->withInput()
                             ->with('error', 'Pegawai sumber dan divisi tujuan wajib dipilih.');
        }

        if (!$this->canAccessPegawai($sourceId)) return $this->forbidden();

        $sourceKpi = $this->kpiPegawaiModel->getByPegawai($sourceId);
        if (empty($sourceKpi)) {
            return redirect()->back()
                             ->with('error', 'Pegawai sumber belum memiliki KPI.');
        }

        // Semua pegawai di divisi tujuan kecuali pegawai sumber itu sendiri
        $semuaPegawai = $this->pegawaiModel->db->table('pegawai')
            ->where('divisi_id', $divisiId)
            ->where('is_active', 1)
            ->where('id !=', $sourceId)
            ->get()->getResultArray();

        if (empty($semuaPegawai)) {
            return redirect()->back()
                             ->with('error', 'Tidak ada pegawai lain di divisi yang dipilih.');
        }

        $berhasil = 0;
        $dilewati = 0;
        $now      = date('Y-m-d H:i:s');

        // Cache Turunan sumber — query sekali, tidak per pegawai
        $turunanBySumber = [];
        foreach ($sourceKpi as $kpi) {
            $turunanBySumber[$kpi['id']] = $this->kpiPegawaiTurunanModel
                                                ->getByKpiPegawai($kpi['id']);
        }

        foreach ($semuaPegawai as $pegawai) {
            $pegawaiId = (int)$pegawai['id'];

            foreach ($sourceKpi as $kpi) {
                // Skip — KPI sudah ada untuk pegawai ini
                if ($this->kpiPegawaiModel->isAssigned($pegawaiId, $kpi['kpi_id'])) {
                    $dilewati++;
                    continue;
                }

                $newKpId = $this->kpiPegawaiModel->insert([
                    'pegawai_id'       => $pegawaiId,
                    'kpi_id'           => $kpi['kpi_id'],
                    'divisi_id'        => $divisiId,
                    'bobot'            => $kpi['bobot'],
                    'target'           => $kpi['target'] ?? 100.00,
                    'deskripsi_target' => $kpi['deskripsi_target'] ?? null,
                    'urutan'           => $kpi['urutan'],
                    'is_active'        => 1,
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ]);

                if ($newKpId) {
                    $berhasil++;
                    foreach (($turunanBySumber[$kpi['id']] ?? []) as $t) {
                        $this->kpiPegawaiTurunanModel->insert([
                            'kpi_pegawai_id'     => $newKpId,
                            'nama_turunan'       => $t['nama_turunan'],
                            'bobot'              => $t['bobot'],
                            'target'             => $t['target'],
                            'deskripsi_target'   => $t['deskripsi_target'] ?? null,
                            'polarity'           => $t['polarity']           ?? 'max',
                            'perubahan_polarity' => $t['perubahan_polarity'] ?? 'pos',
                            'toleransi_skor4'    => $t['toleransi_skor4']    ?? null,
                            'toleransi_skor3'    => $t['toleransi_skor3']    ?? null,
                            'toleransi_skor2'    => $t['toleransi_skor2']    ?? null,
                            'sifat_khusus'       => $t['sifat_khusus']       ?? null,
                            'satuan'             => $t['satuan']             ?? null,
                            'urutan'             => $t['urutan'],
                            'is_active'          => 1,
                            'created_at'         => $now,
                            'updated_at'         => $now,
                        ]);
                    }
                }
            }
        }

        $pesan = "$berhasil KPI berhasil disalin ke " . count($semuaPegawai) . " pegawai.";
        if ($dilewati > 0) $pesan .= " $dilewati KPI dilewati (sudah ada).";

        return redirect()->to(base_url('kpi-pegawai'))->with('success', $pesan);
    }

    // ══ IMPORT KPI PEGAWAI DARI EXCEL ════════════════════════

    public function importForm()
    {
        $check = $this->checkMenuAccess('kpi_pegawai');
        if ($check !== true) return $check;

        return view('layouts/main', [
            'title'   => 'Import KPI Per Pegawai dari Excel',
            'content' => view('kpi_pegawai/_import'),
        ]);
    }

    public function importTemplate()
    {
        $check = $this->checkMenuAccess('kpi_pegawai');
        if ($check !== true) return $check;

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Import KPI Pegawai');

        $headers = [
            'A' => 'Tipe *', 'B' => 'NIP/Email Pegawai *', 'C' => 'Kode KPI *',
            'D' => 'Nama Turunan', 'E' => 'Bobot (desimal) *', 'F' => 'Target *',
            'G' => 'Deskripsi Target', 'H' => 'Satuan',
            'I' => 'Polarity (max/min)', 'J' => 'Perubahan Polarity (pos/neg)', 'K' => 'Urutan',
        ];
        foreach ($headers as $col => $h) {
            $sheet->setCellValue("{$col}1", $h);
            $sheet->getStyle("{$col}1")->getFont()->setBold(true);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $contoh = [
            ['INDUK',   '198501012020', 'MR-F1', '',                  '0.15',  '100', 'Kepuasan nasabah minimal 80%', '',     '',    '',    '1'],
            ['TURUNAN', '',             '',       'Kepuasan Cabang A', '0.075', '80',  'Cabang Mataram min 80 poin',  'Skor', 'max', 'pos', '1'],
            ['TURUNAN', '',             '',       'Kepuasan Cabang B', '0.075', '70',  'Cabang Bima min 70 poin',     'Skor', 'max', 'pos', '2'],
            ['INDUK',   '198501012020', 'MR-C1', '',                  '0.10',  '50',  'Nasabah baru min 50 orang',   '',     '',    '',    '2'],
            ['INDUK',   '199203152021', 'MR-F1', '',                  '0.20',  '90',  '',                            '',     '',    '',    '1'],
        ];
        foreach ($contoh as $i => $row) {
            foreach ($row as $j => $val) {
                $col = chr(ord('A') + $j);
                $sheet->setCellValue("{$col}" . ($i + 2), $val);
            }
        }

        $notes = [
            'M1' => 'CATATAN:', 'M2' => 'Tipe: INDUK atau TURUNAN',
            'M3' => 'NIP/Email di baris TURUNAN boleh kosong (mengikuti INDUK di atasnya)',
            'M4' => 'Kode KPI di baris TURUNAN boleh kosong, isi Nama Turunan-nya',
            'M5' => 'KPI yang sudah ada akan DILEWATI (Skip)',
            'M6' => 'Polarity default: max | Perubahan default: pos',
        ];
        foreach ($notes as $cell => $val) $sheet->setCellValue($cell, $val);
        $sheet->getColumnDimension('M')->setWidth(65);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="Template_Import_KPI_Pegawai.xlsx"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

    public function importProcess()
    {
        $check = $this->checkMenuAccess('kpi_pegawai');
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

        $berhasil  = 0;
        $dilewati  = 0;
        $errors    = [];
        $now       = date('Y-m-d H:i:s');

        // State parser: melacak konteks INDUK terakhir
        $currentPegawaiId = null;
        $currentKpId      = null;
        $currentSkipInduk = false;

        foreach ($rows as $i => $row) {
            if ($i === 1) continue; // Skip header

            $tipe        = strtoupper(trim($row['A'] ?? ''));
            $nikEmail    = trim($row['B'] ?? '');
            $kodeKpi     = strtoupper(trim($row['C'] ?? ''));
            $namaTurunan = trim($row['D'] ?? '');
            $bobot       = trim($row['E'] ?? '');
            $target      = trim($row['F'] ?? '');
            $deskripsi   = trim($row['G'] ?? '');
            $satuan      = trim($row['H'] ?? '');
            $polarity    = strtolower(trim($row['I'] ?? 'max'));
            $perubahan   = strtolower(trim($row['J'] ?? 'pos'));
            $urutan      = (int)($row['K'] ?? 99) ?: 99;

            if ($tipe === '' && $nikEmail === '' && $kodeKpi === '') continue;

            if ($tipe === 'INDUK') {
                $currentSkipInduk = false;
                $currentKpId      = null;
                $currentPegawaiId = null;

                if ($nikEmail === '') {
                    $errors[] = "Baris $i: NIP/Email wajib diisi untuk baris INDUK.";
                    $currentSkipInduk = true; continue;
                }

                // Lookup pegawai via NIP, atau via email di tabel users
                // (tabel pegawai tidak punya kolom email — email ada di users)
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

                $currentPegawaiId = (int)$pegawai['id'];

                if ($kodeKpi === '') {
                    $errors[] = "Baris $i: Kode KPI wajib diisi untuk baris INDUK.";
                    $currentSkipInduk = true; continue;
                }

                $kpiUnit = $this->pegawaiModel->db->table('kpi_unit')
                    ->where('kode', $kodeKpi)->where('is_active', 1)
                    ->get()->getRowArray();

                if (!$kpiUnit) {
                    $errors[] = "Baris $i: Kode KPI '$kodeKpi' tidak ditemukan.";
                    $currentSkipInduk = true; continue;
                }

                if ($bobot === '' || !is_numeric($bobot)) {
                    $errors[] = "Baris $i: Bobot tidak valid.";
                    $currentSkipInduk = true; continue;
                }

                if ($this->kpiPegawaiModel->isAssigned($currentPegawaiId, (int)$kpiUnit['id'])) {
                    $dilewati++;
                    $currentSkipInduk = true; continue;
                }

                $currentKpId = $this->kpiPegawaiModel->insert([
                    'pegawai_id'       => $currentPegawaiId,
                    'kpi_id'           => (int)$kpiUnit['id'],
                    'divisi_id'        => $pegawai['divisi_id'],
                    'bobot'            => (float)$bobot,
                    'target'           => $target !== '' ? (float)$target : 100.00,
                    'deskripsi_target' => $deskripsi ?: null,
                    'urutan'           => $urutan,
                    'is_active'        => 1,
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ]);

                if ($currentKpId) { $berhasil++; }
                else {
                    $errors[] = "Baris $i: Gagal menyimpan KPI '$kodeKpi'.";
                    $currentSkipInduk = true;
                }

            } elseif ($tipe === 'TURUNAN') {
                if ($currentSkipInduk || !$currentKpId) continue;

                if ($namaTurunan === '') {
                    $errors[] = "Baris $i: Nama Turunan wajib diisi."; continue;
                }
                if ($bobot === '' || !is_numeric($bobot)) {
                    $errors[] = "Baris $i: Bobot tidak valid untuk Turunan '$namaTurunan'."; continue;
                }

                if (!in_array($polarity, ['max', 'min']))   $polarity = 'max';
                if (!in_array($perubahan, ['pos', 'neg']))  $perubahan = 'pos';

                $this->kpiPegawaiTurunanModel->insert([
                    'kpi_pegawai_id'     => $currentKpId,
                    'nama_turunan'       => $namaTurunan,
                    'bobot'              => (float)$bobot,
                    'target'             => $target !== '' ? (float)$target : 100.00,
                    'deskripsi_target'   => $deskripsi ?: null,
                    'satuan'             => $satuan ?: null,
                    'polarity'           => $polarity,
                    'perubahan_polarity' => $perubahan,
                    'urutan'             => $urutan,
                    'is_active'          => 1,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ]);

            } elseif ($tipe !== '') {
                $errors[] = "Baris $i: Tipe '$tipe' tidak dikenal. Gunakan INDUK atau TURUNAN.";
            }
        }

        $pesan = "$berhasil KPI berhasil diimport.";
        if ($dilewati > 0) $pesan .= " $dilewati KPI dilewati (sudah ada).";
        if (!empty($errors)) {
            $pesan .= ' ' . count($errors) . ' baris bermasalah: '
                    . implode('; ', array_slice($errors, 0, 5));
            if (count($errors) > 5) $pesan .= '... (dan ' . (count($errors) - 5) . ' lainnya)';
        }

        return redirect()->to(base_url('kpi-pegawai'))
                         ->with($berhasil > 0 ? 'success' : 'warning', $pesan);
    }
}