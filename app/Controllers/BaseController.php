<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseController extends Controller
{
    protected $helpers = [];

    public function initController(
        RequestInterface  $request,
        ResponseInterface $response,
        LoggerInterface   $logger
    ) {
        parent::initController($request, $response, $logger);
    }

    /**
     * Cek apakah user boleh akses data pegawai tertentu
     * berdasarkan role & divisi
     */
    protected function canAccessPegawai(int $pegawaiId): bool
    {
        $role        = session()->get('role');
        $myPegawaiId = session()->get('pegawai_id');

        // Admin & HR bisa akses semua
        if (in_array($role, ['admin', 'hr'])) {
            return true;
        }

        // Drafter & Approver hanya bisa akses pegawai di divisi yang sama
        if (in_array($role, ['drafter', 'approver'])) {
            if (!$myPegawaiId) return false;

            $pegawaiModel = new \App\Models\PegawaiModel();
            $myDivisi     = $pegawaiModel->find($myPegawaiId)['divisi_id'] ?? null;
            $targetDivisi = $pegawaiModel->find($pegawaiId)['divisi_id']   ?? null;

            return $myDivisi && $targetDivisi && $myDivisi === $targetDivisi;
        }

        // Pegawai hanya bisa akses data dirinya sendiri
        if ($role === 'pegawai') {
            return $myPegawaiId == $pegawaiId;
        }

        return false;
    }

    /**
     * Response 403 Forbidden
     */
    protected function forbidden(string $message = 'Anda tidak memiliki akses ke data ini.')
    {
        if ($this->request->isAJAX()) {
            return $this->response->setStatusCode(403)
                                  ->setJSON(['error' => $message]);
        }
        return redirect()->to(base_url('dashboard'))
                         ->with('error', $message);
    }

    protected function checkMenuAccess(string $kodeMenu)
    {
        $role = session()->get('role');
        if ($role === 'admin') return true;

        $permModel = new \App\Models\RolePermissionModel();
        if (!$permModel->canAccess($role, $kodeMenu)) {
            return $this->forbidden('Anda tidak memiliki akses ke menu ini.');
        }
        return true;
    }

    /**
     * Cek hak akses TULIS (simpan/ubah/hapus/import/copy) untuk suatu menu.
     * Dipakai di aksi yang mengubah data, terpisah dari checkMenuAccess()
     * yang hanya membatasi akses lihat — supaya role dengan "Bisa Lihat"
     * saja (can_view=1, can_edit=0) tidak bisa menyimpan/mengubah/menghapus
     * data lewat menu tersebut.
     */
    protected function checkMenuEdit(string $kodeMenu)
    {
        $role = session()->get('role');
        if ($role === 'admin') return true;

        $permModel = new \App\Models\RolePermissionModel();
        if (!$permModel->canEdit($role, $kodeMenu)) {
            return $this->forbidden('Anda hanya memiliki akses lihat untuk menu ini.');
        }
        return true;
    }
}