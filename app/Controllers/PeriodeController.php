<?php
namespace App\Controllers;

use App\Models\PeriodeModel;

class PeriodeController extends BaseController
{
    protected PeriodeModel $periodeModel;

    public function __construct()
    {
        $this->periodeModel = new PeriodeModel();
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
        $rules = [
            'nama'        => 'required',
            'kode'        => 'required',
            'tgl_mulai'   => 'required|valid_date',
            'tgl_selesai' => 'required|valid_date',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                             ->withInput()
                             ->with('errors', $this->validator->getErrors());
        }

        // Cek kode duplikat
        $kode = strtoupper(trim($this->request->getPost('kode')));
        if ($this->periodeModel->where('kode', $kode)->countAllResults() > 0) {
            return redirect()->back()
                             ->withInput()
                             ->with('error', "Kode periode '$kode' sudah digunakan.");
        }

        $status = $this->request->getPost('status');

        // Pastikan hanya 1 periode aktif
        if ($status === 'aktif' && $this->periodeModel->hasAktif()) {
            return redirect()->back()
                             ->withInput()
                             ->with('error', 'Sudah ada periode aktif. Tutup periode aktif dulu sebelum mengaktifkan yang baru.');
        }

        $this->periodeModel->insert([
            'nama'        => $this->request->getPost('nama'),
            'kode'        => $kode,
            'tgl_mulai'   => $this->request->getPost('tgl_mulai'),
            'tgl_selesai' => $this->request->getPost('tgl_selesai'),
            'status'      => $status ?? 'draft',
        ]);

        return redirect()->to(base_url('master/periode'))
                         ->with('success', 'Periode berhasil dibuat.');
    }

    // ── Form Edit ────────────────────────────────────────────
    public function edit(int $id): string
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
        $rules = [
            'nama'        => 'required',
            'tgl_mulai'   => 'required|valid_date',
            'tgl_selesai' => 'required|valid_date',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                             ->withInput()
                             ->with('errors', $this->validator->getErrors());
        }

        $status = $this->request->getPost('status');

        // Pastikan hanya 1 periode aktif
        if ($status === 'aktif' && $this->periodeModel->hasAktif($id)) {
            return redirect()->back()
                             ->withInput()
                             ->with('error', 'Sudah ada periode aktif lain. Tutup periode aktif dulu.');
        }

        $this->periodeModel->update($id, [
            'nama'        => $this->request->getPost('nama'),
            'tgl_mulai'   => $this->request->getPost('tgl_mulai'),
            'tgl_selesai' => $this->request->getPost('tgl_selesai'),
            'status'      => $status,
        ]);

        return redirect()->to(base_url('master/periode'))
                         ->with('success', 'Periode berhasil diupdate.');
    }

    // ── Ubah Status Cepat ────────────────────────────────────
    public function setStatus(int $id, string $status)
    {
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

        $labels = ['draft' => 'Draft', 'aktif' => 'Diaktifkan', 'tutup' => 'Ditutup'];
        return redirect()->to(base_url('master/periode'))
                         ->with('success', "Periode berhasil di-set: {$labels[$status]}.");
    }

    // ── Hapus ────────────────────────────────────────────────
    public function delete(int $id)
    {
        $periode = $this->periodeModel->find($id);
        if ($periode && $periode['status'] === 'aktif') {
            return redirect()->to(base_url('master/periode'))
                             ->with('error', 'Periode aktif tidak bisa dihapus. Tutup dulu.');
        }

        $this->periodeModel->delete($id);
        return redirect()->to(base_url('master/periode'))
                         ->with('success', 'Periode berhasil dihapus.');
    }
}