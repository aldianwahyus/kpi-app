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

    /**
     * "Master KPI" (kpi*() di bawah) adalah fitur lama yang sudah tidak
     * ditautkan dari sidebar manapun (digantikan KPI per Direktorat/Divisi)
     * dan tidak punya baris di menu_list, sehingga tidak bisa diatur lewat
     * layar "Hak Akses Role". Karena tidak configurable, aksesnya tetap
     * dikunci admin/hr langsung di sini (bukan lewat checkMenuAccess, yang
     * akan selalu menolak non-admin karena tidak ada baris permission-nya).
     */
    private function requireAdminAtauHr()
    {
        $role = session()->get('role');
        if ($role !== 'admin' && $role !== 'hr') {
            return $this->forbidden('Anda tidak memiliki akses ke halaman Master KPI.');
        }
        return true;
    }

    /**
     * Aturan validasi & data tambahan untuk field yang bergantung pada
     * Polarity yang dipilih di form KPI Unit — dipusatkan di sini karena
     * dipakai identik oleh kpiUnitStore() maupun kpiUnitUpdate():
     *   - max/min      : field 'Perubahan Polarity' (pos/neg), skema lama.
     *   - precise      : 3 toleransi deviasi (%), harus menaik (Skor4 < Skor3 < Skor2).
     *   - special      : dropdown 'Sifat' (maximize/minimize).
     *   - tertimbang   : Target Indikator 2 (Rata-rata Harian), harus > 0.
     */
    private function buildKpiUnitPolarityRules(string $polarity): array
    {
        $rules    = [];
        $messages = [];

        if (in_array($polarity, ['max', 'min'], true)) {
            $rules['perubahan_polarity']    = 'required|in_list[pos,neg]';
            $messages['perubahan_polarity'] = ['required' => 'Perubahan polarity wajib dipilih.'];
        } elseif ($polarity === 'precise') {
            $rules['toleransi_skor4'] = 'required|numeric|greater_than[0]';
            $rules['toleransi_skor3'] = 'required|numeric|greater_than[0]';
            $rules['toleransi_skor2'] = 'required|numeric|greater_than[0]';
            $messages['toleransi_skor4'] = ['required' => 'Toleransi Skor 4 wajib diisi.'];
            $messages['toleransi_skor3'] = ['required' => 'Toleransi Skor 3 wajib diisi.'];
            $messages['toleransi_skor2'] = ['required' => 'Toleransi Skor 2 wajib diisi.'];
        } elseif ($polarity === 'special') {
            $rules['sifat_khusus']    = 'required|in_list[maximize,minimize]';
            $messages['sifat_khusus'] = ['required' => 'Sifat wajib dipilih.'];
        }
        // 'tertimbang' tidak butuh field tambahan di KPI Unit — Target
        // Indikator 1 memakai Target KPI yang sudah ada, dan Rata-rata
        // Harian (Indikator 2) dimasukkan langsung sebagai persentase saat
        // penginputan penilaian, bukan konfigurasi per-KPI.

        return [$rules, $messages];
    }

    /**
     * Validasi tambahan yang tidak bisa diekspresikan lewat rule bawaan
     * CodeIgniter (urutan menaik antar 3 toleransi Precise is Better).
     * Mengembalikan pesan error jika tidak valid, atau null jika valid/
     * tidak relevan (polarity selain 'precise').
     */
    private function validateToleransiPreciseAscending(string $polarity): ?string
    {
        if ($polarity !== 'precise') return null;

        $t4 = (float)$this->request->getPost('toleransi_skor4');
        $t3 = (float)$this->request->getPost('toleransi_skor3');
        $t2 = (float)$this->request->getPost('toleransi_skor2');

        if (!($t4 < $t3 && $t3 < $t2)) {
            return 'Toleransi harus menaik: Toleransi Skor 4 < Toleransi Skor 3 < Toleransi Skor 2.';
        }

        return null;
    }

    /**
     * Kumpulkan field polarity-dependent dari POST untuk disimpan —
     * hanya field yang relevan dengan polarity terpilih yang diisi nilai
     * asli dari POST, sisanya null (menghindari data basi tersimpan dari
     * polarity sebelumnya jika Admin mengganti-ganti pilihan).
     */
    private function collectKpiUnitPolarityData(string $polarity): array
    {
        return [
            // Perubahan Polarity tidak lagi diinput manual — field ini
            // hanyalah representasi lain dari Polarity itu sendiri (skema
            // lama: "Positif" = Maximize, "Negatif" = Minimize), jadi selalu
            // diturunkan langsung dari Polarity yang dipilih agar tidak
            // pernah terjadi kombinasi yang saling bertentangan (mis.
            // Polarity=Maximize tapi Perubahan=Negatif, yang akan membuat
            // rumus capaian di KpiCalculationService::hitungCapaian()
            // diam-diam terbalik ke arah minimize).
            'perubahan_polarity' => $polarity === 'min' ? 'neg' : 'pos',
            'toleransi_skor4' => $polarity === 'precise' ? $this->request->getPost('toleransi_skor4') : null,
            'toleransi_skor3' => $polarity === 'precise' ? $this->request->getPost('toleransi_skor3') : null,
            'toleransi_skor2' => $polarity === 'precise' ? $this->request->getPost('toleransi_skor2') : null,
            'sifat_khusus'    => $polarity === 'special' ? $this->request->getPost('sifat_khusus') : null,
        ];
    }

    // ── Daftar KPI ──────────────────────────────────────────
    public function kpi()
    {
        $check = $this->requireAdminAtauHr();
        if ($check !== true) return $check;

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
    public function kpiCreate()
    {
        $check = $this->requireAdminAtauHr();
        if ($check !== true) return $check;

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
        $check = $this->requireAdminAtauHr();
        if ($check !== true) return $check;

        // 1. Aturan Validasi (Ditambahkan rule is_unique[kpi.kode])
        $rules = [
            'nama_kpi'           => 'required|trim|min_length[3]',
            'kode'               => 'required|trim|regex_match[/^\S+$/]|min_length[2]|is_unique[kpi.kode]',
            'perspektif'         => 'required|trim',
            'satuan'             => 'required|trim',
            'bobot'              => 'required|decimal',
            'polarity'           => 'required|in_list[max,min]',
        ];

        // 2. Pesan Error Custom
        $messages = [
            'kode' => [
                'required'    => 'Kode KPI wajib diisi.',
                'regex_match' => 'Kode KPI tidak boleh mengandung spasi atau whitespace.',
                'min_length'  => 'Kode KPI minimal 2 karakter.',
                'is_unique'   => 'Kode KPI sudah digunakan, silakan gunakan kode lain.'
            ],
            'nama_kpi' => [
                'required'   => 'Nama KPI wajib diisi.',
                'min_length' => 'Nama KPI minimal 3 karakter.'
            ]
        ];

        // 3. Jalankan Validasi Form
        if (!$this->validate($rules, $messages)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $kode     = strtoupper(trim($this->request->getPost('kode')));
        $polarity = $this->request->getPost('polarity');

        // 4. Simpan Data dengan Sanitasi Input
        $this->kpiModel->insert([
            'perspektif'             => trim($this->request->getPost('perspektif')),
            'nama_kpi'               => trim($this->request->getPost('nama_kpi')),
            'kode'                   => $kode,
            'satuan'                 => trim($this->request->getPost('satuan')),
            'bobot'                  => (float) $this->request->getPost('bobot'),
            'total_bobot_perspektif' => $this->request->getPost('total_bobot_perspektif') ?: null,
            'polarity'               => $polarity,
            // Diturunkan dari Polarity, bukan diinput manual — lihat catatan
            // di collectKpiUnitPolarityData().
            'perubahan_polarity'     => $polarity === 'min' ? 'neg' : 'pos',
            'is_kualitatif'          => $this->request->getPost('is_kualitatif') ? 1 : 0,
            'rubrik_sheet'           => trim($this->request->getPost('rubrik_sheet') ?? '') ?: null,
            'is_active'              => 1,
            'urutan'                 => (int) $this->request->getPost('urutan') ?: 99,
        ]);

        return redirect()->to(base_url('master/kpi'))
            ->with('success', 'KPI berhasil ditambahkan.');
    }

    // ── Form Edit ────────────────────────────────────────────
    public function kpiEdit(int $id)
    {
        $check = $this->requireAdminAtauHr();
        if ($check !== true) return $check;

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
        $check = $this->requireAdminAtauHr();
        if ($check !== true) return $check;

        // 1. Pre-Sanitasi Data Input (Trim sebelum masuk engine validation)
        $namaKpi    = trim($this->request->getPost('nama_kpi') ?? '');
        $satuan     = trim($this->request->getPost('satuan') ?? '');
        $kode       = trim($this->request->getPost('kode') ?? '');
        $perspektif = trim($this->request->getPost('perspektif') ?? '');

        // Timpa global POST agar $this->validate() membaca data yang sudah bersih dari spasi liar
        $this->request->setGlobal('post', array_merge($this->request->getPost(), [
            'nama_kpi'   => $namaKpi,
            'satuan'     => $satuan,
            'kode'       => $kode,
            'perspektif' => $perspektif,
        ]));

        // 2. Aturan Validasi Form
        $rules = [
            'nama_kpi'           => 'required|min_length[3]',
            'kode'               => "required|regex_match[/^\S+$/]|min_length[2]|is_unique[kpi.kode,id,{$id}]",
            'perspektif'         => 'required',
            'satuan'             => 'required|min_length[1]',
            'bobot'              => 'required|decimal',
            'polarity'           => 'required|in_list[max,min]',
        ];

        // 3. Pesan Error Custom
        $messages = [
            'nama_kpi' => [
                'required'   => 'Nama KPI wajib diisi (tidak boleh kosong atau hanya berupa spasi).',
                'min_length' => 'Nama KPI minimal 3 karakter.'
            ],
            'satuan' => [
                'required'   => 'Satuan KPI wajib diisi (tidak boleh kosong atau hanya berupa spasi).',
                'min_length' => 'Satuan KPI minimal 1 karakter.'
            ],
            'kode' => [
                'required'    => 'Kode KPI wajib diisi.',
                'regex_match' => 'Kode KPI tidak boleh mengandung spasi atau whitespace.',
                'min_length'  => 'Kode KPI minimal 2 karakter.',
                'is_unique'   => 'Kode KPI sudah digunakan oleh data lain.'
            ],
            'perspektif' => [
                'required' => 'Perspektif wajib dipilih.'
            ],
            'bobot' => [
                'required' => 'Bobot KPI wajib diisi.',
                'decimal'  => 'Bobot harus berupa angka desimal.'
            ]
        ];

        // 4. Jalankan Validasi Form
        if (!$this->validate($rules, $messages)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $kodeUpper = strtoupper($kode);
        $polarity  = $this->request->getPost('polarity');

        // 5. Update Data (Menggunakan variabel yang sudah bersih)
        $this->kpiModel->update($id, [
            'perspektif'             => $perspektif,
            'nama_kpi'               => $namaKpi,
            'kode'                   => $kodeUpper,
            'satuan'                 => $satuan,
            'bobot'                  => (float) $this->request->getPost('bobot'),
            'total_bobot_perspektif' => $this->request->getPost('total_bobot_perspektif') ?: null,
            'polarity'               => $polarity,
            // Diturunkan dari Polarity, bukan diinput manual — lihat catatan
            // di collectKpiUnitPolarityData().
            'perubahan_polarity'     => $polarity === 'min' ? 'neg' : 'pos',
            'is_kualitatif'          => $this->request->getPost('is_kualitatif') ? 1 : 0,
            'rubrik_sheet'           => trim($this->request->getPost('rubrik_sheet') ?? '') ?: null,
            'urutan'                 => (int) $this->request->getPost('urutan') ?: 99,
        ]);

        return redirect()->to(base_url('master/kpi'))
            ->with('success', 'KPI berhasil diupdate.');
    }

    // ── Toggle Aktif/Nonaktif ────────────────────────────────
    public function kpiToggle(int $id)
    {
        $check = $this->requireAdminAtauHr();
        if ($check !== true) return $check;

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
        $check = $this->requireAdminAtauHr();
        if ($check !== true) return $check;

        $this->kpiModel->delete($id);
        return redirect()->to(base_url('master/kpi'))
            ->with('success', 'KPI berhasil dihapus.');
    }
    // ── Halaman Kelola KPI per Divisi ────────────────────────
    public function kpiDivisi()
    {
        $check = $this->checkMenuAccess('master_kpidivisi');
        if ($check !== true) return $check;

        $divisiModel   = new \App\Models\DivisiModel();
        $kpiDivisiModel = new \App\Models\KpiDivisiModel();

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
    public function kpiDivisiEdit(int $divisiId)
    {
        $check = $this->checkMenuAccess('master_kpidivisi');
        if ($check !== true) return $check;

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
        $check = $this->checkMenuEdit('master_kpidivisi');
        if ($check !== true) return $check;

        $kpiDivisiModel = new \App\Models\KpiDivisiModel();

        $kpiIds  = $this->request->getPost('kpi_id')  ?? [];
        $bobots  = $this->request->getPost('bobot')   ?? [];
        $urutans = $this->request->getPost('urutan')  ?? [];

        // Validasi total bobot harus = 1.00
        $totalBobot = array_sum($bobots);
        if (round($totalBobot, 2) != 1.00) {
            return redirect()->back()
                ->withInput()
                ->with(
                    'error',
                    'Total bobot harus = 100%. '
                        . 'Saat ini: ' . round($totalBobot * 100, 2) . '%'
                );
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
        $check = $this->checkMenuEdit('master_kpidivisi');
        if ($check !== true) return $check;

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
        $check = $this->checkMenuEdit('master_kpidivisi');
        if ($check !== true) return $check;

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
    public function direktorat()
    {
        $check = $this->checkMenuAccess('master_direktorat');
        if ($check !== true) return $check;

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

    public function direktoratCreate()
    {
        $check = $this->checkMenuAccess('master_direktorat');
        if ($check !== true) return $check;

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
        $check = $this->checkMenuEdit('master_direktorat');
        if ($check !== true) return $check;

        // 1. Pre-Sanitasi Input Text (trim spasi di awal & akhir)
        $kode      = trim($this->request->getPost('kode') ?? '');
        $nama      = trim($this->request->getPost('nama') ?? '');
        $singkatan = trim($this->request->getPost('singkatan') ?? '');
        $deskripsi = trim($this->request->getPost('deskripsi') ?? '');

        // Overwrite payload global post agar $this->validate() membaca data yang sudah bersih
        $this->request->setGlobal('post', array_merge($this->request->getPost(), [
            'kode'      => $kode,
            'nama'      => $nama,
            'singkatan' => $singkatan,
            'deskripsi' => $deskripsi,
        ]));

        // 2. Aturan Validasi
        $rules = [
            'kode'      => 'required|regex_match[/^\S+$/]|is_unique[direktorat.kode]',
            'nama'      => 'required|min_length[3]',
            'singkatan' => 'permit_empty|min_length[2]',
        ];

        // 3. Pesan Error Custom
        $messages = [
            'kode' => [
                'required'    => 'Kode direktorat wajib diisi.',
                'regex_match' => 'Kode direktorat tidak boleh mengandung spasi atau whitespace.',
                'is_unique'   => 'Kode direktorat sudah digunakan, gunakan kode lain.'
            ],
            'nama' => [
                'required'   => 'Nama direktorat wajib diisi (tidak boleh kosong atau hanya berupa spasi).',
                'min_length' => 'Nama direktorat minimal 3 karakter.'
            ],
            'singkatan' => [
                'min_length' => 'Singkatan minimal 2 karakter.'
            ]
        ];

        // 4. Jalankan Validasi
        if (!$this->validate($rules, $messages)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // 5. Simpan ke Database
        $m = new \App\Models\DirektoratModel();
        $m->insert([
            'kode'      => strtoupper($kode),
            'nama'      => $nama,
            'singkatan' => $singkatan ?: null,
            'deskripsi' => $deskripsi ?: null,
            'is_active' => 1,
        ]);

        return redirect()->to(base_url('master/direktorat'))
            ->with('success', 'Direktorat berhasil ditambahkan.');
    }

    public function direktoratEdit(int $id)
    {
        $check = $this->checkMenuAccess('master_direktorat');
        if ($check !== true) return $check;

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
        $check = $this->checkMenuEdit('master_direktorat');
        if ($check !== true) return $check;

        // 1. Pre-Sanitasi Input Text (trim spasi di awal & akhir)
        $kode      = trim($this->request->getPost('kode') ?? '');
        $nama      = trim($this->request->getPost('nama') ?? '');
        $singkatan = trim($this->request->getPost('singkatan') ?? '');
        $deskripsi = trim($this->request->getPost('deskripsi') ?? '');

        // Overwrite payload global post agar $this->validate() membaca data yang sudah bersih
        $this->request->setGlobal('post', array_merge($this->request->getPost(), [
            'kode'      => $kode,
            'nama'      => $nama,
            'singkatan' => $singkatan,
            'deskripsi' => $deskripsi,
        ]));

        // 2. Aturan Validasi (Mengabaikan ID yang sedang di-update pada is_unique)
        $rules = [
            'kode'      => "required|regex_match[/^\S+$/]|is_unique[direktorat.kode,id,{$id}]",
            'nama'      => 'required|min_length[3]',
            'singkatan' => 'permit_empty|min_length[2]',
        ];

        // 3. Pesan Error Custom
        $messages = [
            'kode' => [
                'required'    => 'Kode direktorat wajib diisi.',
                'regex_match' => 'Kode direktorat tidak boleh mengandung spasi atau whitespace.',
                'is_unique'   => 'Kode direktorat sudah digunakan oleh data lain.'
            ],
            'nama' => [
                'required'   => 'Nama direktorat wajib diisi (tidak boleh kosong atau hanya berupa spasi).',
                'min_length' => 'Nama direktorat minimal 3 karakter.'
            ],
            'singkatan' => [
                'min_length' => 'Singkatan minimal 2 karakter.'
            ]
        ];

        // 4. Jalankan Validasi
        if (!$this->validate($rules, $messages)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // 5. Update Database
        $m = new \App\Models\DirektoratModel();
        $m->update($id, [
            'kode'      => strtoupper($kode),
            'nama'      => $nama,
            'singkatan' => $singkatan ?: null,
            'deskripsi' => $deskripsi ?: null,
        ]);

        return redirect()->to(base_url('master/direktorat'))
            ->with('success', 'Direktorat berhasil diupdate.');
    }

    // ══ KPI UNIT per DIREKTORAT ══════════════════════════════
    public function kpiUnit(int $direktoratId)
    {
        $check = $this->checkMenuAccess('master_direktorat');
        if ($check !== true) return $check;

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

    public function kpiUnitCreate(int $direktoratId)
    {
        $check = $this->checkMenuAccess('master_direktorat');
        if ($check !== true) return $check;

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
        $check = $this->checkMenuAccess('master_direktorat');
        if ($check !== true) return $check;

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
            'csrf_hash' => csrf_hash(),
        ]);
    }

    public function kpiUnitStore(int $direktoratId)
    {
        $check = $this->checkMenuEdit('master_direktorat');
        if ($check !== true) return $check;

        // 1. Pre-Sanitasi Input Text (trim spasi di awal & akhir)
        $namaKpi    = trim($this->request->getPost('nama_kpi') ?? '');
        $satuan     = trim($this->request->getPost('satuan') ?? '');
        $kodeInput  = trim($this->request->getPost('kode') ?? '');
        $perspektif = trim($this->request->getPost('perspektif') ?? '');

        // Overwrite payload POST agar $this->validate() membaca data yang sudah bersih
        $this->request->setGlobal('post', array_merge($this->request->getPost(), [
            'nama_kpi'   => $namaKpi,
            'satuan'     => $satuan,
            'kode'       => $kodeInput,
            'perspektif' => $perspektif,
        ]));

        $polarity = $this->request->getPost('polarity') ?? 'max';

        // 2. Aturan Validasi Form
        $rules = [
            'nama_kpi'   => 'required|min_length[3]',
            'perspektif' => 'required',
            'satuan'     => 'required|min_length[1]',
            'kode'       => 'permit_empty|regex_match[/^\S+$/]|is_unique[kpi_unit.kode]',
            'polarity'   => 'required|in_list[max,min,precise,special,tertimbang]',
        ];

        // 3. Pesan Error Custom
        $messages = [
            'nama_kpi' => [
                'required'   => 'Nama KPI wajib diisi (tidak boleh kosong atau hanya berupa spasi).',
                'min_length' => 'Nama KPI minimal 3 karakter.'
            ],
            'satuan' => [
                'required'   => 'Satuan KPI wajib diisi (tidak boleh kosong atau hanya berupa spasi).',
                'min_length' => 'Satuan KPI minimal 1 karakter.'
            ],
            'perspektif' => [
                'required' => 'Perspektif wajib dipilih.'
            ],
            'kode' => [
                'regex_match' => 'Kode KPI tidak boleh mengandung spasi atau whitespace.',
                'is_unique'   => 'Kode KPI sudah digunakan oleh data lain.'
            ],
            'polarity' => [
                'required' => 'Polarity wajib dipilih.'
            ],
        ];

        // Field tambahan bergantung pada Polarity yang dipilih (toleransi
        // Precise is Better / Sifat Special Scoring / Target Harian Tertimbang
        // / Perubahan Polarity lama untuk max & min).
        [$polarityRules, $polarityMessages] = $this->buildKpiUnitPolarityRules($polarity);
        $rules    = array_merge($rules, $polarityRules);
        $messages = array_merge($messages, $polarityMessages);

        // 4. Jalankan Validasi
        if (!$this->validate($rules, $messages)) {
            return redirect()->back()->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        if ($errPrecise = $this->validateToleransiPreciseAscending($polarity)) {
            return redirect()->back()->withInput()->with('errors', [$errPrecise]);
        }

        $m        = new \App\Models\KpiUnitModel();
        $kodeForm = strtoupper($kodeInput);

        // 5. Generate Kode Otomatis Jika Kode Kosong
        if (empty($kodeForm)) {
            $direktoratModel = new \App\Models\DirektoratModel();
            $direktorat      = $direktoratModel->find($direktoratId);
            $singkatan       = strtoupper(trim($direktorat['singkatan'] ?? ''));

            if (empty($singkatan) || strlen($singkatan) > 8) {
                $words     = explode(' ', $direktorat['nama']);
                $singkatan = strtoupper(implode('', array_map(fn($w) => substr(trim($w), 0, 1), array_filter($words))));
                $singkatan = substr($singkatan, 0, 4);
            }

            $prefixMap = ['Financial' => 'F', 'Customer' => 'C', 'Internal Process' => 'IP', 'Learning & Growth' => 'LG'];
            $prefix    = $prefixMap[$perspektif] ?? strtoupper(substr($perspektif, 0, 2));
            $pattern   = $singkatan . '-' . $prefix;

            $existing  = $m->db->table('kpi_unit')->select('kode')
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

            // Double check untuk kode hasil auto-generate jika ada race condition
            if ($m->where('kode', $kodeForm)->countAllResults() > 0) {
                return redirect()->back()->withInput()
                    ->with('error', "Kode auto-generate '$kodeForm' bentrok. Silakan coba simpan kembali.");
            }
        }

        // 6. Simpan Data
        $m->insert(array_merge([
            'direktorat_id' => $direktoratId,
            'perspektif'    => $perspektif,
            'nama_kpi'      => $namaKpi,
            'kode'          => $kodeForm,
            'satuan'        => $satuan,
            'bobot'         => 0,
            'polarity'      => $polarity,
            'urutan'        => (int)$this->request->getPost('urutan') ?: 99,
            'is_active'     => 1,
        ], $this->collectKpiUnitPolarityData($polarity)));

        return redirect()->to(base_url("master/kpi-unit/$direktoratId"))
            ->with('success', 'KPI Unit berhasil ditambahkan.');
    }

    public function kpiUnitEdit(int $id)
    {
        $check = $this->checkMenuAccess('master_direktorat');
        if ($check !== true) return $check;

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
        $check = $this->checkMenuEdit('master_direktorat');
        if ($check !== true) return $check;

        $m   = new \App\Models\KpiUnitModel();
        $kpi = $m->find($id);

        if (!$kpi) {
            return redirect()->to(base_url('master/direktorat'))
                ->with('error', 'KPI Unit tidak ditemukan.');
        }

        // 1. Pre-Sanitasi Input Text (potong spasi di awal & akhir)
        $namaKpi    = trim($this->request->getPost('nama_kpi') ?? '');
        $satuan     = trim($this->request->getPost('satuan') ?? '');
        $kode       = trim($this->request->getPost('kode') ?? '');
        $perspektif = trim($this->request->getPost('perspektif') ?? '');

        // Overwrite payload global post dengan data yang sudah di-trim
        $this->request->setGlobal('post', array_merge($this->request->getPost(), [
            'nama_kpi'   => $namaKpi,
            'satuan'     => $satuan,
            'kode'       => $kode,
            'perspektif' => $perspektif,
        ]));

        $polarity = $this->request->getPost('polarity') ?? 'max';

        // 2. Aturan Validasi Form
        $rules = [
            'nama_kpi'   => 'required|min_length[3]',
            'kode'       => "required|regex_match[/^\S+$/]|min_length[2]|is_unique[kpi_unit.kode,id,{$id}]",
            'perspektif' => 'required',
            'satuan'     => 'required|min_length[1]',
            'polarity'   => 'required|in_list[max,min,precise,special,tertimbang]',
        ];

        // 3. Pesan Error Custom
        $messages = [
            'nama_kpi' => [
                'required'   => 'Nama KPI wajib diisi (tidak boleh kosong atau hanya berupa spasi).',
                'min_length' => 'Nama KPI minimal 3 karakter.'
            ],
            'satuan' => [
                'required'   => 'Satuan KPI wajib diisi (tidak boleh kosong atau hanya berupa spasi).',
                'min_length' => 'Satuan KPI minimal 1 karakter.'
            ],
            'kode' => [
                'required'    => 'Kode KPI wajib diisi.',
                'regex_match' => 'Kode KPI tidak boleh mengandung spasi atau whitespace.',
                'min_length'  => 'Kode KPI minimal 2 karakter.',
                'is_unique'   => 'Kode KPI sudah digunakan oleh data lain.'
            ],
            'perspektif' => [
                'required' => 'Perspektif wajib dipilih.'
            ],
            'polarity' => [
                'required' => 'Polarity wajib dipilih.'
            ],
        ];

        [$polarityRules, $polarityMessages] = $this->buildKpiUnitPolarityRules($polarity);
        $rules    = array_merge($rules, $polarityRules);
        $messages = array_merge($messages, $polarityMessages);

        // 4. Jalankan Validasi
        if (!$this->validate($rules, $messages)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        if ($errPrecise = $this->validateToleransiPreciseAscending($polarity)) {
            return redirect()->back()->withInput()->with('errors', [$errPrecise]);
        }

        // 5. Update Data
        $m->update($id, array_merge([
            'perspektif' => $perspektif,
            'nama_kpi'   => $namaKpi,
            'kode'       => strtoupper($kode),
            'satuan'     => $satuan,
            'bobot'      => 0,
            'polarity'   => $polarity,
            'urutan'     => (int)$this->request->getPost('urutan') ?: 99,
        ], $this->collectKpiUnitPolarityData($polarity)));

        return redirect()->to(base_url("master/kpi-unit/{$kpi['direktorat_id']}"))
            ->with('success', 'KPI Unit berhasil diupdate.');
    }

    public function kpiUnitDelete(int $id)
    {
        $check = $this->checkMenuEdit('master_direktorat');
        if ($check !== true) return $check;

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
    public function kpiUnitImportForm(int $direktoratId)
    {
        $check = $this->checkMenuAccess('master_direktorat');
        if ($check !== true) return $check;

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
        $check = $this->checkMenuAccess('master_direktorat');
        if ($check !== true) return $check;

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
            'E' => 'Perubahan Polarity (info, otomatis ikut Polarity)',
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
        $sheet->setCellValue('H5', 'Kolom Perubahan Polarity hanya informasi, tidak dibaca saat import — otomatis mengikuti Polarity (max=Positif, min=Negatif).');
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
        $check = $this->checkMenuEdit('master_direktorat');
        if ($check !== true) return $check;

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
        $prefixMap       = ['Financial' => 'F', 'Customer' => 'C', 'Internal Process' => 'IP', 'Learning & Growth' => 'LG'];

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
            // Kolom E ("Perubahan Polarity") diabaikan — nilainya selalu
            // diturunkan dari Polarity (lihat catatan di
            // collectKpiUnitPolarityData()), bukan lagi dibaca dari template.
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
                'perubahan_polarity' => $polarity === 'min' ? 'neg' : 'pos',
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
    public function unitKerja()
    {
        $check = $this->checkMenuAccess('master_unitkerja');
        if ($check !== true) return $check;

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

    public function unitKerjaCreate()
    {
        $check = $this->checkMenuAccess('master_unitkerja');
        if ($check !== true) return $check;

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
        $check = $this->checkMenuEdit('master_unitkerja');
        if ($check !== true) return $check;

        // 1. Aturan Validasi
        $rules = [
            'kode'          => 'required|regex_match[/^\S+$/]|is_unique[divisi.kode]',
            'nama'          => 'required',
            'direktorat_id' => 'required',
        ];

        // 2. Pesan Error Custom
        $messages = [
            'kode' => [
                'required'    => 'Kode unit kerja wajib diisi.',
                'regex_match' => 'Kode unit kerja tidak boleh mengandung spasi atau whitespace.',
                'is_unique'   => 'Kode unit kerja sudah digunakan, gunakan kode lain.'
            ],
            'nama' => [
                'required' => 'Nama unit kerja wajib diisi.'
            ],
            'direktorat_id' => [
                'required' => 'Silakan pilih direktorat terlebih dahulu.'
            ]
        ];

        // 3. Jalankan Validasi
        if (!$this->validate($rules, $messages)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // 4. Simpan ke Database dengan Sanitasi trim()
        $m = new DivisiModel();
        $m->insert([
            'kode'          => strtoupper(trim($this->request->getPost('kode'))),
            'nama'          => trim($this->request->getPost('nama')),
            'direktorat_id' => $this->request->getPost('direktorat_id'),
            'deskripsi'     => trim($this->request->getPost('deskripsi') ?? ''),
            'kepala_divisi' => trim($this->request->getPost('kepala_divisi') ?? ''),
            'is_active'     => 1,
        ]);

        return redirect()->to(base_url('master/unit-kerja'))
            ->with('success', 'Unit kerja berhasil ditambahkan.');
    }

    public function unitKerjaEdit(int $id)
    {
        $check = $this->checkMenuAccess('master_unitkerja');
        if ($check !== true) return $check;

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
        $check = $this->checkMenuEdit('master_unitkerja');
        if ($check !== true) return $check;

        // 1. Aturan Validasi (Mengabaikan ID yang sedang di-update pada is_unique)
        $rules = [
            'kode'          => "required|regex_match[/^\S+$/]|is_unique[divisi.kode,id,{$id}]",
            'nama'          => 'required',
            'direktorat_id' => 'required',
        ];

        // 2. Pesan Error Custom
        $messages = [
            'kode' => [
                'required'    => 'Kode unit kerja wajib diisi.',
                'regex_match' => 'Kode unit kerja tidak boleh mengandung spasi atau whitespace.',
                'is_unique'   => 'Kode unit kerja sudah digunakan oleh data lain.'
            ],
            'nama' => [
                'required' => 'Nama unit kerja wajib diisi.'
            ],
            'direktorat_id' => [
                'required' => 'Silakan pilih direktorat terlebih dahulu.'
            ]
        ];

        // 3. Jalankan Validasi
        if (!$this->validate($rules, $messages)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // 4. Update Database dengan Sanitasi trim()
        $m = new DivisiModel();
        $m->update($id, [
            'kode'          => strtoupper(trim($this->request->getPost('kode'))),
            'nama'          => trim($this->request->getPost('nama')),
            'direktorat_id' => $this->request->getPost('direktorat_id'),
            'deskripsi'     => trim($this->request->getPost('deskripsi') ?? ''),
            'kepala_divisi' => trim($this->request->getPost('kepala_divisi') ?? ''),
        ]);

        return redirect()->to(base_url('master/unit-kerja'))
            ->with('success', 'Unit kerja berhasil diupdate.');
    }

    public function unitKerjaToggle(int $id)
    {
        $check = $this->checkMenuEdit('master_unitkerja');
        if ($check !== true) return $check;

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
        $check = $this->checkMenuEdit('master_unitkerja');
        if ($check !== true) return $check;

        $m = new \App\Models\DivisiModel();

        $pegawaiCount = (new \App\Models\PegawaiModel())
            ->where('divisi_id', $id)
            ->countAllResults();

        if ($pegawaiCount > 0) {
            return redirect()->to(base_url('master/unit-kerja'))
                ->with(
                    'error',
                    "Unit kerja tidak bisa dihapus karena masih memiliki "
                        . "<strong>$pegawaiCount pegawai</strong>. "
                        . "Pindahkan pegawai ke unit kerja lain terlebih dahulu."
                );
        }

        $kpiDivisiCount = (new \App\Models\KpiDivisiModel())
            ->where('divisi_id', $id)
            ->countAllResults();

        if ($kpiDivisiCount > 0) {
            return redirect()->to(base_url('master/unit-kerja'))
                ->with(
                    'error',
                    "Unit kerja tidak bisa dihapus karena masih memiliki "
                        . "<strong>$kpiDivisiCount KPI</strong> yang ditetapkan. "
                        . "Hapus assignment KPI Divisi terlebih dahulu."
                );
        }

        $m->delete($id);

        return redirect()->to(base_url('master/unit-kerja'))
            ->with('success', 'Unit kerja berhasil dihapus.');
    }

    public function direktoratDelete(int $id)
    {
        $check = $this->checkMenuEdit('master_direktorat');
        if ($check !== true) return $check;

        $m = new \App\Models\DirektoratModel();

        // Cek apakah masih ada divisi yang menggunakan direktorat ini
        $divisiCount = (new \App\Models\DivisiModel())
            ->where('direktorat_id', $id)
            ->countAllResults();

        if ($divisiCount > 0) {
            return redirect()->to(base_url('master/direktorat'))
                ->with(
                    'error',
                    "Direktorat tidak bisa dihapus karena masih memiliki
                                <strong>$divisiCount unit kerja</strong>.
                                Hapus atau pindahkan unit kerja terlebih dahulu."
                );
        }

        // Cek apakah masih ada KPI Unit yang menggunakan direktorat ini
        $kpiCount = (new \App\Models\KpiUnitModel())
            ->where('direktorat_id', $id)
            ->countAllResults();

        if ($kpiCount > 0) {
            return redirect()->to(base_url('master/direktorat'))
                ->with(
                    'error',
                    "Direktorat tidak bisa dihapus karena masih memiliki
                                <strong>$kpiCount KPI Unit</strong>.
                                Hapus KPI Unit terlebih dahulu."
                );
        }

        $m->delete($id);

        return redirect()->to(base_url('master/direktorat'))
            ->with('success', 'Direktorat berhasil dihapus.');
    }
}
