<?php

namespace App\Controllers;

use App\Models\PeriodeModel;

class PeriodeController extends BaseController
{
    protected PeriodeModel $periodeModel;

    public function __construct()
    {
        $this->periodeModel = new PeriodeModel();

        $role = session()->get('role');
        if ($role !== 'admin' && $role !== 'hr') {
            session()->setFlashdata('error', 'Anda tidak memiliki akses ke halaman Periode Penilaian.');
            header('Location: ' . base_url('dashboard'));
            exit;
        }
    }

    // ── Daftar Periode ───────────────────────────────────────
    public function index(): string
    {

        return view('layouts/main', [
            'title'   => 'Periode Penilaian',
            'content' => view('master/periode/_content', [
                'periodes'      => $this->periodeModel->getAllOrdered(),
                'periode_aktif' => $this->periodeModel->getAktif(),
            ]),
        ]);
    }

    // ── Form Tambah ──────────────────────────────────────────
    public function create(): string
    {
        return view('layouts/main', [
            'title'   => 'Buat Periode Baru',
            'content' => view('master/periode/_form', [
                'periode' => null,
                'action'  => base_url('master/periode/store'),
            ]),
        ]);
    }

    // ── Simpan ───────────────────────────────────────────────
    public function store()
    {
        // 1. Aturan Validasi Form
        $rules = [
            'nama'        => 'required|trim|min_length[3]',
            'kode'        => 'required|trim|regex_match[/^\S+$/]|is_unique[periode.kode]',
            'tgl_mulai'   => 'required|valid_date',
            'tgl_selesai' => 'required|valid_date',
        ];

        // 2. Pesan Error Custom
        $messages = [
            'nama' => [
                'required'   => 'Nama periode wajib diisi.',
                'min_length' => 'Nama periode minimal 3 karakter.'
            ],
            'kode' => [
                'required'    => 'Kode periode wajib diisi.',
                'regex_match' => 'Kode periode tidak boleh mengandung spasi atau whitespace.',
                'is_unique'   => 'Kode periode sudah digunakan, gunakan kode lain.'
            ],
            'tgl_mulai' => [
                'required'   => 'Tanggal mulai wajib diisi.',
                'valid_date' => 'Format tanggal mulai tidak valid.'
            ],
            'tgl_selesai' => [
                'required'   => 'Tanggal selesai wajib diisi.',
                'valid_date' => 'Format tanggal selesai tidak valid.'
            ]
        ];

        // 3. Jalankan Validasi
        if (!$this->validate($rules, $messages)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $kode   = strtoupper(trim($this->request->getPost('kode')));
        $status = $this->request->getPost('status');

        // 4. Pastikan hanya ada 1 periode aktif
        if ($status === 'aktif' && $this->periodeModel->hasAktif()) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Sudah ada periode aktif. Tutup periode aktif dulu sebelum mengaktifkan yang baru.');
        }

        // 5. Insert Data Periode
        $this->periodeModel->insert([
            'nama'        => trim($this->request->getPost('nama')),
            'kode'        => $kode,
            'tgl_mulai'   => $this->request->getPost('tgl_mulai'),
            'tgl_selesai' => $this->request->getPost('tgl_selesai'),
            'status'      => $status ?? 'draft',
        ]);

        return redirect()->to(base_url('master/periode'))
            ->with('success', 'Periode berhasil dibuat.');
    }

    // ── Form Edit ────────────────────────────────────────────
    public function edit(int $id)
    {
        $periode = $this->periodeModel->find($id);
        if (!$periode) {
            return redirect()->to(base_url('master/periode'))
                ->with('error', 'Periode tidak ditemukan.');
        }

        return view('layouts/main', [
            'title'   => 'Edit Periode',
            'content' => view('master/periode/_form', [
                'periode' => $periode,
                'action'  => base_url("master/periode/update/$id"),
            ]),
        ]);
    }

    // ── Update ───────────────────────────────────────────────
    public function update(int $id)
    {
        $existingPeriode = $this->periodeModel->find($id);
        if (!$existingPeriode) {
            return redirect()->to(base_url('master/periode'))
                ->with('error', 'Periode tidak ditemukan.');
        }

        // 1. Aturan Validasi Form (Mengabaikan ID saat ini untuk is_unique)
        $rules = [
            'nama'        => 'required|trim|min_length[3]',
            'kode'        => "required|trim|regex_match[/^\S+$/]|is_unique[periode.kode,id,{$id}]",
            'tgl_mulai'   => 'required|valid_date',
            'tgl_selesai' => 'required|valid_date',
        ];

        // 2. Pesan Error Custom
        $messages = [
            'nama' => [
                'required'   => 'Nama periode wajib diisi.',
                'min_length' => 'Nama periode minimal 3 karakter.'
            ],
            'kode' => [
                'required'    => 'Kode periode wajib diisi.',
                'regex_match' => 'Kode periode tidak boleh mengandung spasi atau whitespace.',
                'is_unique'   => 'Kode periode sudah digunakan oleh data lain.'
            ],
            'tgl_mulai' => [
                'required'   => 'Tanggal mulai wajib diisi.',
                'valid_date' => 'Format tanggal mulai tidak valid.'
            ],
            'tgl_selesai' => [
                'required'   => 'Tanggal selesai wajib diisi.',
                'valid_date' => 'Format tanggal selesai tidak valid.'
            ]
        ];

        // 3. Jalankan Validasi
        if (!$this->validate($rules, $messages)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $kode   = strtoupper(trim($this->request->getPost('kode')));
        $status = $this->request->getPost('status');

        // 4. Pastikan hanya ada 1 periode aktif
        if ($status === 'aktif' && $this->periodeModel->hasAktif($id)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Sudah ada periode aktif lain. Tutup periode aktif dulu.');
        }

        // 5. Update Data Periode
        $this->periodeModel->update($id, [
            'nama'        => trim($this->request->getPost('nama')),
            'kode'        => $kode,
            'tgl_mulai'   => $this->request->getPost('tgl_mulai'),
            'tgl_selesai' => $this->request->getPost('tgl_selesai'),
            'status'      => $status,
        ]);

        // Sama seperti setStatus() — begitu Periode ditutup lewat form Edit
        // ini, arsipkan snapshot beku Penilaian-nya juga.
        $arsipInfo = '';
        if ($status === 'tutup') {
            $jumlah = (new \App\Services\PenilaianArsipService())
                ->arsipkanPeriode($id, session()->get('user_id'));
            $arsipInfo = " {$jumlah} baris penilaian berhasil diarsipkan.";
        }

        return redirect()->to(base_url('master/periode'))
            ->with('success', "Periode berhasil diupdate.{$arsipInfo}");
    }

    // ── Ubah Status Cepat ────────────────────────────────────
    public function setStatus(int $id, string $status)
    {
        $periode = $this->periodeModel->find($id);
        if (!$periode) {
            return redirect()->to(base_url('master/periode'))
                ->with('error', 'Periode tidak ditemukan.');
        }

        $allowed = ['draft', 'aktif', 'tutup'];
        if (!in_array($status, $allowed)) {
            return redirect()->to(base_url('master/periode'))
                ->with('error', 'Status tidak valid.');
        }

        // Hanya 1 periode aktif
        if ($status === 'aktif' && $this->periodeModel->hasAktif($id)) {
            return redirect()->to(base_url('master/periode'))
                ->with('error', 'Sudah ada periode aktif. Tutup periode aktif dulu.');
        }

        $this->periodeModel->update($id, ['status' => $status]);

        // Begitu Periode ditutup, arsipkan (snapshot beku) seluruh Penilaian
        // beserta konfigurasi KPI Induk & Turunan pada saat itu — supaya
        // laporan periode ini tidak lagi ikut berubah walau konfigurasi KPI
        // diubah kemudian untuk periode berikutnya.
        $arsipInfo = '';
        if ($status === 'tutup') {
            $jumlah = (new \App\Services\PenilaianArsipService())
                ->arsipkanPeriode($id, session()->get('user_id'));
            $arsipInfo = " {$jumlah} baris penilaian berhasil diarsipkan.";
        }

        $labels = ['draft' => 'Draft', 'aktif' => 'Diaktifkan', 'tutup' => 'Ditutup'];
        return redirect()->to(base_url('master/periode'))
            ->with('success', "Periode berhasil di-set: {$labels[$status]}.{$arsipInfo}");
    }

    // ── Hapus ────────────────────────────────────────────────
    public function delete(int $id)
    {
        $periode = $this->periodeModel->find($id);
        if (!$periode) {
            return redirect()->to(base_url('master/periode'))
                ->with('error', 'Periode tidak ditemukan.');
        }

        if ($periode['status'] === 'aktif') {
            return redirect()->to(base_url('master/periode'))
                ->with('error', 'Periode aktif tidak bisa dihapus. Tutup dulu.');
        }

        // Cegah penghapusan periode yang masih memiliki data penilaian historis.
        // Tanpa pengecekan ini, menghapus periode akan meninggalkan data yatim
        // pada tabel penilaian, penilaian_unit, dan email_log — merusak Rekap,
        // Audit Trail, dan Histori Notifikasi untuk periode tersebut.
        $jumlahPenilaian = $this->periodeModel->db->table('penilaian')
            ->where('periode_id', $id)
            ->countAllResults();

        if ($jumlahPenilaian > 0) {
            return redirect()->to(base_url('master/periode'))
                ->with(
                    'error',
                    "Periode tidak bisa dihapus karena masih memiliki "
                        . "<strong>$jumlahPenilaian data penilaian individu</strong>. "
                        . "Hapus seluruh data penilaian terkait terlebih dahulu."
                );
        }

        $jumlahPenilaianUnit = $this->periodeModel->db->table('penilaian_unit')
            ->where('periode_id', $id)
            ->countAllResults();

        if ($jumlahPenilaianUnit > 0) {
            return redirect()->to(base_url('master/periode'))
                ->with(
                    'error',
                    "Periode tidak bisa dihapus karena masih memiliki "
                        . "<strong>$jumlahPenilaianUnit data penilaian unit</strong>. "
                        . "Hapus seluruh data penilaian terkait terlebih dahulu."
                );
        }

        $this->periodeModel->delete($id);
        return redirect()->to(base_url('master/periode'))
            ->with('success', 'Periode berhasil dihapus.');
    }
}
