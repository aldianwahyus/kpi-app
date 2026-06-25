<?php
namespace App\Controllers;

use App\Models\KpiPegawaiModel;
use App\Models\KpiDivisiModel;
use App\Models\PegawaiModel;
use App\Models\DivisiModel;

class KpiPegawaiController extends BaseController
{
    protected KpiPegawaiModel $kpiPegawaiModel;
    protected KpiDivisiModel  $kpiDivisiModel;
    protected PegawaiModel    $pegawaiModel;
    protected DivisiModel     $divisiModel;

    public function __construct()
    {
        $this->kpiPegawaiModel = new KpiPegawaiModel();
        $this->kpiDivisiModel  = new KpiDivisiModel();
        $this->pegawaiModel    = new PegawaiModel();
        $this->divisiModel     = new DivisiModel();
    }

    // ── Daftar pegawai untuk setup KPI ──────────────────────
    public function index(): string
    {
        $check = $this->checkMenuAccess('penilaian');
        if ($check !== true) return $check;
        
        $pegawaiList = $this->pegawaiModel->getAllWithDivisi();
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

        return view('layouts/main', [
            'title'   => 'KPI Per Pegawai — ' . $pegawai['nama'],
            'content' => view('kpi_pegawai/_form', [
                'pegawai'        => $pegawai,
                'assigned'       => $assigned,
                'assignedIds'    => $assignedIds,
                'assignedGrouped'=> $assignedGrouped,
                'poolGrouped'    => $poolGrouped,
                'totalBobot'     => $totalBobot,
            ]),
        ]);
    }

    // ── Tambah satu KPI ke Pegawai ───────────────────────────
    public function add(int $pegawaiId)
    {
        $pegawai = $this->pegawaiModel->find($pegawaiId);
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
        $bobots  = $this->request->getPost('bobot')  ?? [];
        $targets = $this->request->getPost('target') ?? []; 
        $ids     = $this->request->getPost('kp_id')  ?? [];

        // Validasi total bobot = 100%
        $total = array_sum($bobots);
        
        if ($total > 1.05) {
            $total = $total / 100;
            foreach($bobots as $key => $val) {
                $bobots[$key] = $val / 100;
            }
        }

        if (round($total, 2) != 1.00) {
            return redirect()->back()
                             ->with('error',
                                 'Total bobot harus = 100%. '
                                 . 'Saat ini: ' . round($total * 100, 2) . '%');
        }

        foreach ($ids as $i => $kpId) {
            $this->kpiPegawaiModel->update((int)$kpId, [
                'bobot'  => (float)($bobots[$i] ?? 0),
                'target' => (float)($targets[$i] ?? 100.00), 
            ]);
        }

        return redirect()->to(base_url("kpi-pegawai/edit/$pegawaiId"))
                         ->with('success', 'Konfigurasi bobot dan target KPI berhasil disimpan.');
    }

    // ── Hapus KPI dari Pegawai ───────────────────────────────
    public function delete(int $id)
    {
        $row = $this->kpiPegawaiModel->find($id);
        $this->kpiPegawaiModel->delete($id);

        return redirect()->to(base_url("kpi-pegawai/edit/{$row['pegawai_id']}"))
                         ->with('success', 'KPI berhasil dihapus.');
    }

    // ── Copy KPI dari pegawai lain (same jabatan) ────────────
    // Mengubah nama method menjadi copy() agar cocok dengan route form action `kpi-pegawai/copy/...`
    public function copy(int $pegawaiId)
    {
        $sourceId = (int)$this->request->getPost('source_pegawai_id');
        $pegawai  = $this->pegawaiModel->find($pegawaiId);

        if (!$sourceId || $sourceId === $pegawaiId) {
            return redirect()->back()
                             ->with('error', 'Pilih pegawai sumber yang valid.');
        }

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