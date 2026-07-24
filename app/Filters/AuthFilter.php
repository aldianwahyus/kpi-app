<?php
namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (!session()->get('logged_in')) {
            if ($request->isAJAX()) {
                return service('response')->setStatusCode(401)
                    ->setJSON(['error' => 'Unauthorized']);
            }
            return redirect()->to(base_url('auth/login'))
                             ->with('error', 'Silakan login terlebih dahulu.');
        }

        // Pengecekan timeout inaktivitas secara eksplisit, sebagai lapisan
        // pertahanan tambahan di atas mekanisme garbage collection sesi
        // bawaan framework (yang murni berbasis waktu pembaruan file/data
        // sesi, bukan pemeriksaan logout paksa). Sesi akan dianggap
        // berakhir apabila tidak ada aktivitas selama durasi yang sama
        // dengan Config\Session::$expiration.
        $lastActivity = session()->get('last_activity');
        $batasInaktif = (new \Config\Session())->expiration;

        if ($lastActivity && (time() - $lastActivity) > $batasInaktif) {
            session()->destroy();

            if ($request->isAJAX()) {
                return service('response')->setStatusCode(401)
                    ->setJSON(['error' => 'Sesi telah berakhir karena tidak ada aktivitas.']);
            }
            return redirect()->to(base_url('auth/login'))
                             ->with('error', 'Sesi Anda telah berakhir karena tidak ada aktivitas. Silakan login kembali.');
        }

        session()->set('last_activity', time());

        // Sinkronisasi status akun & role dari database — mencegah user
        // yang baru dinonaktifkan atau diganti role-nya oleh Admin lewat
        // Manajemen User tetap beroperasi dengan hak akses lama selama
        // sesinya masih hidup. Hanya diperiksa jika baris user-nya memang
        // ditemukan — sengaja TIDAK menghancurkan sesi bila baris tidak
        // ditemukan (mis. skenario pengujian yang tidak menyiapkan baris
        // 'users' sungguhan), karena pada penggunaan nyata user_id sesi
        // selalu berasal dari baris 'users' yang valid saat login.
        $userId = session()->get('user_id');
        if ($userId) {
            $userRow = (new \App\Models\UserModel())
                ->select('is_active, role')
                ->find($userId);

            if ($userRow) {
                if ((int) $userRow['is_active'] !== 1) {
                    session()->destroy();

                    if ($request->isAJAX()) {
                        return service('response')->setStatusCode(401)
                            ->setJSON(['error' => 'Akun Anda telah dinonaktifkan.']);
                    }
                    return redirect()->to(base_url('auth/login'))
                                     ->with('error', 'Akun Anda telah dinonaktifkan. Silakan hubungi Administrator.');
                }

                if ($userRow['role'] !== session()->get('role')) {
                    session()->set('role', $userRow['role']);
                }
            }
        }

        // Paksa pengguna mengganti password apabila flag must_change_password
        // aktif — berlaku untuk akun baru, akun yang di-reset Admin, maupun
        // akun yang dibuat lewat import massal Excel. Pengguna tidak dapat
        // mengakses halaman lain sampai password diubah sendiri lewat Profil.
        if (session()->get('must_change_password') == 1) {
            // uri_string() mengembalikan path relatif dari root aplikasi
            // (misal 'profil', 'dashboard', 'penilaian/form/1') — lebih
            // andal dari parse_url() + str_replace() yang sebelumnya
            // gagal karena base_url() menyertakan skema (http://) sedangkan
            // PHP_URL_PATH hanya mengembalikan path, sehingga str_replace
            // tidak pernah menemukan kecocokan dan loop redirect terjadi.
            $relPath = ltrim(uri_string(), '/');

            $allowed = ['profil', 'profil/update', 'auth/logout'];
            if (!in_array($relPath, $allowed)) {
                return redirect()->to(base_url('profil'))
                                 ->with('warning',
                                     'Anda wajib mengganti password sebelum dapat '
                                     . 'menggunakan aplikasi. Silakan perbarui password '
                                     . 'Anda di bawah ini.');
            }
        }

        if ($arguments) {
            $userRole = session()->get('role');
            if (!in_array($userRole, $arguments)) {
                return redirect()->to(base_url('dashboard'))
                                 ->with('error', 'Anda tidak memiliki akses ke halaman ini.');
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}