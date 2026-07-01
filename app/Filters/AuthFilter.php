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

        // Paksa pengguna mengganti password apabila flag must_change_password
        // aktif — berlaku untuk akun baru, akun yang di-reset Admin, maupun
        // akun yang dibuat lewat import massal Excel. Pengguna tidak dapat
        // mengakses halaman lain sampai password diubah sendiri lewat Profil.
        if (session()->get('must_change_password') == 1) {
            $currentPath = ltrim(parse_url(current_url(), PHP_URL_PATH), '/');
            $basePath    = ltrim(base_url(), '/');
            $relPath     = ltrim(str_replace($basePath, '', $currentPath), '/');

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