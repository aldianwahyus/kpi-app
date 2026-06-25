<?php
namespace App\Controllers;

use App\Models\KpiMasterModel;

class MasterController extends BaseController
{
    protected KpiMasterModel $kpiModel;

    public function __construct()
    {
        $this->kpiModel = new KpiMasterModel();
    }

    // ── Daftar KPI ──────────────────────────────────────────
    public function kpi(): string
    {
        $grouped     = $this->kpiModel->getGroupedByPerspektif();
        $totalBobot  = $this->kpiModel->getTotalBobot();

        return view('layouts/main', [
            'title'   => 'Master KPI',
            'content' => view('master/kpi/_content', [
                'grouped'    => $grouped,
                'totalBobot' => $totalBobot,
            ]),
        ]);
    }

    // ── Form Tambah ──────────────────────────────────────────
    public function kpiCreate(): string
    {
        return view('layouts/main', [
            'title'   => 'Tambah KPI',
            'content' => view('master/kpi/_form', [
                'kpi'    => null,
                'action' => base_url('master/kpi/store'),
            ]),
        ]);
    }

    // ── Simpan Tambah ────────────────────────────────────────
    public function kpiStore()
    {
        $rules = [
            'nama_kpi'  => 'required|min_length[3]',
            'kode'      => 'required|min_length[2]',
            'perspektif'=> 'required',
            'satuan'    => 'required',
            'bobot'     => 'required|decimal',
            'polarity'  => 'required|in_list[max,min]',
            'perubahan_polarity' => 'required|in_list[pos,neg]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                             ->withInput()
                             ->with('errors', $this->validator->getErrors());
        }

        $kode = strtoupper($this->request->getPost('kode'));
        if ($this->kpiModel->isKodeExists($kode)) {
            return redirect()->back()
                             ->withInput()
                             ->with('error', "Kode KPI '$kode' sudah digunakan.");
        }

        $this->kpiModel->insert([
            'perspektif'          => $this->request->getPost('perspektif'),
            'nama_kpi'            => $this->request->getPost('nama_kpi'),
            'kode'                => $kode,
            'satuan'              => $this->request->getPost('satuan'),
            'bobot'               => (float) $this->request->getPost('bobot'),
            'total_bobot_perspektif' => $this->request->getPost('total_bobot_perspektif') ?: null,
            'polarity'            => $this->request->getPost('polarity'),
            'perubahan_polarity'  => $this->request->getPost('perubahan_polarity'),
            'is_kualitatif'       => $this->request->getPost('is_kualitatif') ? 1 : 0,
            'rubrik_sheet'        => $this->request->getPost('rubrik_sheet') ?: null,
            'is_active'           => 1,
            'urutan'              => (int) $this->request->getPost('urutan') ?: 99,
        ]);

        return redirect()->to(base_url('master/kpi'))
                         ->with('success', 'KPI berhasil ditambahkan.');
    }

    // ── Form Edit ────────────────────────────────────────────
    public function kpiEdit(int $id): string
    {
        $kpi = $this->kpiModel->find($id);
        if (!$kpi) {
            return redirect()->to(base_url('master/kpi'))
                             ->with('error', 'KPI tidak ditemukan.');
        }

        return view('layouts/main', [
            'title'   => 'Edit KPI',
            'content' => view('master/kpi/_form', [
                'kpi'    => $kpi,
                'action' => base_url("master/kpi/update/$id"),
            ]),
        ]);
    }

    // ── Simpan Edit ──────────────────────────────────────────
    public function kpiUpdate(int $id)
    {
        $rules = [
            'nama_kpi'  => 'required|min_length[3]',
            'kode'      => 'required|min_length[2]',
            'perspektif'=> 'required',
            'satuan'    => 'required',
            'bobot'     => 'required|decimal',
            'polarity'  => 'required|in_list[max,min]',
            'perubahan_polarity' => 'required|in_list[pos,neg]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                             ->withInput()
                             ->with('errors', $this->validator->getErrors());
        }

        $kode = strtoupper($this->request->getPost('kode'));
        if ($this->kpiModel->isKodeExists($kode, $id)) {
            return redirect()->back()
                             ->withInput()
                             ->with('error', "Kode KPI '$kode' sudah digunakan.");
        }

        $this->kpiModel->update($id, [
            'perspektif'          => $this->request->getPost('perspektif'),
            'nama_kpi'            => $this->request->getPost('nama_kpi'),
            'kode'                => $kode,
            'satuan'              => $this->request->getPost('satuan'),
            'bobot'               => (float) $this->request->getPost('bobot'),
            'total_bobot_perspektif' => $this->request->getPost('total_bobot_perspektif') ?: null,
            'polarity'            => $this->request->getPost('polarity'),
            'perubahan_polarity'  => $this->request->getPost('perubahan_polarity'),
            'is_kualitatif'       => $this->request->getPost('is_kualitatif') ? 1 : 0,
            'rubrik_sheet'        => $this->request->getPost('rubrik_sheet') ?: null,
            'urutan'              => (int) $this->request->getPost('urutan') ?: 99,
        ]);

        return redirect()->to(base_url('master/kpi'))
                         ->with('success', 'KPI berhasil diupdate.');
    }

    // ── Toggle Aktif/Nonaktif ────────────────────────────────
    public function kpiToggle(int $id)
    {
        $kpi = $this->kpiModel->find($id);
        if ($kpi) {
            $this->kpiModel->update($id, [
                'is_active' => $kpi['is_active'] ? 0 : 1,
            ]);
        }
        return redirect()->to(base_url('master/kpi'))
                         ->with('success', 'Status KPI diubah.');
    }

    // ── Hapus ────────────────────────────────────────────────
    public function kpiDelete(int $id)
    {
        $this->kpiModel->delete($id);
        return redirect()->to(base_url('master/kpi'))
                         ->with('success', 'KPI berhasil dihapus.');
    }
    // ── Halaman Kelola KPI per Divisi ────────────────────────
    public function kpiDivisi(): string
    {
        $divisiModel   = new \App\Models\DivisiModel();
        $kpiDivisiModel= new \App\Models\KpiDivisiModel();

        $divisiList = $divisiModel->getActive();

        // Hitung jumlah KPI dan total bobot per divisi
        $summary = [];
        foreach ($divisiList as $div) {
            $summary[$div['id']] = [
                'jumlah_kpi'  => count($kpiDivisiModel->getByDivisi($div['id'])),
                'total_bobot' => $kpiDivisiModel->getTotalBobot($div['id']),
            ];
        }

        return view('layouts/main', [
            'title'   => 'KPI per Divisi',
            'content' => view('master/kpi_divisi/_list', [
                'divisiList' => $divisiList,
                'summary'    => $summary,
            ]),
        ]);
    }

    // ── Form Assign KPI ke Divisi ────────────────────────────
    public function kpiDivisiEdit(int $divisiId): string
    {
        $divisiModel    = new \App\Models\DivisiModel();
        $kpiDivisiModel = new \App\Models\KpiDivisiModel();
        $kpiUnitModel   = new \App\Models\KpiUnitModel();

        $divisi         = $divisiModel->find($divisiId);

        // ← Kunci perubahan: ambil KPI dari kpi_unit sesuai direktorat divisi
        $kpiPool        = $kpiUnitModel->getByDirektorat($divisi['direktorat_id']);
        $assigned       = $kpiDivisiModel->getByDivisi($divisiId);
        $assignedIds    = $kpiDivisiModel->getAssignedKpiIds($divisiId);
        $totalBobot     = $kpiDivisiModel->getTotalBobot($divisiId);

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

        // Ambil nama direktorat
        $direktoratModel = new \App\Models\DirektoratModel();
        $direktorat      = $direktoratModel->find($divisi['direktorat_id']);

        return view('layouts/main', [
            'title'   => 'KPI Divisi — ' . $divisi['nama'],
            'content' => view('master/kpi_divisi/_form', [
                'divisi'          => $divisi,
                'direktorat'      => $direktorat,
                'poolGrouped'     => $poolGrouped,      // ← KPI dari direktorat
                'assigned'        => $assigned,
                'assignedIds'     => $assignedIds,
                'assignedGrouped' => $assignedGrouped,
                'totalBobot'      => $totalBobot,
            ]),
        ]);
    }

    // ── Simpan Assign KPI ke Divisi ──────────────────────────
    public function kpiDivisiStore(int $divisiId)
    {
        $kpiDivisiModel = new \App\Models\KpiDivisiModel();

        $kpiIds  = $this->request->getPost('kpi_id')  ?? [];
        $bobots  = $this->request->getPost('bobot')   ?? [];
        $urutans = $this->request->getPost('urutan')  ?? [];

        // Validasi total bobot harus = 1.00
        $totalBobot = array_sum($bobots);
        if (round($totalBobot, 2) != 1.00) {
            return redirect()->back()
                            ->withInput()
                            ->with('error',
                                'Total bobot harus = 100%. '
                                . 'Saat ini: ' . round($totalBobot * 100, 2) . '%');
        }

        // Hapus semua assignment lama lalu insert baru
        $kpiDivisiModel->deleteByDivisi($divisiId);

        $now = date('Y-m-d H:i:s');
        foreach ($kpiIds as $i => $kpiId) {
            if (!$kpiId) continue;
            $kpiDivisiModel->insert([
                'divisi_id'  => $divisiId,
                'kpi_id'     => (int)$kpiId,
                'bobot'      => (float)($bobots[$i] ?? 0),
                'urutan'     => (int)($urutans[$i] ?? $i + 1),
                'is_active'  => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return redirect()->to(base_url('master/kpi-divisi'))
                        ->with('success', 'KPI Divisi berhasil disimpan.');
    }

    // ── Hapus satu KPI dari Divisi ───────────────────────────
    public function kpiDivisiDelete(int $id)
    {
        $kpiDivisiModel = new \App\Models\KpiDivisiModel();
        $row = $kpiDivisiModel->find($id);
        $kpiDivisiModel->delete($id);

        return redirect()->to(base_url("master/kpi-divisi/edit/{$row['divisi_id']}"))
                        ->with('success', 'KPI berhasil dihapus dari divisi.');
    }
    // ── Tambah satu KPI ke Divisi (dari kolom kanan) ─────────
    public function kpiDivisiAdd(int $divisiId)
    {
        $kpiDivisiModel = new \App\Models\KpiDivisiModel();

        $kpiId = (int)$this->request->getPost('kpi_id');

        if ($kpiDivisiModel->isAssigned($divisiId, $kpiId)) {
            return redirect()->back()
                            ->with('error', 'KPI sudah ada di divisi ini.');
        }

        $kpiDivisiModel->insert([
            'divisi_id' => $divisiId,
            'kpi_id'    => $kpiId,
            'bobot'     => (float)$this->request->getPost('bobot') ?: 0.05,
            'urutan'    => (int)$this->request->getPost('urutan') ?: 99,
            'is_active' => 1,
        ]);

        return redirect()->back()
                        ->with('success', 'KPI berhasil ditambahkan ke divisi.');
    }

    // ══ DIREKTORAT ══════════════════════════════════════════
    public function direktorat(): string
    {
        $direktoratModel = new \App\Models\DirektoratModel();
        $kpiUnitModel    = new \App\Models\KpiUnitModel();

        $list = $direktoratModel->getActive();
        $summary = [];
        foreach ($list as $d) {
            $summary[$d['id']] = [
                'total_kpi'   => count($kpiUnitModel->getByDirektorat($d['id'])),
                'total_bobot' => $kpiUnitModel->getTotalBobot($d['id']),
            ];
        }

        return view('layouts/main', [
            'title'   => 'Master Direktorat',
            'content' => view('master/direktorat/_content', [
                'list'    => $list,
                'summary' => $summary,
            ]),
        ]);
    }

    public function direktoratCreate(): string
    {
        return view('layouts/main', [
            'title'   => 'Tambah Direktorat',
            'content' => view('master/direktorat/_form', [
                'dir'    => null,
                'action' => base_url('master/direktorat/store'),
            ]),
        ]);
    }

    public function direktoratStore()
    {
        if (!$this->validate([
            'kode' => 'required',
            'nama' => 'required',
        ])) {
            return redirect()->back()->withInput()
                            ->with('errors', $this->validator->getErrors());
        }

        $m = new \App\Models\DirektoratModel();
        $m->insert([
            'kode'      => strtoupper($this->request->getPost('kode')),
            'nama'      => $this->request->getPost('nama'),
            'singkatan' => $this->request->getPost('singkatan'),
            'deskripsi' => $this->request->getPost('deskripsi'),
            'is_active' => 1,
        ]);

        return redirect()->to(base_url('master/direktorat'))
                        ->with('success', 'Direktorat berhasil ditambahkan.');
    }

    public function direktoratEdit(int $id): string
    {
        $m   = new \App\Models\DirektoratModel();
        $dir = $m->find($id);

        return view('layouts/main', [
            'title'   => 'Edit Direktorat',
            'content' => view('master/direktorat/_form', [
                'dir'    => $dir,
                'action' => base_url("master/direktorat/update/$id"),
            ]),
        ]);
    }

    public function direktoratUpdate(int $id)
    {
        $m = new \App\Models\DirektoratModel();
        $m->update($id, [
            'kode'      => strtoupper($this->request->getPost('kode')),
            'nama'      => $this->request->getPost('nama'),
            'singkatan' => $this->request->getPost('singkatan'),
            'deskripsi' => $this->request->getPost('deskripsi'),
        ]);

        return redirect()->to(base_url('master/direktorat'))
                        ->with('success', 'Direktorat berhasil diupdate.');
    }

    // ══ KPI UNIT per DIREKTORAT ══════════════════════════════
    public function kpiUnit(int $direktoratId): string
    {
        $direktoratModel = new \App\Models\DirektoratModel();
        $kpiUnitModel    = new \App\Models\KpiUnitModel();

        $direktorat = $direktoratModel->find($direktoratId);
        $grouped    = $kpiUnitModel->getGroupedPerspektif($direktoratId);
        // $totalBobot = $kpiUnitModel->getTotalBobot($direktoratId);

        return view('layouts/main', [
            'title'   => 'KPI Unit — ' . $direktorat['nama'],
            'content' => view('master/kpi_unit/_content', [
                'direktorat' => $direktorat,
                'grouped'    => $grouped,
                // 'totalBobot' => $totalBobot,
            ]),
        ]);
    }

    public function kpiUnitCreate(int $direktoratId): string
    {
        $direktoratModel = new \App\Models\DirektoratModel();
        return view('layouts/main', [
            'title'   => 'Tambah KPI Unit',
            'content' => view('master/kpi_unit/_form', [
                'direktorat' => $direktoratModel->find($direktoratId),
                'kpi'        => null,
                'action'     => base_url("master/kpi-unit/{$direktoratId}/store"),
            ]),
        ]);
    }

    public function kpiUnitStore(int $direktoratId)
    {
        // ← Hapus echo "test"
        if (!$this->validate([
            'nama_kpi'   => 'required',
            'kode'       => 'required',
            'perspektif' => 'required',
            'satuan'     => 'required',
            // ← Hapus validasi bobot
        ])) {
            return redirect()->back()->withInput()
                            ->with('errors', $this->validator->getErrors());
        }

        $m = new \App\Models\KpiUnitModel();

        // Cek kode duplikat
        $kode = strtoupper($this->request->getPost('kode'));
        if ($m->where('kode', $kode)->countAllResults() > 0) {
            return redirect()->back()->withInput()
                            ->with('error', "Kode '$kode' sudah digunakan.");
        }

        $m->insert([
            'direktorat_id'      => $direktoratId,
            'perspektif'         => $this->request->getPost('perspektif'),
            'nama_kpi'           => $this->request->getPost('nama_kpi'),
            'kode'               => $kode,
            'satuan'             => $this->request->getPost('satuan'),
            'bobot'              => 0,
            'polarity'           => $this->request->getPost('polarity') ?? 'max',
            'perubahan_polarity' => $this->request->getPost('perubahan_polarity') ?? 'pos',
            'urutan'             => (int)$this->request->getPost('urutan') ?: 99,
            'is_active'          => 1,
        ]);

        return redirect()->to(base_url("master/kpi-unit/$direktoratId"))
                        ->with('success', 'KPI Unit berhasil ditambahkan.');
    }

    public function kpiUnitEdit(int $id): string
    {
        $m   = new \App\Models\KpiUnitModel();
        $kpi = $m->find($id);
        $direktoratModel = new \App\Models\DirektoratModel();

        return view('layouts/main', [
            'title'   => 'Edit KPI Unit',
            'content' => view('master/kpi_unit/_form', [
                'direktorat' => $direktoratModel->find($kpi['direktorat_id']),
                'kpi'        => $kpi,
                'action'     => base_url("master/kpi-unit/update/$id"),
            ]),
        ]);
    }

    public function kpiUnitUpdate(int $id)
    {
        $m   = new \App\Models\KpiUnitModel();
        $kpi = $m->find($id);

        $m->update($id, [
            'perspektif'         => $this->request->getPost('perspektif'),
            'nama_kpi'           => $this->request->getPost('nama_kpi'),
            'kode'               => strtoupper($this->request->getPost('kode')),
            'satuan'             => $this->request->getPost('satuan'),
            'bobot'              => 0,
            'polarity'           => $this->request->getPost('polarity'),
            'perubahan_polarity' => $this->request->getPost('perubahan_polarity'),
            'urutan'             => (int)$this->request->getPost('urutan') ?: 99,
        ]);

        return redirect()->to(base_url("master/kpi-unit/{$kpi['direktorat_id']}"))
                        ->with('success', 'KPI Unit berhasil diupdate.');
    }

    public function kpiUnitDelete(int $id)
    {
        $m   = new \App\Models\KpiUnitModel();
        $kpi = $m->find($id);
        $m->delete($id);

        return redirect()->to(base_url("master/kpi-unit/{$kpi['direktorat_id']}"))
                        ->with('success', 'KPI Unit berhasil dihapus.');
    }

    // ══ DATA UNIT KERJA ══════════════════════════════════════
    public function unitKerja(): string
    {
        $divisiModel     = new \App\Models\DivisiModel();
        $direktoratModel = new \App\Models\DirektoratModel();

        return view('layouts/main', [
            'title'   => 'Data Unit Kerja',
            'content' => view('master/unit_kerja/_content', [
                'grouped'     => $divisiModel->getGroupedByDirektorat(),
                'direktorats' => $direktoratModel->getActive(),
            ]),
        ]);
    }

    public function unitKerjaCreate(): string
    {
        $direktoratModel = new \App\Models\DirektoratModel();
        return view('layouts/main', [
            'title'   => 'Tambah Unit Kerja',
            'content' => view('master/unit_kerja/_form', [
                'divisi'      => null,
                'action'      => base_url('master/unit-kerja/store'),
                'direktorats' => $direktoratModel->getDropdown(),
            ]),
        ]);
    }

    public function unitKerjaStore()
    {
        if (!$this->validate([
            'kode'          => 'required',
            'nama'          => 'required',
            'direktorat_id' => 'required',
        ])) {
            return redirect()->back()->withInput()
                            ->with('errors', $this->validator->getErrors());
        }

        $m = new \App\Models\DivisiModel();
        $m->insert([
            'kode'          => strtoupper($this->request->getPost('kode')),
            'nama'          => $this->request->getPost('nama'),
            'direktorat_id' => $this->request->getPost('direktorat_id'),
            'deskripsi'     => $this->request->getPost('deskripsi'),
            'kepala_divisi' => $this->request->getPost('kepala_divisi'),
            'is_active'     => 1,
        ]);

        return redirect()->to(base_url('master/unit-kerja'))
                        ->with('success', 'Unit kerja berhasil ditambahkan.');
    }

    public function unitKerjaEdit(int $id): string
    {
        $m = new \App\Models\DivisiModel();
        $direktoratModel = new \App\Models\DirektoratModel();

        return view('layouts/main', [
            'title'   => 'Edit Unit Kerja',
            'content' => view('master/unit_kerja/_form', [
                'divisi'      => $m->find($id),
                'action'      => base_url("master/unit-kerja/update/$id"),
                'direktorats' => $direktoratModel->getDropdown(),
            ]),
        ]);
    }

    public function unitKerjaUpdate(int $id)
    {
        $m = new \App\Models\DivisiModel();
        $m->update($id, [
            'kode'          => strtoupper($this->request->getPost('kode')),
            'nama'          => $this->request->getPost('nama'),
            'direktorat_id' => $this->request->getPost('direktorat_id'),
            'deskripsi'     => $this->request->getPost('deskripsi'),
            'kepala_divisi' => $this->request->getPost('kepala_divisi'),
        ]);

        return redirect()->to(base_url('master/unit-kerja'))
                        ->with('success', 'Unit kerja berhasil diupdate.');
    }

    public function unitKerjaToggle(int $id)
    {
        $m = new \App\Models\DivisiModel();
        $d = $m->find($id);
        $m->update($id, ['is_active' => $d['is_active'] ? 0 : 1]);

        return redirect()->to(base_url('master/unit-kerja'))
                        ->with('success', 'Status unit kerja diubah.');
    }

    public function unitKerjaDelete(int $id)
    {
        $m = new \App\Models\DivisiModel();
        $m->delete($id);

        return redirect()->to(base_url('master/unit-kerja'))
                        ->with('success', 'Unit kerja berhasil dihapus.');
    }

    public function direktoratDelete(int $id)
    {
        $m = new \App\Models\DirektoratModel();

        // Cek apakah masih ada divisi yang menggunakan direktorat ini
        $divisiCount = (new \App\Models\DivisiModel())
            ->where('direktorat_id', $id)
            ->countAllResults();

        if ($divisiCount > 0) {
            return redirect()->to(base_url('master/direktorat'))
                            ->with('error',
                                "Direktorat tidak bisa dihapus karena masih memiliki
                                <strong>$divisiCount unit kerja</strong>.
                                Hapus atau pindahkan unit kerja terlebih dahulu.");
        }

        // Cek apakah masih ada KPI Unit yang menggunakan direktorat ini
        $kpiCount = (new \App\Models\KpiUnitModel())
            ->where('direktorat_id', $id)
            ->countAllResults();

        if ($kpiCount > 0) {
            return redirect()->to(base_url('master/direktorat'))
                            ->with('error',
                                "Direktorat tidak bisa dihapus karena masih memiliki
                                <strong>$kpiCount KPI Unit</strong>.
                                Hapus KPI Unit terlebih dahulu.");
        }

        $m->delete($id);

        return redirect()->to(base_url('master/direktorat'))
                        ->with('success', 'Direktorat berhasil dihapus.');
    }
}