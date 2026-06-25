<?php
namespace App\Controllers;

use App\Models\DraftUlangRequestModel;
use App\Models\PenilaianModel;
use App\Models\PegawaiModel;
use App\Models\PeriodeModel;
use App\Services\AuditService;

class DraftUlangController extends BaseController
{
    protected DraftUlangRequestModel $requestModel;
    protected PenilaianModel         $penilaianModel;
    protected PegawaiModel           $pegawaiModel;
    protected PeriodeModel           $periodeModel;
    protected AuditService           $auditService;

    public function __construct()
    {
        $this->requestModel   = new DraftUlangRequestModel();
        $this->penilaianModel = new PenilaianModel();
        $this->pegawaiModel   = new PegawaiModel();
        $this->periodeModel   = new PeriodeModel();
        $this->auditService   = new AuditService();
    }

    // ── Approver: Ajukan permintaan draft ulang per pegawai ──
    public function requestPegawai(int $pegawaiId)
    {
        if (session()->get('role') !== 'approver') {
            return redirect()->back()
                             ->with('error', 'Hanya Approver yang dapat mengajukan draft ulang.');
        }

        $periodeAktif = $this->periodeModel->getAktif();
        if (!$periodeAktif) {
            return redirect()->back()->with('error', 'Tidak ada periode aktif.');
        }

        // Pastikan status memang approved
        $record = $this->penilaianModel
            ->where('pegawai_id', $pegawaiId)
            ->where('periode_id', $periodeAktif['id'])
            ->where('status', 'approved')
            ->first();

        if (!$record) {
            return redirect()->back()
                             ->with('error', 'Penilaian belum berstatus approved.');
        }

        if ($this->requestModel->hasPendingRequest($pegawaiId, $periodeAktif['id'], 'pegawai')) {
            return redirect()->back()
                             ->with('error', 'Sudah ada permintaan draft ulang yang menunggu konfirmasi Admin.');
        }

        $alasan = trim($this->request->getPost('alasan') ?? '');
        if (empty($alasan)) {
            return redirect()->back()
                             ->with('error', 'Alasan draft ulang wajib diisi.');
        }

        $this->requestModel->insert([
            'tipe'              => 'pegawai',
            'pegawai_id'        => $pegawaiId,
            'periode_id'        => $periodeAktif['id'],
            'alasan'            => $alasan,
            'status'            => 'pending',
            'requested_by'      => session()->get('user_id'),
            'requested_by_nama' => session()->get('nama'),
            'requested_at'      => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to(base_url("penilaian/form/$pegawaiId"))
                         ->with('success',
                             'Permintaan draft ulang berhasil diajukan. Menunggu konfirmasi Admin.');
    }

    // ── Approver: Ajukan permintaan draft ulang massal per periode ──
    public function requestPeriode()
    {
        if (session()->get('role') !== 'approver') {
            return redirect()->back()
                             ->with('error', 'Hanya Approver yang dapat mengajukan draft ulang.');
        }

        $periodeAktif = $this->periodeModel->getAktif();
        if (!$periodeAktif) {
            return redirect()->back()->with('error', 'Tidak ada periode aktif.');
        }

        if ($this->requestModel->hasPendingRequest(null, $periodeAktif['id'], 'periode')) {
            return redirect()->back()
                             ->with('error', 'Sudah ada permintaan draft ulang periode ini yang menunggu konfirmasi.');
        }

        $alasan = trim($this->request->getPost('alasan') ?? '');
        if (empty($alasan)) {
            return redirect()->back()
                             ->with('error', 'Alasan draft ulang wajib diisi.');
        }

        $this->requestModel->insert([
            'tipe'              => 'periode',
            'pegawai_id'        => null,
            'periode_id'        => $periodeAktif['id'],
            'alasan'            => $alasan,
            'status'            => 'pending',
            'requested_by'      => session()->get('user_id'),
            'requested_by_nama' => session()->get('nama'),
            'requested_at'      => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to(base_url('draft-ulang'))
                         ->with('success',
                             'Permintaan draft ulang periode berhasil diajukan. Menunggu konfirmasi Admin.');
    }

    // ── Admin: Daftar permintaan draft ulang ─────────────────
    public function index(): string
    {
        if (session()->get('role') !== 'admin') {
            return redirect()->to(base_url('dashboard'))
                             ->with('error', 'Hanya Admin yang dapat mengakses halaman ini.');
        }

        return view('layouts/main', [
            'title'   => 'Permintaan Draft Ulang',
            'content' => view('draft_ulang/_content', [
                'pending' => $this->requestModel->getPending(),
                'semua'   => $this->requestModel->getAllWithDetail(),
            ]),
        ]);
    }

    // ── Admin: Konfirmasi draft ulang ────────────────────────
    public function confirm(int $requestId)
    {
        if (session()->get('role') !== 'admin') {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        $req = $this->requestModel->find($requestId);
        if (!$req || $req['status'] !== 'pending') {
            return redirect()->back()->with('error', 'Permintaan tidak valid.');
        }

        $catatan = trim($this->request->getPost('catatan_admin') ?? '');

        // Update status request
        $this->requestModel->update($requestId, [
            'status'            => 'dikonfirmasi',
            'confirmed_by'      => session()->get('user_id'),
            'confirmed_by_nama' => session()->get('nama'),
            'confirmed_at'      => date('Y-m-d H:i:s'),
            'catatan_admin'     => $catatan,
        ]);

        // Tentukan target penilaian yang akan di-draft ulang
        $query = $this->penilaianModel
            ->where('periode_id', $req['periode_id'])
            ->where('status', 'approved');

        if ($req['tipe'] === 'pegawai') {
            $query->where('pegawai_id', $req['pegawai_id']);
        }

        $records = $query->findAll();

        foreach ($records as $record) {
            $this->penilaianModel->update($record['id'], [
                'status'       => 'draft',
                'approved_by'  => null,
                'approved_at'  => null,
                'submitted_at' => null,
                'reject_note'  => null,
            ]);

            // Catat audit per record
            $this->auditService->logDraftUlang(
                $record['id'],
                $req['alasan'],
                $req['requested_by_nama'],
                session()->get('nama')
            );
        }

        $jumlah = count($records);
        $target = $req['tipe'] === 'pegawai'
            ? "1 pegawai"
            : "$jumlah penilaian (seluruh periode)";

        return redirect()->to(base_url('draft-ulang'))
                         ->with('success',
                             "Draft ulang dikonfirmasi. $target berhasil dikembalikan ke status Draft.");
    }

    // ── Admin: Tolak permintaan ───────────────────────────────
    public function decline(int $requestId)
    {
        if (session()->get('role') !== 'admin') {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        $catatan = trim($this->request->getPost('catatan_admin') ?? '');

        $this->requestModel->update($requestId, [
            'status'            => 'ditolak',
            'confirmed_by'      => session()->get('user_id'),
            'confirmed_by_nama' => session()->get('nama'),
            'confirmed_at'      => date('Y-m-d H:i:s'),
            'catatan_admin'     => $catatan,
        ]);

        return redirect()->to(base_url('draft-ulang'))
                         ->with('success', 'Permintaan draft ulang ditolak.');
    }
}