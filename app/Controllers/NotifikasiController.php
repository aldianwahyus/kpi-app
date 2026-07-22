<?php
namespace App\Controllers;

use App\Models\UserModel;
use App\Models\PegawaiModel;
use App\Models\PeriodeModel;
use App\Models\PenilaianModel;
use App\Models\EmailLogModel;
use App\Services\EmailService;

class NotifikasiController extends BaseController
{
    protected EmailService   $emailService;
    protected UserModel      $userModel;
    protected PegawaiModel   $pegawaiModel;
    protected PeriodeModel   $periodeModel;
    protected PenilaianModel $penilaianModel;
    protected EmailLogModel  $emailLogModel;

    public function __construct()
    {
        $this->emailService   = new EmailService();
        $this->userModel      = new UserModel();
        $this->pegawaiModel   = new PegawaiModel();
        $this->periodeModel   = new PeriodeModel();
        $this->penilaianModel = new PenilaianModel();
        $this->emailLogModel  = new EmailLogModel();
    }

    public function index()
    {
        $check = $this->checkMenuAccess('notifikasi');
        if ($check !== true) return $check;

        $periodeAktif = $this->periodeModel->getAktif();

        $users = $this->userModel->db->table('users u')
            ->select('u.id, u.nama, u.email, u.role, u.is_active, u.pegawai_id,
                      p.jabatan, p.divisi_id, d.nama as divisi')
            ->join('pegawai p', 'p.id = u.pegawai_id', 'left')
            ->join('divisi d', 'd.id = p.divisi_id', 'left')
            ->whereIn('u.role', ['drafter','approver','hr'])
            ->where('u.is_active', 1)
            ->get()->getResultArray();

        // Histori 50 terakhir
        $histori = $this->emailLogModel->getAllOrdered(50);
        $statistik = $this->emailLogModel->getStatistik();

        return view('layouts/main', [
            'title'   => 'Notifikasi Email',
            'content' => view('notifikasi/_content', [
                'users'        => $users,
                'periodeAktif' => $periodeAktif,
                'histori'      => $histori,
                'statistik'    => $statistik,
            ]),
        ]);
    }

    public function sendReminder(int $userId)
    {
        $check = $this->checkMenuAccess('notifikasi');
        if ($check !== true) return $check;

        $periodeAktif = $this->periodeModel->getAktif();
        if (!$periodeAktif) {
            return redirect()->back()
                             ->with('error', 'Tidak ada periode aktif.');
        }

        $user = $this->userModel->db->table('users u')
            ->select('u.id, u.nama, u.email, u.pegawai_id, p.divisi_id')
            ->join('pegawai p', 'p.id = u.pegawai_id', 'left')
            ->where('u.id', $userId)
            ->get()->getRowArray();

        if (!$user || !$user['email']) {
            return redirect()->back()
                             ->with('error', 'User tidak valid atau tidak punya email.');
        }

        $belumDiisi = $this->hitungBelumDiisi(
            $user['divisi_id'], $periodeAktif['id']
        );
        $deadline = date('d F Y', strtotime($periodeAktif['tgl_selesai']));

        $result = $this->emailService->sendReminderInputKpi(
            $user['email'], $user['nama'],
            $periodeAktif['nama'], $deadline, $belumDiisi
        );

        // Catat log
        $this->logEmail(
            'reminder', $user['email'], $user['nama'],
            $result['subject'], $result['success'],
            $result['error'], $periodeAktif['id']
        );

        return redirect()->back()
                         ->with($result['success'] ? 'success' : 'error',
                             $result['success']
                                 ? "Reminder berhasil dikirim ke {$user['email']}"
                                 : "Gagal kirim ke {$user['email']}: {$result['error']}");
    }

    public function sendReminderAll()
    {
        $check = $this->checkMenuAccess('notifikasi');
        if ($check !== true) return $check;

        $periodeAktif = $this->periodeModel->getAktif();
        if (!$periodeAktif) {
            return redirect()->back()
                             ->with('error', 'Tidak ada periode aktif.');
        }

        $users = $this->userModel->db->table('users u')
            ->select('u.id, u.nama, u.email, u.pegawai_id, p.divisi_id')
            ->join('pegawai p', 'p.id = u.pegawai_id', 'left')
            ->whereIn('u.role', ['drafter','approver','hr'])
            ->where('u.is_active', 1)
            ->whereNotNull('u.email')
            ->get()->getResultArray();

        $berhasil = 0;
        $gagal    = 0;
        $deadline = date('d F Y', strtotime($periodeAktif['tgl_selesai']));

        foreach ($users as $user) {
            $belumDiisi = $this->hitungBelumDiisi(
                $user['divisi_id'], $periodeAktif['id']
            );

            $result = $this->emailService->sendReminderInputKpi(
                $user['email'], $user['nama'],
                $periodeAktif['nama'], $deadline, $belumDiisi
            );

            $this->logEmail(
                'reminder', $user['email'], $user['nama'],
                $result['subject'], $result['success'],
                $result['error'], $periodeAktif['id']
            );

            $result['success'] ? $berhasil++ : $gagal++;
        }

        return redirect()->back()
                         ->with('success',
                             "Reminder dikirim: <strong>$berhasil berhasil</strong>"
                             . ($gagal > 0 ? ", <strong>$gagal gagal</strong>" : ''));
    }

    // ── Halaman Histori Lengkap ───────────────────────────────
    public function histori()
    {
        $check = $this->checkMenuAccess('notifikasi');
        if ($check !== true) return $check;

        $statusFilter = $this->request->getGet('status') ?? '';
        $search       = $this->request->getGet('search')  ?? '';

        $builder = $this->emailLogModel->orderBy('created_at', 'DESC');

        if ($statusFilter) {
            $builder->where('status', $statusFilter);
        }
        if ($search) {
            $builder->groupStart()
                    ->like('to_email', $search)
                    ->orLike('to_nama', $search)
                    ->groupEnd();
        }

        $histori   = $builder->findAll(200);
        $statistik = $this->emailLogModel->getStatistik();

        return view('layouts/main', [
            'title'   => 'Histori Notifikasi Email',
            'content' => view('notifikasi/_histori', [
                'histori'      => $histori,
                'statistik'    => $statistik,
                'statusFilter' => $statusFilter,
                'search'       => $search,
            ]),
        ]);
    }

    private function logEmail(
        string $jenis, string $toEmail, string $toNama,
        string $subject, bool $success, ?string $error,
        ?int $periodeId
    ): void {
        $this->emailLogModel->insert([
            'jenis'         => $jenis,
            'to_email'      => $toEmail,
            'to_nama'       => $toNama,
            'subject'       => $subject,
            'status'        => $success ? 'terkirim' : 'gagal',
            'error_message' => $error,
            'periode_id'    => $periodeId,
            'sent_by'       => session()->get('user_id'),
            'sent_by_nama'  => session()->get('nama'),
        ]);
    }

    private function hitungBelumDiisi(?int $divisiId, int $periodeId): int
    {
        if (!$divisiId) return 0;

        $totalPegawai = (new PegawaiModel())
            ->where('divisi_id', $divisiId)
            ->where('is_active', 1)
            ->countAllResults();

        // Hitung hanya pegawai DI DIVISI INI yang sudah memiliki minimal
        // satu baris penilaian pada periode tersebut. Sebelumnya query ini
        // tidak memfilter berdasarkan divisi sama sekali, sehingga pegawai
        // yang sudah mengisi di divisi lain ikut terhitung untuk divisi ini.
        $sudahDiisi = $this->penilaianModel->db->table('penilaian p')
            ->select('p.pegawai_id')
            ->join('pegawai pg', 'pg.id = p.pegawai_id')
            ->where('p.periode_id', $periodeId)
            ->where('pg.divisi_id', $divisiId)
            ->groupBy('p.pegawai_id')
            ->get()->getNumRows();

        return max(0, $totalPegawai - $sudahDiisi);
    }
}