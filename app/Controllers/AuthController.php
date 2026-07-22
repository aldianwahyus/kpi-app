<?php

namespace App\Controllers;

use App\Models\UserModel;

class AuthController extends BaseController
{
    protected UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function login()
    {
        if (session()->get('logged_in')) {
            return redirect()->to(base_url('dashboard'));
        }

        // --- MULAI PENAMBAHAN CAPTCHA ---
        $angka1 = rand(1, 9);
        $angka2 = rand(1, 9);
        $jawaban_benar = $angka1 + $angka2;

        // Simpan jawaban ke session
        session()->set('captcha_answer', $jawaban_benar);

        // Kirim teks soal ke view
        $data = [
            'captcha_text' => "$angka1 + $angka2"
        ];
        // --- AKHIR PENAMBAHAN CAPTCHA ---

        return view('auth/login', $data);
    }

   public function doLogin()
    {
        $email = $this->request->getPost('email');
        
        // --- MULAI VALIDASI CAPTCHA ---
        $jawabanUser = $this->request->getPost('captcha');
        $jawabanBenar = session()->get('captcha_answer');

        // Jika tebakan kosong atau salah
        if ($jawabanUser === null || $jawabanUser == '' || $jawabanUser != $jawabanBenar) {
            // Hapus session captcha lama agar soal ter-reset saat view diload ulang
            session()->remove('captcha_answer');
            
            return redirect()->back()
                             ->with('error', 'Jawaban perhitungan keamanan salah. Silakan coba lagi.')
                             ->with('_ci_old_input', ['get' => [], 'post' => ['email' => $email]]);
        }

        // Jika benar, hapus session captcha agar tidak disalahgunakan
        session()->remove('captcha_answer');
        // --- AKHIR VALIDASI CAPTCHA ---


        $ip = $this->request->getIPAddress();

        // Cek rate limit (max 5 percobaan per 15 menit)
        $cache    = \Config\Services::cache();
        $cacheKey = 'login_attempt_' . md5($ip . $email);
        $attempts = $cache->get($cacheKey) ?? 0;

        if ($attempts >= 5) {
            return redirect()->back()
                             ->with('error',
                                 'Terlalu banyak percobaan login. Coba lagi dalam 15 menit.');
        }

        $password = $this->request->getPost('password');
        $user = $this->userModel->where('email', $email)
                                ->where('is_active', 1)
                                ->first();

        if (!$user || !password_verify($password, $user['password'])) {
            $cache->save($cacheKey, $attempts + 1, 900); // 15 menit

            log_message('warning', "Login gagal: $email dari IP: $ip");

            // Simpan hanya email ke flashdata (bukan withInput(), yang akan
            // menyimpan seluruh $_POST termasuk password dalam bentuk
            // plaintext ke session — risiko tanpa manfaat, karena view
            // login hanya pernah memanggil old('email'), tidak pernah
            // old('password')).
            return redirect()->back()
                             ->with('error', 'Email atau password salah.')
                             ->with('_ci_old_input', ['get' => [], 'post' => ['email' => $email]]);
        }

        // Login berhasil — hapus cache rate limit
        $cache->delete($cacheKey);

        session()->regenerate();
        session()->set([
            'logged_in'            => true,
            'user_id'              => $user['id'],
            'nama'                 => $user['nama'],
            'email'                => $user['email'],
            'role'                 => $user['role'],
            'pegawai_id'           => $user['pegawai_id'],
            'must_change_password' => (int)($user['must_change_password'] ?? 0),
            'login_time'           => time(),
            'last_activity'        => time(),
        ]);

        $this->userModel->update($user['id'], [
            'last_login' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to(base_url('dashboard'));
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to(base_url('auth/login'))->with('success', 'Anda telah logout.');
    }
}