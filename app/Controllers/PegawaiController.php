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
    public function index(): string
    {
        $check = $this->checkMenuAccess('penilaian');
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
    public function create(): string
    {
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
        $rules = [
            'nama'      => 'required|min_length[3]',
            'nip'       => 'permit_empty',
            'divisi_id' => 'required',
            'jabatan'   => 'permit_empty',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                             ->withInput()
                             ->with('errors', $this->validator->getErrors());
        }

        // Cek NIP duplikat
        $nip = trim($this->request->getPost('nip'));
        if ($nip && $this->pegawaiModel->isNipExists($nip)) {
            return redirect()->back()
                             ->withInput()
                             ->with('error', "NIP '$nip' sudah terdaftar.");
        }

        $pegawaiId = $this->pegawaiModel->insert([
            'nip'       => $nip ?: null,
            'nama'      => $this->request->getPost('nama'),
            'jabatan'   => $this->request->getPost('jabatan'),
            'unit'      => $this->request->getPost('unit'),
            'divisi_id' => $this->request->getPost('divisi_id'),
            'golongan'  => $this->request->getPost('golongan'),
            'tgl_masuk' => $this->request->getPost('tgl_masuk') ?: null,
            'atasan_id' => $this->request->getPost('atasan_id') ?: null,
            'is_active' => 1,
        ]);

        // Buat akun user otomatis jika email diisi
        $email = trim($this->request->getPost('email'));
        if ($email) {
            $this->userModel->insert([
                'pegawai_id' => $pegawaiId,
                'nama'       => $this->request->getPost('nama'),
                'email'      => $email,
                'password'   => password_hash(
                    $this->request->getPost('password') ?: 'pegawai123',
                    PASSWORD_DEFAULT
                ),
                'role'       => $this->request->getPost('role') ?? 'pegawai',
                'is_active'  => 1,
            ]);
        }

        return redirect()->to(base_url('pegawai'))
                         ->with('success', 'Pegawai berhasil ditambahkan.');
    }

    // ── Form Edit ────────────────────────────────────────────
    public function edit(int $id): string
    {
        $pegawai = $this->pegawaiModel->getWithDivisi($id);
        if (!$pegawai) return redirect()->to(base_url('pegawai'))->with('error', 'Pegawai tidak ditemukan.');

        $user = $this->userModel->where('pegawai_id', $id)->first();

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
        $this->pegawaiModel->update($id, [
            'nip'       => trim($this->request->getPost('nip')) ?: null,
            'nama'      => $this->request->getPost('nama'),
            'jabatan'   => $this->request->getPost('jabatan'),
            'unit'      => $this->request->getPost('unit'),
            'divisi_id' => $this->request->getPost('divisi_id'),
            'golongan'  => $this->request->getPost('golongan'),
            'tgl_masuk' => $this->request->getPost('tgl_masuk') ?: null,
            'atasan_id' => $this->request->getPost('atasan_id') ?: null,
        ]);

        $email = trim($this->request->getPost('email'));
        if ($email) {
            $user = $this->userModel->where('pegawai_id', $id)->first();
            if ($user) {
                $updateData = ['email' => $email, 'role' => $this->request->getPost('role')];
                if ($pwd = trim($this->request->getPost('password'))) $updateData['password'] = password_hash($pwd, PASSWORD_DEFAULT);
                $this->userModel->update($user['id'], $updateData);
            } else {
                $this->userModel->insert([
                    'pegawai_id' => $id,
                    'nama'       => $this->request->getPost('nama'),
                    'email'      => $email,
                    'password'   => password_hash($this->request->getPost('password') ?: 'pegawai123', PASSWORD_DEFAULT),
                    'role'       => $this->request->getPost('role') ?? 'pegawai',
                    'is_active'  => 1,
                ]);
            }
        }
        return redirect()->to(base_url('pegawai'))->with('success', 'Data berhasil diupdate.');
    }

    public function toggle(int $id)
    {
        $pegawai = $this->pegawaiModel->find($id);
        if ($pegawai) $this->pegawaiModel->update($id, ['is_active' => $pegawai['is_active'] ? 0 : 1]);
        return redirect()->to(base_url('pegawai'))->with('success', 'Status pegawai diubah.');
    }

    public function delete(int $id)
    {
        $this->userModel->where('pegawai_id', $id)->delete();
        $this->pegawaiModel->delete($id);
        return redirect()->to(base_url('pegawai'))->with('success', 'Pegawai berhasil dihapus.');
    }

    // ── Download Template Import ──────────────────────────────
    public function templateImport()
    {
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
                'font' => ['bold'=>true,'color'=>['rgb'=>'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color'    => ['rgb' => '1F4E79'],
                ],
            ]);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Contoh data
        $contoh = [
            ['1234567890','Budi Santoso','Staff','Unit Kredit','DIV-HCD',
            'III/A','2020-01-15','budi@email.com','budi123','pegawai'],
            ['0987654321','Siti Rahayu','Kepala Unit','Unit Audit','DIV-IA',
            'III/B','2018-06-01','siti@email.com','siti123','approver'],
        ];

        foreach ($contoh as $i => $row) {
            $rowNum = $i + 2;
            foreach (array_values($row) as $j => $val) {
                $col = chr(65 + $j);
                $sheet->setCellValue("{$col}{$rowNum}", $val);
            }
        }

        // Catatan
        $sheet->setCellValue('A' . (count($contoh)+3),
            '* Wajib diisi. Kode Divisi harus sesuai dengan kode di Master Data Unit Kerja.');
        $sheet->getStyle('A' . (count($contoh)+3))->getFont()
            ->setItalic(true)->setColor(
                new \PhpOffice\PhpSpreadsheet\Style\Color('FF888888'));

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
    public function importForm(): string
    {
        return view('layouts/main', [
            'title'   => 'Import Data Pegawai',
            'content' => view('master/pegawai/_import'),
        ]);
    }

    // ── Proses Import ─────────────────────────────────────────
    public function importProcess()
    {
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

        // Validasi nama file (cegah path traversal)
        $extension = $file->getClientExtension();
        if (!in_array($extension, ['xlsx', 'xls'])) {
            return redirect()->back()
                            ->with('error', 'Ekstensi file tidak diizinkan.');
        }

        // Generate nama file random untuk temp (cegah overwrite)
        $newName = $file->getRandomName();

        try {
            $reader      = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet = $reader->load($file->getTempName());
            // ... sisa kode tetap sama
        } catch (\Exception $e) {
            log_message('error', 'Import gagal: ' . $e->getMessage());
            return redirect()->back()
                            ->with('error', 'Gagal membaca file. Pastikan format sesuai template.');
        }
    }
}