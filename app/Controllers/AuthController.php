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
            return redirect()->to('/dashboard');
        }
        return view('auth/login');
    }

    public function doLogin()
    {
        $email = $this->request->getPost('email');
        $ip    = $this->request->getIPAddress();

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

            return redirect()->back()
                            ->with('error', 'Email atau password salah.')
                            ->withInput();
        }

        // Login berhasil — hapus cache rate limit
        $cache->delete($cacheKey);

        session()->regenerate();
        session()->set([
            'logged_in'  => true,
            'user_id'    => $user['id'],
            'nama'       => $user['nama'],
            'email'      => $user['email'],
            'role'       => $user['role'],
            'pegawai_id' => $user['pegawai_id'],
            'login_time' => time(),
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