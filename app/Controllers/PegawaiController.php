<?php

namespace App\Controllers;

use App\Models\PegawaiModel;
use App\Models\DivisiModel;
use App\Models\UserModel;

class PegawaiController extends BaseController
{
    protected PegawaiModel $pegawaiModel;
    protected DivisiModel  $divisiModel;
    protected UserModel    $userModel;

    public function __construct()
    {
        $this->pegawaiModel = new PegawaiModel();
        $this->divisiModel  = new DivisiModel();
        $this->userModel    = new UserModel();
    }

    // ── Daftar Pegawai ───────────────────────────────────────
    public function index()
    {
        $check = $this->checkMenuAccess('pegawai');
        if ($check !== true) return $check;

        $pegawai = $this->pegawaiModel->getAllWithDivisi();

        // PERBAIKAN: Mengganti whereNotNull dengan sintaks CI4 yang benar
        $allUsers = $this->userModel->select('pegawai_id')
            ->where('pegawai_id IS NOT NULL')
            ->findAll();

        $userPegawaiIds = array_column($allUsers, 'pegawai_id');

        // Tandai setiap pegawai apakah punya user
        foreach ($pegawai as &$p) {
            $p['has_user'] = in_array($p['id'], $userPegawaiIds);
        }

        $grouped = [];
        foreach ($pegawai as $p) {
            $key = $p['nama_divisi'] ?? 'Belum Ada Divisi';
            $grouped[$key][] = $p;
        }

        return view('layouts/main', [
            'title'   => 'Data Pegawai',
            'content' => view('master/pegawai/_content', [
                'grouped'       => $grouped,
                'total_pegawai' => count($pegawai),
                'divisi_list'   => $this->divisiModel->getActive(),
            ]),
        ]);
    }

    // ── Form Tambah ──────────────────────────────────────────
    public function create()
    {
        $check = $this->checkMenuAccess('pegawai');
        if ($check !== true) return $check;

        return view('layouts/main', [
            'title'   => 'Tambah Pegawai',
            'content' => view('master/pegawai/_form', [
                'pegawai'      => null,
                'action'       => base_url('pegawai/store'),
                'divisi_dd'    => $this->divisiModel->getDropdown(),
                'atasan_dd'    => $this->pegawaiModel->getDropdown(),
                'create_user'  => true,
            ]),
        ]);
    }

    // ── Simpan Tambah ────────────────────────────────────────
    public function store()
    {
        $check = $this->checkMenuEdit('pegawai');
        if ($check !== true) return $check;

        // 1. Aturan Validasi Form (NIP & Email unik otomatis)
        $rules = [
            'nama'      => 'required|trim|min_length[3]',
            'nip'       => 'permit_empty|trim|regex_match[/^\S+$/]|is_unique[pegawai.nip]',
            'divisi_id' => 'required',
            'jabatan'   => 'permit_empty|trim',
            'unit'      => 'permit_empty|trim',
            'golongan'  => 'permit_empty|trim',
            'email'     => 'permit_empty|trim|valid_email|is_unique[users.email]',
        ];

        // 2. Pesan Error Custom
        $messages = [
            'nama' => [
                'required'   => 'Nama pegawai wajib diisi.',
                'min_length' => 'Nama pegawai minimal 3 karakter.'
            ],
            'nip' => [
                'regex_match' => 'NIP tidak boleh mengandung spasi atau whitespace.',
                'is_unique'   => 'NIP sudah terdaftar pada sistem.'
            ],
            'email' => [
                'valid_email' => 'Format email tidak valid.',
                'is_unique'   => 'Email sudah digunakan oleh akun lain.'
            ]
        ];

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $nip   = trim($this->request->getPost('nip') ?? '');
        $nama  = trim($this->request->getPost('nama'));
        $email = trim($this->request->getPost('email') ?? '');

        // 3. Guard Anti-Duplikat Double Submit (Rentang 10 detik)
        $duplikatRecent = $this->pegawaiModel
            ->where('nama', $nama)
            ->where('divisi_id', $this->request->getPost('divisi_id'))
            ->where('created_at >=', date('Y-m-d H:i:s', time() - 10))
            ->first();

        if ($duplikatRecent) {
            return redirect()->to(base_url('pegawai'))
                ->with('success', 'Pegawai berhasil ditambahkan.');
        }

        // 4. Insert Data Pegawai
        $pegawaiId = $this->pegawaiModel->insert([
            'nip'       => $nip !== '' ? $nip : null,
            'nama'      => $nama,
            'jabatan'   => trim($this->request->getPost('jabatan') ?? '') ?: null,
            'unit'      => trim($this->request->getPost('unit') ?? '') ?: null,
            'divisi_id' => $this->request->getPost('divisi_id'),
            'golongan'  => trim($this->request->getPost('golongan') ?? '') ?: null,
            'tgl_masuk' => $this->request->getPost('tgl_masuk') ?: null,
            'atasan_id' => $this->request->getPost('atasan_id') ?: null,
            'is_active' => 1,
        ]);

        // 5. Buat Akun User Otomatis Jika Email Diisi
        if ($email !== '') {
            $passwordInput = trim($this->request->getPost('password') ?? '');
            $role          = $this->request->getPost('role');

            $this->userModel->insert([
                'pegawai_id'           => $pegawaiId,
                'nama'                 => $nama,
                'email'                => $email,
                'password'             => password_hash(
                    $passwordInput !== '' ? $passwordInput : 'pegawai123',
                    PASSWORD_DEFAULT
                ),
                'must_change_password' => $passwordInput !== '' ? 0 : 1,
                'role'                 => in_array($role, ['admin', 'hr', 'drafter', 'approver', 'pegawai'], true)
                    ? $role : 'pegawai',
                'is_active'            => 1,
            ]);
        }

        return redirect()->to(base_url('pegawai'))
            ->with('success', 'Pegawai berhasil ditambahkan.');
    }

    // ── Form Edit ────────────────────────────────────────────
    public function edit(int $id)
    {
        $check = $this->checkMenuAccess('pegawai');
        if ($check !== true) return $check;

        $pegawai = $this->pegawaiModel->getWithDivisi($id);
        if (!$pegawai) return redirect()->to(base_url('pegawai'))->with('error', 'Pegawai tidak ditemukan.');

        $user = $this->userModel->where('pegawai_id', $id)->first();
        if ($user) unset($user['password']);

        return view('layouts/main', [
            'title'   => 'Edit Pegawai',
            'content' => view('master/pegawai/_form', [
                'pegawai'   => $pegawai,
                'action'    => base_url("pegawai/update/$id"),
                'divisi_dd' => $this->divisiModel->getDropdown(),
                'atasan_dd' => $this->pegawaiModel->getDropdown($id),
                'user'      => $user,
            ]),
        ]);
    }

    public function update(int $id)
    {
        $check = $this->checkMenuEdit('pegawai');
        if ($check !== true) return $check;

        $pegawaiExisting = $this->pegawaiModel->find($id);
        if (!$pegawaiExisting) {
            return redirect()->to(base_url('pegawai'))->with('error', 'Pegawai tidak ditemukan.');
        }

        $existingUser = $this->userModel->where('pegawai_id', $id)->first();
        $userId       = $existingUser ? $existingUser['id'] : '';

        // 1. Aturan Validasi Form (NIP & Email mengabaikan ID yang sedang di-update)
        $rules = [
            'nama'      => 'required|trim|min_length[3]',
            'nip'       => "permit_empty|trim|regex_match[/^\S+$/]|is_unique[pegawai.nip,id,{$id}]",
            'divisi_id' => 'required',
            'jabatan'   => 'permit_empty|trim',
            'unit'      => 'permit_empty|trim',
            'golongan'  => 'permit_empty|trim',
            'email'     => "permit_empty|trim|valid_email|is_unique[users.email,id,{$userId}]",
        ];

        // 2. Pesan Error Custom
        $messages = [
            'nama' => [
                'required'   => 'Nama pegawai wajib diisi.',
                'min_length' => 'Nama pegawai minimal 3 karakter.'
            ],
            'nip' => [
                'regex_match' => 'NIP tidak boleh mengandung spasi atau whitespace.',
                'is_unique'   => 'NIP sudah digunakan oleh pegawai lain.'
            ],
            'email' => [
                'valid_email' => 'Format email tidak valid.',
                'is_unique'   => 'Email sudah digunakan oleh akun lain.'
            ]
        ];

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $nip   = trim($this->request->getPost('nip') ?? '');
        $nama  = trim($this->request->getPost('nama'));
        $email = trim($this->request->getPost('email') ?? '');

        // 3. Update Data Pegawai
        $this->pegawaiModel->update($id, [
            'nip'       => $nip !== '' ? $nip : null,
            'nama'      => $nama,
            'jabatan'   => trim($this->request->getPost('jabatan') ?? '') ?: null,
            'unit'      => trim($this->request->getPost('unit') ?? '') ?: null,
            'divisi_id' => $this->request->getPost('divisi_id'),
            'golongan'  => trim($this->request->getPost('golongan') ?? '') ?: null,
            'tgl_masuk' => $this->request->getPost('tgl_masuk') ?: null,
            'atasan_id' => $this->request->getPost('atasan_id') ?: null,
        ]);

        // 4. Sync/Update Akun User
        if ($email !== '') {
            $role = $this->request->getPost('role');
            $role = in_array($role, ['admin', 'hr', 'drafter', 'approver', 'pegawai'], true)
                ? $role : 'pegawai';

            if ($existingUser) {
                $updateData = [
                    'nama'  => $nama,
                    'email' => $email,
                    'role'  => $role
                ];

                if ($pwd = trim($this->request->getPost('password') ?? '')) {
                    $updateData['password']             = password_hash($pwd, PASSWORD_DEFAULT);
                    $updateData['must_change_password'] = 1;
                }

                $this->userModel->update($existingUser['id'], $updateData);
            } else {
                $pwd = trim($this->request->getPost('password') ?? '');
                $this->userModel->insert([
                    'pegawai_id'           => $id,
                    'nama'                 => $nama,
                    'email'                => $email,
                    'password'             => password_hash($pwd !== '' ? $pwd : 'pegawai123', PASSWORD_DEFAULT),
                    'must_change_password' => 1,
                    'role'                 => $role,
                    'is_active'            => 1,
                ]);
            }
        }

        return redirect()->to(base_url('pegawai'))->with('success', 'Data berhasil diupdate.');
    }

    public function toggle(int $id)
    {
        $check = $this->checkMenuEdit('pegawai');
        if ($check !== true) return $check;

        $pegawai = $this->pegawaiModel->find($id);
        if ($pegawai) $this->pegawaiModel->update($id, ['is_active' => $pegawai['is_active'] ? 0 : 1]);
        return redirect()->to(base_url('pegawai'))->with('success', 'Status pegawai diubah.');
    }

    public function delete(int $id)
    {
        $check = $this->checkMenuEdit('pegawai');
        if ($check !== true) return $check;

        $pegawai = $this->pegawaiModel->find($id);
        if (!$pegawai) {
            return redirect()->to(base_url('pegawai'))
                ->with('error', 'Pegawai tidak ditemukan.');
        }

        // Cegah penghapusan pegawai yang masih memiliki riwayat penilaian.
        // Tanpa pengecekan ini, menghapus pegawai akan meninggalkan data
        // yatim pada tabel penilaian — merusak Rekap, Laporan, dan Audit
        // Trail untuk pegawai tersebut secara permanen.
        $jumlahPenilaian = $this->pegawaiModel->db->table('penilaian')
            ->where('pegawai_id', $id)
            ->countAllResults();

        if ($jumlahPenilaian > 0) {
            return redirect()->to(base_url('pegawai'))
                ->with(
                    'error',
                    "Pegawai tidak bisa dihapus karena masih memiliki "
                        . "<strong>$jumlahPenilaian data penilaian</strong>. "
                        . "Nonaktifkan pegawai ini alih-alih menghapusnya jika "
                        . "sudah tidak aktif bekerja."
                );
        }

        $jumlahKpiPegawai = $this->pegawaiModel->db->table('kpi_pegawai')
            ->where('pegawai_id', $id)
            ->countAllResults();

        if ($jumlahKpiPegawai > 0) {
            return redirect()->to(base_url('pegawai'))
                ->with(
                    'error',
                    "Pegawai tidak bisa dihapus karena masih memiliki "
                        . "konfigurasi KPI Per Pegawai. Hapus konfigurasi KPI "
                        . "terlebih dahulu di modul KPI Per Pegawai."
                );
        }

        $this->userModel->where('pegawai_id', $id)->delete();
        $this->pegawaiModel->delete($id);
        return redirect()->to(base_url('pegawai'))->with('success', 'Pegawai berhasil dihapus.');
    }

    // ── Download Template Import ──────────────────────────────
    public function templateImport()
    {
        $check = $this->checkMenuAccess('pegawai');
        if ($check !== true) return $check;

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Import Pegawai');

        // Header
        $headers = [
            'A' => 'NIP',
            'B' => 'Nama Lengkap *',
            'C' => 'Jabatan',
            'D' => 'Unit/Bagian',
            'E' => 'Kode Divisi *',
            'F' => 'Golongan',
            'G' => 'Tanggal Masuk (YYYY-MM-DD)',
            'H' => 'Email (untuk akun login)',
            'I' => 'Password (default: pegawai123)',
            'J' => 'Role (admin/hr/drafter/approver/pegawai)',
        ];

        foreach ($headers as $col => $label) {
            $sheet->setCellValue("{$col}1", $label);
            $sheet->getStyle("{$col}1")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color'    => ['rgb' => '1F4E79'],
                ],
            ]);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Contoh data
        $contoh = [
            [
                '1234567890',
                'Budi Santoso',
                'Staff',
                'Unit Kredit',
                'DIV-HCD',
                'III/A',
                '2020-01-15',
                'budi@email.com',
                'budi123',
                'pegawai'
            ],
            [
                '0987654321',
                'Siti Rahayu',
                'Kepala Unit',
                'Unit Audit',
                'DIV-IA',
                'III/B',
                '2018-06-01',
                'siti@email.com',
                'siti123',
                'approver'
            ],
        ];

        foreach ($contoh as $i => $row) {
            $rowNum = $i + 2;
            foreach (array_values($row) as $j => $val) {
                $col = chr(65 + $j);
                $sheet->setCellValue("{$col}{$rowNum}", $val);
            }
        }

        // Catatan
        $sheet->setCellValue(
            'A' . (count($contoh) + 3),
            '* Wajib diisi. Kode Divisi harus sesuai dengan kode di Master Data Unit Kerja.'
        );
        $sheet->getStyle('A' . (count($contoh) + 3))->getFont()
            ->setItalic(true)->setColor(
                new \PhpOffice\PhpSpreadsheet\Style\Color('FF888888')
            );

        // Daftar divisi sebagai referensi di sheet kedua
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Referensi Divisi');
        $sheet2->setCellValue('A1', 'Kode Divisi');
        $sheet2->setCellValue('B1', 'Nama Divisi');
        $sheet2->setCellValue('C1', 'Direktorat');

        $divisis = $this->divisiModel->getAllWithDirektorat();
        foreach ($divisis as $i => $div) {
            $row = $i + 2;
            $sheet2->setCellValue("A{$row}", $div['kode']);
            $sheet2->setCellValue("B{$row}", $div['nama']);
            $sheet2->setCellValue("C{$row}", $div['nama_direktorat'] ?? '—');
        }
        $sheet2->getColumnDimension('A')->setWidth(15);
        $sheet2->getColumnDimension('B')->setWidth(40);
        $sheet2->getColumnDimension('C')->setWidth(40);

        $filename = 'Template_Import_Pegawai.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // ── Form Import ───────────────────────────────────────────
    public function importForm()
    {
        $check = $this->checkMenuAccess('pegawai');
        if ($check !== true) return $check;

        return view('layouts/main', [
            'title'   => 'Import Data Pegawai',
            'content' => view('master/pegawai/_import'),
        ]);
    }

    // ── Proses Import ─────────────────────────────────────────
    public function importProcess()
    {
        $check = $this->checkMenuEdit('pegawai');
        if ($check !== true) return $check;

        $file = $this->request->getFile('file_excel');

        if (!$file || !$file->isValid()) {
            return redirect()->back()
                ->with('error', 'File tidak valid.');
        }

        // Validasi MIME type yang sebenarnya, bukan hanya ekstensi
        $allowedMimes = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
        ];

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            return redirect()->back()
                ->with('error', 'Format file tidak valid. Gunakan .xlsx atau .xls.');
        }

        // Validasi ukuran maksimal 5MB
        if ($file->getSize() > 5 * 1024 * 1024) {
            return redirect()->back()
                ->with('error', 'Ukuran file maksimal 5MB.');
        }

        // Validasi ekstensi file (cegah path traversal/penyamaran ekstensi)
        $extension = $file->getClientExtension();
        if (!in_array($extension, ['xlsx', 'xls'])) {
            return redirect()->back()
                ->with('error', 'Ekstensi file tidak diizinkan.');
        }

        try {
            $reader      = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet = $reader->load($file->getTempName());
            $sheet       = $spreadsheet->getSheet(0);
            $rows        = $sheet->toArray(null, true, true, false);

            // Baris pertama adalah header, mulai data dari baris kedua
            array_shift($rows);

            // Peta Kode Divisi -> ID Divisi untuk validasi cepat
            $divisiMap = [];
            foreach ($this->divisiModel->getActive() as $d) {
                $divisiMap[strtoupper(trim($d['kode']))] = $d['id'];
            }

            $berhasil = 0;
            $dilewati = [];

            foreach ($rows as $i => $row) {
                $baris = $i + 2; // nomor baris asli di Excel (1 = header)

                // Lewati baris yang sepenuhnya kosong
                if (empty(array_filter($row, fn($v) => trim((string) $v) !== ''))) {
                    continue;
                }

                $nip       = trim((string) ($row[0] ?? ''));
                $nama      = trim((string) ($row[1] ?? ''));
                $jabatan   = trim((string) ($row[2] ?? ''));
                $unit      = trim((string) ($row[3] ?? ''));
                $kodeDiv   = strtoupper(trim((string) ($row[4] ?? '')));
                $golongan  = trim((string) ($row[5] ?? ''));
                $tglMasuk  = trim((string) ($row[6] ?? ''));
                $email     = trim((string) ($row[7] ?? ''));
                $password  = trim((string) ($row[8] ?? ''));
                $role      = trim((string) ($row[9] ?? 'pegawai'));

                // Validasi field wajib
                if ($nama === '') {
                    $dilewati[] = "Baris {$baris}: Nama Lengkap wajib diisi.";
                    continue;
                }
                if ($kodeDiv === '' || !isset($divisiMap[$kodeDiv])) {
                    $dilewati[] = "Baris {$baris} ({$nama}): Kode Divisi '{$kodeDiv}' tidak ditemukan pada Master Data Unit Kerja.";
                    continue;
                }

                // NIP yang sudah terdaftar dilewati, tidak di-overwrite
                if ($nip !== '' && $this->pegawaiModel->isNipExists($nip)) {
                    $dilewati[] = "Baris {$baris} ({$nama}): NIP '{$nip}' sudah terdaftar, baris dilewati.";
                    continue;
                }

                $pegawaiId = $this->pegawaiModel->insert([
                    'nip'       => $nip !== '' ? $nip : null,
                    'nama'      => $nama,
                    'jabatan'   => $jabatan !== '' ? $jabatan : null,
                    'unit'      => $unit !== '' ? $unit : null,
                    'divisi_id' => $divisiMap[$kodeDiv],
                    'golongan'  => $golongan !== '' ? $golongan : null,
                    'tgl_masuk' => $tglMasuk !== '' ? $tglMasuk : null,
                    'is_active' => 1,
                ]);

                // Email yang sudah terdaftar sebagai user tidak dibuat ulang
                if ($email !== '' && !$this->userModel->where('email', $email)->first()) {
                    $this->userModel->insert([
                        'pegawai_id'           => $pegawaiId,
                        'nama'                 => $nama,
                        'email'                => $email,
                        'password'             => password_hash(
                            $password !== '' ? $password : 'pegawai123',
                            PASSWORD_DEFAULT
                        ),
                        'must_change_password' => 1,
                        'role'                 => in_array($role, ['admin', 'hr', 'drafter', 'approver', 'pegawai'])
                            ? $role : 'pegawai',
                        'is_active'            => 1,
                    ]);
                }

                $berhasil++;
            }

            $pesan = "{$berhasil} pegawai berhasil diimpor.";
            if (!empty($dilewati)) {
                $pesan .= ' ' . count($dilewati) . ' baris dilewati (lihat detail di bawah).';
            }

            return redirect()->to(base_url('pegawai'))
                ->with($berhasil > 0 ? 'success' : 'error', $pesan)
                ->with('import_errors', $dilewati);
        } catch (\Exception $e) {
            log_message('error', 'Import gagal: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Gagal membaca file. Pastikan format sesuai template.');
        }
    }
}
