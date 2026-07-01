<?php
namespace App\Controllers;

use App\Models\UserModel;
use App\Models\PegawaiModel;

class UserController extends BaseController
{
    protected UserModel   $userModel;
    protected PegawaiModel $pegawaiModel;

    public function __construct()
    {
        $this->userModel    = new UserModel();
        $this->pegawaiModel = new PegawaiModel();
    }

    // ── Daftar User ──────────────────────────────────────────
    public function index(): string
    {
        if (!in_array(session()->get('role'), ['admin', 'hr'])) return $this->forbidden();

        $users = $this->userModel->db->table('users u')
            ->select('u.*, p.nama as nama_pegawai,
                      p.jabatan, p.nip,
                      d.nama as divisi')
            ->join('pegawai p', 'p.id = u.pegawai_id', 'left')
            ->join('divisi d', 'd.id = p.divisi_id', 'left')
            ->orderBy('u.role', 'ASC')
            ->orderBy('u.nama', 'ASC')
            ->get()->getResultArray();

        // Kelompokkan per role (Tetap dipertahankan agar tidak merusak View)
        $grouped = [];
        foreach ($users as $u) {
            $grouped[$u['role']][] = $u;
        }

        return view('layouts/main', [
            'title'   => 'Manajemen User',
            'content' => view('user/_content', [
                'grouped'      => $grouped,
                'total_users'  => count($users),
            ]),
        ]);
    }

    // ── Form Tambah ──────────────────────────────────────────
    public function create(): string
    {
        if (!in_array(session()->get('role'), ['admin', 'hr'])) return $this->forbidden();

        return view('layouts/main', [
            'title'   => 'Tambah User',
            'content' => view('user/_form', [
                'user'        => null,
                'action'      => base_url('master/users/store'),
                'pegawai_dd'  => $this->getPegawaiDropdown(),
            ]),
        ]);
    }

    // ── Simpan Tambah ────────────────────────────────────────
    public function store()
    {
        if (!in_array(session()->get('role'), ['admin', 'hr'])) return $this->forbidden();

        if (!$this->validate([
            'nama'     => 'required',
            'email'    => 'required|valid_email|is_unique[users.email]',
            'password' => 'required|min_length[6]',
            'role'     => 'required|in_list[admin,hr,drafter,approver,pegawai]',
        ])) {
            return redirect()->back()->withInput()
                             ->with('errors', $this->validator->getErrors());
        }

        $this->userModel->insert([
            'pegawai_id'           => $this->request->getPost('pegawai_id') ?: null,
            'nama'                 => $this->request->getPost('nama'),
            'email'                => $this->request->getPost('email'),
            'password'             => password_hash(
                $this->request->getPost('password'),
                PASSWORD_DEFAULT
            ),
            'must_change_password' => 1,
            'role'                 => $this->request->getPost('role'),
            'is_active'            => 1,
        ]);

        return redirect()->to(base_url('master/users'))
                         ->with('success', 'User berhasil ditambahkan.');
    }

    // ── Form Edit ────────────────────────────────────────────
    public function edit(int $id): string
    {
        if (!in_array(session()->get('role'), ['admin', 'hr'])) return $this->forbidden();

        $user = $this->userModel->find($id);
        if (!$user) {
            return redirect()->to(base_url('master/users'))
                             ->with('error', 'User tidak ditemukan.');
        }
        unset($user['password']);

        return view('layouts/main', [
            'title'   => 'Edit User',
            'content' => view('user/_form', [
                'user'       => $user,
                'action'     => base_url("master/users/update/$id"),
                'pegawai_dd' => $this->getPegawaiDropdown(),
            ]),
        ]);
    }

    // ── Update ───────────────────────────────────────────────
    public function update(int $id)
    {
        if (!in_array(session()->get('role'), ['admin', 'hr'])) return $this->forbidden();

        $emailRule = "required|valid_email|is_unique[users.email,id,$id]";

        if (!$this->validate([
            'nama'  => 'required',
            'email' => $emailRule,
            'role'  => 'required|in_list[admin,hr,drafter,approver,pegawai]',
        ])) {
            return redirect()->back()->withInput()
                             ->with('errors', $this->validator->getErrors());
        }

        $data = [
            'pegawai_id' => $this->request->getPost('pegawai_id') ?: null,
            'nama'       => $this->request->getPost('nama'),
            'email'      => $this->request->getPost('email'),
            'role'       => $this->request->getPost('role'),
            // Tambahkan ini jika di form ada input status aktif
            'is_active'  => $this->request->getPost('is_active') !== null ? (int)$this->request->getPost('is_active') : 1,
        ];

        // Update password hanya jika diisi
        $pwd = trim($this->request->getPost('password'));
        if ($pwd) {
            if (strlen($pwd) < 6) {
                return redirect()->back()->withInput()
                                 ->with('error', 'Password minimal 6 karakter.');
            }
            $data['password']             = password_hash($pwd, PASSWORD_DEFAULT);
            $data['must_change_password'] = 1;
        }

        $this->userModel->update($id, $data);

        return redirect()->to(base_url('master/users'))
                         ->with('success', 'User berhasil diupdate.');
    }

    // ── Toggle Aktif ─────────────────────────────────────────
    public function toggle(int $id)
    {
        if (!in_array(session()->get('role'), ['admin', 'hr'])) return $this->forbidden();

        // Jangan nonaktifkan diri sendiri
        if ($id == session()->get('user_id')) {
            return redirect()->to(base_url('master/users'))
                             ->with('error', 'Tidak bisa menonaktifkan akun sendiri.');
        }

        $user = $this->userModel->find($id);
        if ($user) {
            $this->userModel->update($id, [
                'is_active' => $user['is_active'] ? 0 : 1,
            ]);
        }

        return redirect()->to(base_url('master/users'))
                         ->with('success', 'Status user diubah.');
    }

    // ── Reset Password ───────────────────────────────────────
    public function resetPassword(int $id)
    {
        if (!in_array(session()->get('role'), ['admin', 'hr'])) return $this->forbidden();

        $user = $this->userModel->find($id);
        if (!$user) {
            return redirect()->to(base_url('master/users'))
                             ->with('error', 'User tidak ditemukan.');
        }

        $defaultPwd = 'pegawai123';
        $this->userModel->update($id, [
            'password'             => password_hash($defaultPwd, PASSWORD_DEFAULT),
            'must_change_password' => 1,
        ]);

        return redirect()->to(base_url('master/users'))
                         ->with('success',
                             "Password direset ke: <strong>$defaultPwd</strong>. "
                             . "Pengguna wajib mengganti password ini saat login berikutnya.");
    }

    // ── Hapus ────────────────────────────────────────────────
    public function delete(int $id)
    {
        if (!in_array(session()->get('role'), ['admin', 'hr'])) return $this->forbidden();

        if ($id == session()->get('user_id')) {
            return redirect()->to(base_url('master/users'))
                             ->with('error', 'Tidak bisa menghapus akun sendiri.');
        }

        $this->userModel->delete($id);

        return redirect()->to(base_url('master/users'))
                         ->with('success', 'User berhasil dihapus.');
    }

    // ── Profil Sendiri ───────────────────────────────────────
    public function profil(): string
    {
        $userId = session()->get('user_id');
        $user   = $this->userModel->find($userId);
        if ($user) unset($user['password']);

        return view('layouts/main', [
            'title'   => 'Profil Saya',
            'content' => view('user/_profil', [
                'user'   => $user,
                'action' => base_url('profil/update'),
            ]),
        ]);
    }

    public function profilUpdate()
    {
        $userId = session()->get('user_id');

        if (!$this->validate([
            'nama'  => 'required',
            'email' => "required|valid_email|is_unique[users.email,id,$userId]",
        ])) {
            return redirect()->back()->withInput()
                             ->with('errors', $this->validator->getErrors());
        }

        $data = [
            'nama'  => $this->request->getPost('nama'),
            'email' => $this->request->getPost('email'),
        ];

        $pwd = trim($this->request->getPost('password'));
        if ($pwd) {
            if (strlen($pwd) < 6) {
                return redirect()->back()
                                 ->with('error', 'Password minimal 6 karakter.');
            }
            $data['password']             = password_hash($pwd, PASSWORD_DEFAULT);
            // Hanya saat pemilik akun sendiri yang mengganti password,
            // flag wajib ganti password dihapus. Admin mengatur password
            // tidak pernah menghapus flag ini, sesuai kebijakan yang
            // ditetapkan — hanya pemilik akun yang boleh tahu password
            // aslinya secara permanen.
            $data['must_change_password'] = 0;
        }

        $this->userModel->update($userId, $data);

        // Update session
        session()->set('nama',  $data['nama']);
        session()->set('email', $data['email']);
        if (isset($data['must_change_password'])) {
            session()->set('must_change_password', $data['must_change_password']);
        }

        return redirect()->to(base_url('profil'))
                         ->with('success', 'Profil berhasil diupdate.');
    }

    // ── Helper ───────────────────────────────────────────────
    private function getPegawaiDropdown(): array
    {
        $rows = $this->pegawaiModel->where('is_active', 1)
                                   ->orderBy('nama', 'ASC')
                                   ->findAll();
        $result = ['' => '-- Tidak terhubung ke pegawai --'];
        foreach ($rows as $r) {
            $result[$r['id']] = $r['nama'] . ($r['nip'] ? " ({$r['nip']})" : '');
        }
        return $result;
    }
}