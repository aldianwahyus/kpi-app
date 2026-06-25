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