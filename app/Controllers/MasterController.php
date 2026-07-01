<?php
namespace App\Controllers;

use App\Models\KpiMasterModel;

class MasterController extends BaseController
{
    protected KpiMasterModel $kpiModel;

    public function __construct()
    {
        $this->kpiModel = new KpiMasterModel();

        // Seluruh operasi Master Data (KPI, Direktorat, Unit Kerja, Periode,
        // KPI Divisi, KPI Unit) hanya boleh diakses oleh Administrator.
        // Pengecekan dilakukan di konstruktor karena SEMUA method di
        // controller ini berbagi persyaratan akses yang sama persis.
        $role = session()->get('role');
        if ($role !== 'admin' && $role !== 'hr') {
            // Tidak bisa memanggil $this->forbidden() sebelum DI siap,
            // gunakan session flash + redirect langsung.
            session()->setFlashdata('error', 'Anda tidak memiliki akses ke halaman Master Data.');
            header('Location: ' . base_url('dashboard'));
            exit;
        }
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

        if (!$row) {
            return redirect()->to(base_url('master/kpi-divisi'))
                            ->with('error', 'Data KPI Divisi tidak ditemukan atau sudah dihapus.');
        }

        $kpiDivisiModel->delete($id);

        return redirect()->to(base_url("master/kpi-divisi/edit/{$row['divisi_id']}"))
                        ->with('success', 'KPI berhasil dihapus dari divisi.');
    }
    // ── Tambah satu KPI ke Divisi (dari kolom kanan) ─────────
    public function kpiDivisiAdd(int $divisiId)
    {
        $kpiDivisiModel = new \App\Models\KpiDivisiModel();

        $kpiId = (int)$this->request->getPost('kpi_id');

        if ($kpiId <= 0) {
            return redirect()->back()
                            ->with('error', 'KPI yang dipilih tidak valid.');
        }

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

        if (!$dir) {
            return redirect()->to(base_url('master/direktorat'))
                            ->with('error', 'Direktorat tidak ditemukan.');
        }

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

        if (!$direktorat) {
            return redirect()->to(base_url('master/direktorat'))
                            ->with('error', 'Direktorat tidak ditemukan.');
        }

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
        $direktorat      = $direktoratModel->find($direktoratId);

        if (!$direktorat) {
            return redirect()->to(base_url('master/direktorat'))
                            ->with('error', 'Direktorat tidak ditemukan.');
        }

        return view('layouts/main', [
            'title'   => 'Tambah KPI Unit',
            'content' => view('master/kpi_unit/_form', [
                'direktorat' => $direktorat,
                'kpi'        => null,
                'action'     => base_url("master/kpi-unit/{$direktoratId}/store"),
            ]),
        ]);
    }

    // ── AJAX: Generate kode KPI otomatis ─────────────────────
    // Format: [SINGKATAN_DIR]-[PREFIX_PERSPEKTIF][NOMOR_URUT]
    // Contoh: MR-F1, MR-IP3, DU-LG2
    public function kpiUnitGenerateKode()
    {
        $direktoratId = (int)$this->request->getGet('direktorat_id');
        $perspektif   = $this->request->getGet('perspektif');

        if (!$direktoratId || !$perspektif) {
            return $this->response->setJSON(['kode' => '', 'error' => 'Parameter tidak lengkap.']);
        }

        $direktoratModel = new \App\Models\DirektoratModel();
        $kpiUnitModel    = new \App\Models\KpiUnitModel();

        $direktorat = $direktoratModel->find($direktoratId);
        if (!$direktorat) {
            return $this->response->setJSON(['kode' => '', 'error' => 'Direktorat tidak ditemukan.']);
        }

        // Singkatan direktorat — ambil dari kolom singkatan, fallback ke
        // kode direktorat jika singkatan kosong atau masih format panjang
        $singkatan = strtoupper(trim($direktorat['singkatan'] ?? ''));
        if (empty($singkatan) || strlen($singkatan) > 8) {
            // Fallback: derive dari nama direktorat (ambil huruf pertama tiap kata)
            $words = explode(' ', preg_replace('/\b(dan|dan|the|of|dan|&)\b/i', '', $direktorat['nama']));
            $singkatan = strtoupper(implode('', array_map(fn($w) => substr(trim($w), 0, 1), array_filter($words))));
            $singkatan = substr($singkatan, 0, 4);
        }

        // Peta perspektif ke prefix huruf
        $prefixMap = [
            'Financial'         => 'F',
            'Customer'          => 'C',
            'Internal Process'  => 'IP',
            'Learning & Growth' => 'LG',
        ];
        $prefix = $prefixMap[$perspektif] ?? strtoupper(substr($perspektif, 0, 2));

        // Temukan nomor urut berikutnya yang belum pernah dipakai
        // untuk kombinasi singkatan + perspektif ini
        $pattern = $singkatan . '-' . $prefix;
        $existing = $kpiUnitModel->db->table('kpi_unit')
            ->select('kode')
            ->like('kode', $pattern, 'after')
            ->where('direktorat_id', $direktoratId)
            ->get()->getResultArray();

        $usedNumbers = [];
        foreach ($existing as $row) {
            if (preg_match('/^' . preg_quote($pattern, '/') . '(\d+)$/', $row['kode'], $m)) {
                $usedNumbers[] = (int)$m[1];
            }
        }

        $nextNum = 1;
        while (in_array($nextNum, $usedNumbers)) {
            $nextNum++;
        }

        $kode = $pattern . $nextNum;

        return $this->response->setJSON([
            'kode'     => $kode,
            'preview'  => "Kode yang akan digunakan: <strong>$kode</strong>",
            'csrf_hash'=> csrf_hash(),
        ]);
    }

    public function kpiUnitStore(int $direktoratId)
    {
        if (!$this->validate([
            'nama_kpi'   => 'required',
            'perspektif' => 'required',
            'satuan'     => 'required',
        ])) {
            return redirect()->back()->withInput()
                            ->with('errors', $this->validator->getErrors());
        }

        $m           = new \App\Models\KpiUnitModel();
        $perspektif  = $this->request->getPost('perspektif');
        $kodeForm    = strtoupper(trim($this->request->getPost('kode') ?? ''));

        // Jika kode dari form kosong atau tidak valid (JS dimatikan),
        // generate ulang di sisi server menggunakan logika yang sama
        // dengan kpiUnitGenerateKode() untuk konsistensi.
        if (empty($kodeForm)) {
            $direktoratModel = new \App\Models\DirektoratModel();
            $direktorat      = $direktoratModel->find($direktoratId);
            $singkatan       = strtoupper(trim($direktorat['singkatan'] ?? ''));
            if (empty($singkatan) || strlen($singkatan) > 8) {
                $words     = explode(' ', $direktorat['nama']);
                $singkatan = strtoupper(implode('', array_map(fn($w) => substr(trim($w), 0, 1), array_filter($words))));
                $singkatan = substr($singkatan, 0, 4);
            }
            $prefixMap  = ['Financial'=>'F','Customer'=>'C','Internal Process'=>'IP','Learning & Growth'=>'LG'];
            $prefix     = $prefixMap[$perspektif] ?? strtoupper(substr($perspektif, 0, 2));
            $pattern    = $singkatan . '-' . $prefix;
            $existing   = $m->db->table('kpi_unit')->select('kode')
                            ->like('kode', $pattern, 'after')
                            ->where('direktorat_id', $direktoratId)->get()->getResultArray();
            $usedNumbers = [];
            foreach ($existing as $row) {
                if (preg_match('/^' . preg_quote($pattern, '/') . '(\d+)$/', $row['kode'], $mx)) {
                    $usedNumbers[] = (int)$mx[1];
                }
            }
            $nextNum = 1;
            while (in_array($nextNum, $usedNumbers)) $nextNum++;
            $kodeForm = $pattern . $nextNum;
        }

        // Cek kode duplikat (keamanan: validasi tetap di server)
        if ($m->where('kode', $kodeForm)->countAllResults() > 0) {
            return redirect()->back()->withInput()
                            ->with('error', "Kode '$kodeForm' sudah digunakan. Silakan muat ulang form untuk mendapatkan kode baru.");
        }

        $m->insert([
            'direktorat_id'      => $direktoratId,
            'perspektif'         => $perspektif,
            'nama_kpi'           => $this->request->getPost('nama_kpi'),
            'kode'               => $kodeForm,
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

        if (!$kpi) {
            return redirect()->to(base_url('master/direktorat'))
                            ->with('error', 'KPI Unit tidak ditemukan.');
        }

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

        if (!$kpi) {
            return redirect()->to(base_url('master/direktorat'))
                            ->with('error', 'KPI Unit tidak ditemukan.');
        }

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

        if (!$kpi) {
            return redirect()->to(base_url('master/direktorat'))
                            ->with('error', 'KPI Unit tidak ditemukan atau sudah dihapus.');
        }

        $m->delete($id);

        return redirect()->to(base_url("master/kpi-unit/{$kpi['direktorat_id']}"))
                        ->with('success', 'KPI Unit berhasil dihapus.');
    }

    // ── Tampilkan form Import KPI Unit ────────────────────────
    public function kpiUnitImportForm(int $direktoratId): string
    {
        $direktoratModel = new \App\Models\DirektoratModel();
        $direktorat      = $direktoratModel->find($direktoratId);
        if (!$direktorat) {
            return redirect()->to(base_url('master/direktorat'))->with('error', 'Direktorat tidak ditemukan.');
        }

        return view('layouts/main', [
            'title'   => 'Import KPI Unit',
            'content' => view('master/kpi_unit/_import', ['direktorat' => $direktorat]),
        ]);
    }

    // ── Download template import KPI Unit ─────────────────────
    public function kpiUnitImportTemplate(int $direktoratId)
    {
        $direktoratModel = new \App\Models\DirektoratModel();
        $direktorat      = $direktoratModel->find($direktoratId);
        if (!$direktorat) {
            return redirect()->to(base_url('master/direktorat'))->with('error', 'Direktorat tidak ditemukan.');
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Import KPI Unit');

        $headers = [
            'A' => 'Perspektif *',
            'B' => 'Nama KPI *',
            'C' => 'Satuan *',
            'D' => 'Polarity (max/min) *',
            'E' => 'Perubahan Polarity (pos/neg) *',
            'F' => 'Urutan',
        ];
        foreach ($headers as $col => $h) {
            $sheet->setCellValue("{$col}1", $h);
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $sheet->getStyle("{$col}1")->getFont()->setBold(true);
        }

        // Baris contoh
        $contoh = [
            ['Financial', 'Rasio Laba Bersih', '%', 'max', 'pos', 1],
            ['Customer', 'Tingkat Kepuasan Nasabah', 'Skor', 'max', 'pos', 2],
            ['Internal Process', 'Penyelesaian SLA', '%', 'max', 'pos', 3],
            ['Learning & Growth', 'Jam Pelatihan Pegawai', 'Jam', 'max', 'pos', 4],
        ];
        foreach ($contoh as $i => $row) {
            foreach ($row as $j => $val) {
                $col = chr(ord('A') + $j);
                $sheet->setCellValue("{$col}" . ($i + 2), $val);
            }
        }

        // Instruksi di kolom H
        $sheet->setCellValue('H1', 'CATATAN:');
        $sheet->setCellValue('H2', 'Kode akan digenerate otomatis oleh sistem.');
        $sheet->setCellValue('H3', 'Perspektif valid: Financial | Customer | Internal Process | Learning & Growth');
        $sheet->setCellValue('H4', "Untuk direktorat: {$direktorat['nama']}");
        $sheet->getColumnDimension('H')->setWidth(60);

        $writer   = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'Template_Import_KPI_Unit_' . preg_replace('/[^a-zA-Z0-9]/', '_', $direktorat['kode']) . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

    // ── Proses Import KPI Unit dari Excel ─────────────────────
    public function kpiUnitImportProcess(int $direktoratId)
    {
        $direktoratModel = new \App\Models\DirektoratModel();
        $direktorat      = $direktoratModel->find($direktoratId);
        if (!$direktorat) {
            return redirect()->to(base_url('master/direktorat'))->with('error', 'Direktorat tidak ditemukan.');
        }

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
            return redirect()->back()->with('error', 'Gagal membaca file: ' . $e->getMessage());
        }

        $perspektifValid = ['Financial', 'Customer', 'Internal Process', 'Learning & Growth'];
        $prefixMap       = ['Financial'=>'F','Customer'=>'C','Internal Process'=>'IP','Learning & Growth'=>'LG'];

        $singkatan = strtoupper(trim($direktorat['singkatan'] ?? ''));
        if (empty($singkatan) || strlen($singkatan) > 8) {
            $words     = explode(' ', $direktorat['nama']);
            $singkatan = strtoupper(implode('', array_map(fn($w) => substr(trim($w), 0, 1), array_filter($words))));
            $singkatan = substr($singkatan, 0, 4);
        }

        $m         = new \App\Models\KpiUnitModel();
        $berhasil  = 0;
        $dilewati  = [];

        foreach ($rows as $i => $row) {
            if ($i === 1) continue; // header
            if (empty(trim($row['A'] ?? '')) && empty(trim($row['B'] ?? ''))) continue; // baris kosong

            $perspektif = trim($row['A'] ?? '');
            $namaKpi    = trim($row['B'] ?? '');
            $satuan     = trim($row['C'] ?? '');
            $polarity   = strtolower(trim($row['D'] ?? 'max'));
            $perubahan  = strtolower(trim($row['E'] ?? 'pos'));
            $urutan     = (int)($row['F'] ?? 99) ?: 99;

            // Validasi baris
            if (!in_array($perspektif, $perspektifValid)) {
                $dilewati[] = "Baris $i: Perspektif '$perspektif' tidak valid.";
                continue;
            }
            if (empty($namaKpi)) {
                $dilewati[] = "Baris $i: Nama KPI wajib diisi.";
                continue;
            }
            if (empty($satuan)) {
                $dilewati[] = "Baris $i: Satuan wajib diisi.";
                continue;
            }
            if (!in_array($polarity, ['max', 'min'])) {
                $dilewati[] = "Baris $i: Polarity harus 'max' atau 'min'.";
                continue;
            }
            if (!in_array($perubahan, ['pos', 'neg'])) {
                $dilewati[] = "Baris $i: Perubahan Polarity harus 'pos' atau 'neg'.";
                continue;
            }

            // Generate kode otomatis
            $prefix   = $prefixMap[$perspektif];
            $pattern  = $singkatan . '-' . $prefix;
            $existing = $m->db->table('kpi_unit')->select('kode')
                            ->like('kode', $pattern, 'after')
                            ->where('direktorat_id', $direktoratId)->get()->getResultArray();
            $usedNumbers = [];
            foreach ($existing as $r2) {
                if (preg_match('/^' . preg_quote($pattern, '/') . '(\d+)$/', $r2['kode'], $mx)) {
                    $usedNumbers[] = (int)$mx[1];
                }
            }
            $nextNum = 1;
            while (in_array($nextNum, $usedNumbers)) $nextNum++;
            $kode = $pattern . $nextNum;

            $m->insert([
                'direktorat_id'      => $direktoratId,
                'perspektif'         => $perspektif,
                'nama_kpi'           => $namaKpi,
                'kode'               => $kode,
                'satuan'             => $satuan,
                'bobot'              => 0,
                'polarity'           => $polarity,
                'perubahan_polarity' => $perubahan,
                'urutan'             => $urutan,
                'is_active'          => 1,
            ]);
            $berhasil++;
        }

        $pesan = "$berhasil KPI Unit berhasil diimpor ke direktorat {$direktorat['nama']}.";
        if (!empty($dilewati)) {
            $pesan .= ' ' . count($dilewati) . ' baris dilewati: ' . implode('; ', array_slice($dilewati, 0, 5));
        }

        return redirect()->to(base_url("master/kpi-unit/$direktoratId"))->with('success', $pesan);
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
        $divisi = $m->find($id);

        if (!$divisi) {
            return redirect()->to(base_url('master/unit-kerja'))
                            ->with('error', 'Unit kerja tidak ditemukan.');
        }

        $direktoratModel = new \App\Models\DirektoratModel();

        return view('layouts/main', [
            'title'   => 'Edit Unit Kerja',
            'content' => view('master/unit_kerja/_form', [
                'divisi'      => $divisi,
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

        if (!$d) {
            return redirect()->to(base_url('master/unit-kerja'))
                            ->with('error', 'Unit kerja tidak ditemukan.');
        }

        $m->update($id, ['is_active' => $d['is_active'] ? 0 : 1]);

        return redirect()->to(base_url('master/unit-kerja'))
                        ->with('success', 'Status unit kerja diubah.');
    }

    public function unitKerjaDelete(int $id)
    {
        $m = new \App\Models\DivisiModel();

        $pegawaiCount = (new \App\Models\PegawaiModel())
            ->where('divisi_id', $id)
            ->countAllResults();

        if ($pegawaiCount > 0) {
            return redirect()->to(base_url('master/unit-kerja'))
                            ->with('error',
                                "Unit kerja tidak bisa dihapus karena masih memiliki "
                                . "<strong>$pegawaiCount pegawai</strong>. "
                                . "Pindahkan pegawai ke unit kerja lain terlebih dahulu.");
        }

        $kpiDivisiCount = (new \App\Models\KpiDivisiModel())
            ->where('divisi_id', $id)
            ->countAllResults();

        if ($kpiDivisiCount > 0) {
            return redirect()->to(base_url('master/unit-kerja'))
                            ->with('error',
                                "Unit kerja tidak bisa dihapus karena masih memiliki "
                                . "<strong>$kpiDivisiCount KPI</strong> yang ditetapkan. "
                                . "Hapus assignment KPI Divisi terlebih dahulu.");
        }

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