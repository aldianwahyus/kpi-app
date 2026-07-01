<?php
namespace App\Controllers;

use App\Models\KpiPegawaiModel;
use App\Models\KpiPegawaiTurunanModel;
use App\Models\KpiDivisiModel;
use App\Models\PegawaiModel;
use App\Models\DivisiModel;

class KpiPegawaiController extends BaseController
{
    protected KpiPegawaiModel         $kpiPegawaiModel;
    protected KpiPegawaiTurunanModel  $kpiPegawaiTurunanModel;
    protected KpiDivisiModel          $kpiDivisiModel;
    protected PegawaiModel            $pegawaiModel;
    protected DivisiModel             $divisiModel;

    public function __construct()
    {
        $this->kpiPegawaiModel        = new KpiPegawaiModel();
        $this->kpiPegawaiTurunanModel = new KpiPegawaiTurunanModel();
        $this->kpiDivisiModel         = new KpiDivisiModel();
        $this->pegawaiModel           = new PegawaiModel();
        $this->divisiModel            = new DivisiModel();
    }

    // ── Daftar pegawai untuk setup KPI ──────────────────────
    public function index(): string
    {
        $check = $this->checkMenuAccess('penilaian');
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

        // Hitung status KPI per pegawai
        $status = [];
        foreach ($pegawaiList as $p) {
            $totalBobot   = $this->kpiPegawaiModel->getTotalBobot($p['id']);
            $jumlahKpi    = count($this->kpiPegawaiModel->getByPegawai($p['id']));

            // Cek apakah KPI Unit Kerja sudah 100%
            $bobotDivisi  = $p['divisi_id']
                ? $this->kpiDivisiModel->getTotalBobot($p['divisi_id'])
                : 0;

            $status[$p['id']] = [
                'jumlah_kpi'    => $jumlahKpi,
                'total_bobot'   => $totalBobot,
                'bobot_ok'      => round($totalBobot * 100, 2) == 100,
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
    public function edit(int $pegawaiId): string
    {
        $check = $this->checkMenuAccess('penilaian');
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
        $totalBobot  = $this->kpiPegawaiModel->getTotalBobot($pegawaiId);

        // Ambil seluruh Parameter Turunan untuk setiap KPI Induk yang
        // sudah di-assign, dikelompokkan berdasarkan id baris kpi_pegawai
        // (bukan kpi_id), agar setiap Induk dapat menampilkan daftar
        // Turunannya sendiri pada form.
        $turunanByInduk = [];
        foreach ($assigned as $a) {
            $turunanByInduk[$a['id']] = $this->kpiPegawaiTurunanModel->getByKpiPegawai($a['id']);
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
                'totalBobot'     => $totalBobot,
                'grouped'        => $grouped,
            ]),
        ]);
    }

    // ── Tambah satu KPI ke Pegawai ───────────────────────────
    public function add(int $pegawaiId)
    {
        $check = $this->checkMenuAccess('penilaian');
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

        $this->kpiPegawaiModel->insert([
            'pegawai_id' => $pegawaiId,
            'kpi_id'     => $kpiId,
            'divisi_id'  => $pegawai['divisi_id'],
            'bobot'      => 0,
            'target'     => 100.00, // Menambahkan default value untuk kolom target baru
            'urutan'     => (int)$this->request->getPost('urutan') ?: 99,
            'is_active'  => 1,
        ]);

        return redirect()->to(base_url("kpi-pegawai/edit/$pegawaiId"))
                         ->with('success', 'KPI berhasil ditambahkan.');
    }

    // ── Simpan bobot & target (batch update) ──────────────────
    public function saveBobot(int $pegawaiId)
    {
        $check = $this->checkMenuAccess('penilaian');
        if ($check !== true) return $check;

        if (!$this->canAccessPegawai($pegawaiId)) return $this->forbidden();

        $bobots  = $this->request->getPost('bobot')  ?? [];
        $targets = $this->request->getPost('target') ?? [];
        $ids     = $this->request->getPost('kp_id')  ?? [];

        // Tolak secara eksplisit apabila ada nilai bobot di luar rentang 0-1,
        // alih-alih menebak maksud pengguna (format desimal sudah ditegaskan
        // pada elemen input di _form.php: min="0" max="1").
        foreach ($bobots as $b) {
            if ((float)$b < 0 || (float)$b > 1) {
                return redirect()->back()
                                 ->with('error',
                                     'Nilai bobot harus berupa desimal antara 0 dan 1 '
                                     . '(misalnya 0.10 untuk 10%). Periksa kembali input Anda.');
            }
        }

        // Validasi total bobot = 100%
        $total = array_sum($bobots);

        if (round($total, 2) != 1.00) {
            return redirect()->back()
                             ->with('error',
                                 'Total bobot harus = 100%. '
                                 . 'Saat ini: ' . round($total * 100, 2) . '%');
        }

        foreach ($ids as $i => $kpId) {
            $kpId = (int)$kpId;
            $bobotBaru  = (float)($bobots[$i] ?? 0);
            $targetBaru = (float)($targets[$i] ?? 100.00);

            // Jika Parameter Induk ini sudah memiliki Parameter Turunan,
            // Bobot Induk maupun Target Induk tidak dapat diubah begitu
            // saja — karena Bobot dan Target Turunan yang sudah ada
            // merupakan hasil pembagian dari pagu Bobot/Target Induk
            // sebelumnya. Mengubah pagu tanpa menyesuaikan Turunannya
            // akan membuat SUM Turunan tidak lagi sama dengan pagu yang baru.
            if ($this->kpiPegawaiTurunanModel->hasTurunan($kpId)) {
                $totalBobotTurunan  = $this->kpiPegawaiTurunanModel->getTotalBobot($kpId);
                $totalTargetTurunan = $this->kpiPegawaiTurunanModel->getTotalTarget($kpId);

                if (round($totalBobotTurunan, 4) != round($bobotBaru, 4)) {
                    return redirect()->back()
                                     ->with('error',
                                         'Bobot Parameter Induk yang sudah memiliki Parameter Turunan '
                                         . 'tidak dapat diubah secara langsung, karena akan membuat '
                                         . 'total bobot Turunannya (' . round($totalBobotTurunan * 100, 2) . '%) '
                                         . 'tidak lagi sesuai. Hapus atau sesuaikan Parameter Turunan '
                                         . 'terlebih dahulu sebelum mengubah Bobot Induk.');
                }

                if (round($totalTargetTurunan, 2) != round($targetBaru, 2)) {
                    return redirect()->back()
                                     ->with('error',
                                         'Target Parameter Induk yang sudah memiliki Parameter Turunan '
                                         . 'tidak dapat diubah secara langsung, karena akan membuat '
                                         . 'total target Turunannya (' . number_format($totalTargetTurunan, 2) . ') '
                                         . 'tidak lagi sesuai. Hapus atau sesuaikan Parameter Turunan '
                                         . 'terlebih dahulu sebelum mengubah Target Induk.');
                }
            }

            $this->kpiPegawaiModel->update($kpId, [
                'bobot'  => $bobotBaru,
                'target' => $targetBaru,
            ]);
        }

        return redirect()->to(base_url("kpi-pegawai/edit/$pegawaiId"))
                         ->with('success', 'Konfigurasi bobot dan target KPI berhasil disimpan.');
    }

    // ── Hapus KPI dari Pegawai ───────────────────────────────
    public function delete(int $id)
    {
        $check = $this->checkMenuAccess('penilaian');
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
        $check = $this->checkMenuAccess('penilaian');
        if ($check !== true) return $check;

        $induk = $this->kpiPegawaiModel->find($kpiPegawaiId);
        if (!$induk) {
            return redirect()->back()
                             ->with('error', 'Parameter Induk tidak ditemukan.');
        }

        if (!$this->canAccessPegawai($induk['pegawai_id'])) return $this->forbidden();

        $nama   = trim($this->request->getPost('nama_turunan') ?? '');
        $bobot  = (float)$this->request->getPost('bobot');
        $target = (float)$this->request->getPost('target');

        if ($nama === '') {
            return redirect()->to(base_url("kpi-pegawai/edit/{$induk['pegawai_id']}"))
                             ->with('error', 'Nama Parameter Turunan wajib diisi.');
        }

        if ($bobot <= 0) {
            return redirect()->to(base_url("kpi-pegawai/edit/{$induk['pegawai_id']}"))
                             ->with('error', 'Bobot Parameter Turunan harus lebih besar dari 0.');
        }

        if ($target <= 0) {
            return redirect()->to(base_url("kpi-pegawai/edit/{$induk['pegawai_id']}"))
                             ->with('error', 'Target Parameter Turunan harus lebih besar dari 0.');
        }

        // Validasi tegas: SUM Bobot Turunan (termasuk yang baru ditambahkan
        // ini) tidak boleh melebihi Bobot Induk. Tidak ada toleransi
        // pembulatan — selisih sekecil apa pun akan ditolak.
        $totalBobotTurunanSaatIni = $this->kpiPegawaiTurunanModel->getTotalBobot($kpiPegawaiId);
        $totalSetelahDitambah     = round($totalBobotTurunanSaatIni + $bobot, 4);
        $bobotInduk               = round((float)$induk['bobot'], 4);

        if ($totalSetelahDitambah > $bobotInduk) {
            $sisaBobot = round($bobotInduk - $totalBobotTurunanSaatIni, 4);
            return redirect()->to(base_url("kpi-pegawai/edit/{$induk['pegawai_id']}"))
                             ->with('error',
                                 'Total Bobot Parameter Turunan melebihi Bobot Parameter Induk. '
                                 . 'Sisa bobot yang tersedia: ' . round($sisaBobot * 100, 2) . '%.');
        }

        // Validasi tegas yang sama untuk Target: SUM Target Turunan
        // (termasuk yang baru ditambahkan ini) tidak boleh melebihi
        // Target Induk (pagu yang sudah ditentukan manual oleh Admin).
        $totalTargetTurunanSaatIni = $this->kpiPegawaiTurunanModel->getTotalTarget($kpiPegawaiId);
        $totalTargetSetelahDitambah = round($totalTargetTurunanSaatIni + $target, 2);
        $targetInduk                = round((float)$induk['target'], 2);

        if ($totalTargetSetelahDitambah > $targetInduk) {
            $sisaTarget = round($targetInduk - $totalTargetTurunanSaatIni, 2);
            return redirect()->to(base_url("kpi-pegawai/edit/{$induk['pegawai_id']}"))
                             ->with('error',
                                 'Total Target Parameter Turunan melebihi Target Parameter Induk. '
                                 . 'Sisa target yang tersedia: ' . number_format($sisaTarget, 2) . '.');
        }

        $this->kpiPegawaiTurunanModel->insert([
            'kpi_pegawai_id' => $kpiPegawaiId,
            'nama_turunan'   => $nama,
            'bobot'          => $bobot,
            'target'         => $target,
            'urutan'         => (int)$this->request->getPost('urutan') ?: 99,
            'is_active'      => 1,
        ]);

        return redirect()->to(base_url("kpi-pegawai/edit/{$induk['pegawai_id']}"))
                         ->with('success', 'Parameter Turunan berhasil ditambahkan.');
    }

    // ── Hapus satu Parameter Turunan ──────────────────────────
    public function deleteTurunan(int $id)
    {
        $check = $this->checkMenuAccess('penilaian');
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
        $check = $this->checkMenuAccess('penilaian');
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

        $nama   = trim($this->request->getPost('nama_turunan') ?? '');
        $bobot  = (float)$this->request->getPost('bobot');
        $target = (float)$this->request->getPost('target');

        if ($nama === '') {
            return redirect()->to(base_url("kpi-pegawai/edit/{$induk['pegawai_id']}"))
                             ->with('error', 'Nama Parameter Turunan wajib diisi.');
        }

        if ($bobot <= 0) {
            return redirect()->to(base_url("kpi-pegawai/edit/{$induk['pegawai_id']}"))
                             ->with('error', 'Bobot Parameter Turunan harus lebih besar dari 0.');
        }

        if ($target <= 0) {
            return redirect()->to(base_url("kpi-pegawai/edit/{$induk['pegawai_id']}"))
                             ->with('error', 'Target Parameter Turunan harus lebih besar dari 0.');
        }

        // Validasi tegas Bobot, mengecualikan Turunan ini sendiri dari
        // total saat ini agar tidak terjadi false-positive (nilai lama
        // Turunan ini tidak boleh ikut dihitung dua kali bersama nilai
        // barunya).
        $totalBobotTurunanLain = $this->kpiPegawaiTurunanModel->getTotalBobot($turunan['kpi_pegawai_id'], $id);
        $totalBobotSetelahDiubah = round($totalBobotTurunanLain + $bobot, 4);
        $bobotInduk = round((float)$induk['bobot'], 4);

        if ($totalBobotSetelahDiubah > $bobotInduk) {
            $sisaBobot = round($bobotInduk - $totalBobotTurunanLain, 4);
            return redirect()->to(base_url("kpi-pegawai/edit/{$induk['pegawai_id']}"))
                             ->with('error',
                                 'Total Bobot Parameter Turunan melebihi Bobot Parameter Induk. '
                                 . 'Bobot maksimal untuk Turunan ini: ' . round($sisaBobot * 100, 2) . '%.');
        }

        // Validasi tegas Target dengan pola pengecualian yang sama.
        $totalTargetTurunanLain = $this->kpiPegawaiTurunanModel->getTotalTarget($turunan['kpi_pegawai_id'], $id);
        $totalTargetSetelahDiubah = round($totalTargetTurunanLain + $target, 2);
        $targetInduk = round((float)$induk['target'], 2);

        if ($totalTargetSetelahDiubah > $targetInduk) {
            $sisaTarget = round($targetInduk - $totalTargetTurunanLain, 2);
            return redirect()->to(base_url("kpi-pegawai/edit/{$induk['pegawai_id']}"))
                             ->with('error',
                                 'Total Target Parameter Turunan melebihi Target Parameter Induk. '
                                 . 'Target maksimal untuk Turunan ini: ' . number_format($sisaTarget, 2) . '.');
        }

        $this->kpiPegawaiTurunanModel->update($id, [
            'nama_turunan' => $nama,
            'bobot'        => $bobot,
            'target'       => $target,
        ]);

        return redirect()->to(base_url("kpi-pegawai/edit/{$induk['pegawai_id']}"))
                         ->with('success', 'Parameter Turunan berhasil diperbarui.');
    }

    // ── Copy KPI dari pegawai lain (same jabatan) ────────────
    // Mengubah nama method menjadi copy() agar cocok dengan route form action `kpi-pegawai/copy/...`
    public function copy(int $pegawaiId)
    {
        $check = $this->checkMenuAccess('penilaian');
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

        // Hapus KPI lama pegawai tujuan
        $this->kpiPegawaiModel->deleteByPegawai($pegawaiId);

        // Copy dari sumber
        $now = date('Y-m-d H:i:s');
        foreach ($sourceKpi as $kpi) {
            $this->kpiPegawaiModel->insert([
                'pegawai_id' => $pegawaiId,
                'kpi_id'     => $kpi['kpi_id'],
                'divisi_id'  => $pegawai['divisi_id'],
                'bobot'      => $kpi['bobot'],
                'target'     => $kpi['target'] ?? 100.00, 
                'urutan'     => $kpi['urutan'],
                'is_active'  => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return redirect()->to(base_url("kpi-pegawai/edit/$pegawaiId"))
                         ->with('success', 'KPI berhasil disalin dari pegawai lain.');
    }
}