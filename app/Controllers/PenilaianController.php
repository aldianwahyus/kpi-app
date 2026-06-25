<?php

namespace App\Controllers;

use App\Models\PenilaianModel;
use App\Models\PegawaiModel;
use App\Models\KpiPegawaiModel;
use App\Models\PeriodeModel;
use App\Models\AuditLogModel;
use App\Services\KpiCalculationService;
use App\Services\AuditService;

class PenilaianController extends BaseController
{
    protected PenilaianModel        $penilaianModel;
    protected PegawaiModel          $pegawaiModel;
    protected KpiPegawaiModel       $kpiPegawaiModel;
    protected PeriodeModel          $periodeModel;
    protected KpiCalculationService $calculator;
    protected AuditService          $auditService;
    protected AuditLogModel         $auditLogModel;

    public function __construct()
    {
        $this->penilaianModel  = new PenilaianModel();
        $this->pegawaiModel    = new PegawaiModel();
        $this->kpiPegawaiModel = new KpiPegawaiModel();
        $this->periodeModel    = new PeriodeModel();
        $this->calculator      = new KpiCalculationService();
        $this->auditService    = new AuditService();
        $this->auditLogModel   = new AuditLogModel();
    }

    public function index(): string
    {   
        $periodeAktif = $this->periodeModel->getAktif();
        $role         = session()->get('role');
        $userPegawaiId= session()->get('pegawai_id');

        $pegawai = [];
        if ($role === 'kepala_unit' && $userPegawaiId) {
            $userPegawai = $this->pegawaiModel->find($userPegawaiId);
            $divisiId    = $userPegawai['divisi_id'] ?? null;
            if ($divisiId) {
                $all = $this->pegawaiModel->getAllWithDivisi();
                $pegawai = array_values(array_filter($all, fn($p) => $p['divisi_id'] == $divisiId && $p['id'] != $userPegawaiId));
            }
        } else {
            $pegawai = $this->pegawaiModel->getAllWithDivisi();
        }

        $rekap = [];
        if ($periodeAktif) {
            foreach ($this->penilaianModel->getRekapKombinasi($periodeAktif['id']) as $row) {
                $rekap[$row['pegawai_id']] = $row;
            }
        }

        return view('layouts/main', [
            'title'   => 'Input Penilaian KPI',
            'content' => view('penilaian/_content', ['pegawai' => $pegawai, 'rekap' => $rekap, 'periodeAktif' => $periodeAktif, 'role' => $role]),
        ]);
    }

    public function form(int $pegawaiId)
    {
        if (!$this->canAccessPegawai($pegawaiId)) return $this->forbidden();

        $periodeAktif = $this->periodeModel->getAktif();
        if (!$periodeAktif) return redirect()->to(base_url('penilaian'))->with('error', 'Tidak ada periode aktif.');

        $pegawai = $this->pegawaiModel->find($pegawaiId);
        $kpiList = $this->kpiPegawaiModel->getByPegawai($pegawaiId);
        
        if (empty($kpiList)) return redirect()->to(base_url('penilaian'))->with('error', "KPI untuk {$pegawai['nama']} belum di-setup.");

        $existing   = $this->penilaianModel->getIndexedByKpi($pegawaiId, $periodeAktif['id']);
        $nilaiAkhir = $this->penilaianModel->getNilaiAkhir($pegawaiId, $periodeAktif['id']);
        $approval   = $this->getStatusApproval($pegawaiId, $periodeAktif['id']);

        $kpiGrouped = [];
        foreach ($kpiList as $kpi) { $kpiGrouped[$kpi['perspektif']][] = $kpi; }

        return view('layouts/main', [
            'title'   => 'Input Penilaian — ' . $pegawai['nama'],
            'content' => view('penilaian/_form', [
                'pegawai' => $pegawai, 'kpiList' => $kpiList, 'kpiGrouped' => $kpiGrouped,
                'existing' => $existing, 'periodeAktif' => $periodeAktif, 'nilaiAkhir' => $nilaiAkhir,
                'statusApproval' => $approval, 'rejectNote' => $approval['reject_note'],
                'histori' => $this->auditLogModel->getPenilaianHistory($pegawaiId, $periodeAktif['id']),
                'role' => session()->get('role')
            ]),
        ]);
    }

    public function store(int $pegawaiId)
    {
        if (!$this->canAccessPegawai($pegawaiId)) return $this->forbidden();

        $periodeAktif = $this->periodeModel->getAktif();
        if (!$periodeAktif) {
            return redirect()->to(base_url('penilaian'))->with('error', 'Tidak ada periode aktif.');
        }

        $role = session()->get('role');

        // Cek status saat ini
        $statusCheck = $this->getStatusApproval($pegawaiId, $periodeAktif['id']);
        $currentStatus = $statusCheck['current'];

        // Drafter hanya bisa edit saat draft/rejected
        if ($role === 'drafter' && !in_array($currentStatus, ['draft','rejected'])) {
            return redirect()->to(base_url("penilaian/form/$pegawaiId"))
                            ->with('error', 'Penilaian sudah disubmit, tidak bisa diedit oleh Drafter.');
        }

        // Approver bisa edit hanya saat draft (bukan submitted/approved/rejected)
        if ($role === 'approver' && $currentStatus === 'approved') {
            return redirect()->to(base_url("penilaian/form/$pegawaiId"))
                            ->with('error', 'Penilaian sudah approved. Ajukan Draft Ulang ke Admin terlebih dahulu.');
        }

        $kpiList   = $this->kpiPegawaiModel->getByPegawai($pegawaiId);
        $realisasi = $this->request->getPost('realisasi') ?? [];
        $catatan   = $this->request->getPost('catatan')   ?? [];

        $saved = 0;
        foreach ($kpiList as $kpi) {
            $kpiId = (int)$kpi['kpi_id'];
            $real  = $realisasi[$kpiId] ?? null;

            if ($real === null || $real === '') continue;

            $real     = (float)$real;
            $target   = (float)($kpi['target'] ?? 100);
            $polarity = $kpi['polarity'] ?? 'max';
            $isCapped = (bool)($kpi['is_capped'] ?? true);

            $skor       = $this->calculator->hitungSkorCapaian($real, $target, $polarity, $isCapped);
            $kontribusi = $this->calculator->hitungKontribusi($skor, (float)$kpi['bobot']);

            // 1. Ambil data lama untuk pembanding di Histori Log
            $oldData = $this->penilaianModel
                ->where('pegawai_id', $pegawaiId)
                ->where('kpi_id', $kpiId)
                ->where('periode_id', $periodeAktif['id'])
                ->first();

            $newData = [
                'realisasi'        => $real,
                'skor'             => round($skor, 2),
                'nilai_kontribusi' => round($kontribusi, 2),
                'catatan'          => $catatan[$kpiId] ?? null,
                'input_by'         => session()->get('user_id'),
                'status'           => 'draft',
            ];

            // 2. Simpan Data ke Database
            $this->penilaianModel->upsert($pegawaiId, $kpiId, $periodeAktif['id'], $newData);

            // 3. Catat Perubahan ke Tabel Histori (Audit Log)
            $action = $oldData ? 'update' : 'create';
            
            // Dapatkan ID record yang baru saja disimpan
            $recordId = $oldData['id'] ?? null;
            if (!$recordId) {
                $savedRecord = $this->penilaianModel
                    ->where('pegawai_id', $pegawaiId)->where('kpi_id', $kpiId)->where('periode_id', $periodeAktif['id'])->first();
                $recordId = $savedRecord['id'] ?? null;
            }

            if ($recordId) {
                $keterangan = $oldData ? "Update Realisasi menjadi $real" : "Input Realisasi awal $real";
                
                $this->auditService->log(
                    'penilaian',
                    $recordId,
                    $action,
                    $oldData ? ['realisasi' => $oldData['realisasi'], 'skor' => $oldData['skor']] : null,
                    ['realisasi' => $newData['realisasi'], 'skor' => $newData['skor']],
                    $keterangan
                );
            }

            $saved++;
        }

        return redirect()->to(base_url("penilaian/form/$pegawaiId"))
                        ->with('success', "Penilaian berhasil disimpan! ($saved KPI)");
    }

    public function submit(int $pegawaiId)
    {
        $periode = $this->periodeModel->getAktif();
        $record  = $this->penilaianModel->where(['pegawai_id' => $pegawaiId, 'periode_id' => $periode['id']])->first();

        if ($record) {
            $this->penilaianModel->db->table('penilaian')
                ->where(['pegawai_id' => $pegawaiId, 'periode_id' => $periode['id']])
                ->update(['status' => 'submitted', 'submitted_at' => date('Y-m-d H:i:s')]);

            // Catat log
            $this->auditService->log('penilaian', $record['id'], 'submit', ['status' => $record['status']], ['status' => 'submitted'], 'Penilaian disubmit untuk approval');
        }

        return redirect()->to(base_url("penilaian/form/$pegawaiId"))->with('success', 'Berhasil disubmit.');
    }

    public function approve(int $pegawaiId)
    {
        $periode = $this->periodeModel->getAktif();
        $record  = $this->penilaianModel->where(['pegawai_id' => $pegawaiId, 'periode_id' => $periode['id']])->first();

        if ($record) {
            $this->penilaianModel->db->table('penilaian')
                ->where(['pegawai_id' => $pegawaiId, 'periode_id' => $periode['id']])
                ->update(['status' => 'approved', 'approved_by' => session()->get('user_id'), 'approved_at' => date('Y-m-d H:i:s'), 'reject_note' => null]);

            // Catat log
            $this->auditService->log('penilaian', $record['id'], 'approve', ['status' => $record['status']], ['status' => 'approved'], 'Penilaian disetujui');
        }

        return redirect()->to(base_url("penilaian/form/$pegawaiId"))->with('success', 'Berhasil diapprove.');
    }

    public function reject(int $pegawaiId)
    {
        $note = trim($this->request->getPost('reject_note') ?? '');
        if (empty($note)) return redirect()->back()->with('error', 'Catatan wajib diisi.');

        $periode = $this->periodeModel->getAktif();
        $record  = $this->penilaianModel->where(['pegawai_id' => $pegawaiId, 'periode_id' => $periode['id']])->first();

        if ($record) {
            $this->penilaianModel->db->table('penilaian')
                ->where(['pegawai_id' => $pegawaiId, 'periode_id' => $periode['id']])
                ->update(['status' => 'rejected', 'reject_note' => $note]);

            // Catat log
            $this->auditService->log('penilaian', $record['id'], 'reject', ['status' => $record['status']], ['status' => 'rejected', 'reject_note' => $note], "Penilaian ditolak: $note");
        }

        return redirect()->to(base_url("penilaian/form/$pegawaiId"))->with('error', "Ditolak: $note");
    }

    public function ajaxHitung()
    {
        $pegawaiId = (int)$this->request->getPost('pegawai_id');
        $kpiId     = (int)$this->request->getPost('kpi_id');
        $realisasi = $this->request->getPost('realisasi');

        // 1. Ambil list KPI pegawai
        $kpiList = $this->kpiPegawaiModel->getByPegawai($pegawaiId);

        // 2. Cari KPI yang sedang diketik (menggunakan foreach standar CI4)
        $currentKpi = null;
        foreach ($kpiList as $item) {
            if ($item['kpi_id'] == $kpiId) {
                $currentKpi = $item;
                break;
            }
        }

        // Jika tidak ketemu, hentikan
        if (!$currentKpi) {
            return $this->response->setJSON(['valid' => false, 'message' => 'KPI tidak ditemukan']);
        }

        // 3. Konversi tipe data untuk dikalkulasi
        $realisasi = (float)$realisasi;
        $target    = (float)($currentKpi['target'] ?? 100);
        $bobot     = (float)($currentKpi['bobot'] ?? 0);
        $polarity  = $currentKpi['polarity'] ?? 'max';
        $isCapped  = isset($currentKpi['is_capped']) ? (bool)$currentKpi['is_capped'] : true;

        // 4. Lakukan perhitungan menggunakan Service
        $skor       = $this->calculator->hitungSkorCapaian($realisasi, $target, $polarity, $isCapped);
        $kontribusi = $this->calculator->hitungKontribusi($skor, $bobot);

        // 5. Tentukan warna badge
        $bootstrapColor = $skor >= 91 ? 'success' : ($skor >= 81 ? 'primary' : ($skor >= 71 ? 'warning' : 'danger'));

        // 6. Kembalikan response JSON beserta token CSRF baru
        return $this->response->setJSON([
            'valid'      => true,
            'skor'       => round($skor, 2),
            'kontribusi' => round($kontribusi, 2),
            'color'      => $bootstrapColor,
            'csrf_hash'  => csrf_hash()
        ]);
    }

    protected function getStatusApproval(int $pegawaiId, int $periodeId): array
    {
        $rows = $this->penilaianModel->db->table('penilaian')
            ->select('status, COUNT(*) as jumlah')
            ->where('pegawai_id', $pegawaiId)
            ->where('periode_id', $periodeId)
            ->groupBy('status')
            ->get()->getResultArray();

        $result = ['draft'=>0,'submitted'=>0,'approved'=>0,'rejected'=>0];
        foreach ($rows as $row) {
            $result[$row['status']] = (int)$row['jumlah'];
        }

        if ($result['approved'] > 0 && $result['draft']==0 && $result['submitted']==0) {
            $result['current'] = 'approved';
        } elseif ($result['rejected'] > 0) {
            $result['current'] = 'rejected';
        } elseif ($result['submitted'] > 0) {
            $result['current'] = 'submitted';
        } else {
            $result['current'] = 'draft';
        }

        $rejectRecord = $this->penilaianModel
            ->where('pegawai_id', $pegawaiId)
            ->where('periode_id', $periodeId)
            ->where('status', 'rejected')
            ->first();
        $result['reject_note'] = $rejectRecord['reject_note'] ?? null;

        // Cek apakah ada permintaan draft ulang pending
        $draftUlangModel = new \App\Models\DraftUlangRequestModel();
        $result['has_pending_draft_request'] = $draftUlangModel->hasPendingRequest(
            $pegawaiId, $periodeId, 'pegawai'
        );

        return $result;
    }

    protected function canAccessPegawai(int $pegawaiId): bool
    {
        if (in_array(session()->get('role'), ['admin', 'hr'])) return true;
        $u = $this->pegawaiModel->find(session()->get('pegawai_id'));
        $t = $this->pegawaiModel->find($pegawaiId);
        return ($u && $t) ? $u['divisi_id'] == $t['divisi_id'] : false;
    }

    // Pastikan signature parameter sama dengan BaseController
    protected function forbidden(string $message = 'Anda tidak memiliki akses.') 
    { 
        return $this->response->setStatusCode(403)->setBody($message); 
    }

    public function redraft(int $pegawai_id)
    {
        // 1. Validasi: HANYA Administrator yang boleh melakukan ini
        if (session()->get('role') !== 'admin') {
            return redirect()->back()->with('error', 'Akses Ditolak! Hanya Administrator yang dapat melakukan Draft Ulang.');
        }

        // 2. Ambil data pegawai untuk kebutuhan Log Audit
        $pegawaiModel = new \App\Models\PegawaiModel();
        $pegawai = $pegawaiModel->find($pegawai_id);
        if (!$pegawai) {
            return redirect()->back()->with('error', 'Pegawai tidak ditemukan.');
        }

        // 3. Ambil periode yang sedang aktif
        $periodeModel = new \App\Models\PeriodeModel();
        $periodeAktif = $periodeModel->getActive();
        if (!$periodeAktif) {
            return redirect()->back()->with('error', 'Tidak ada periode aktif.');
        }

        // 4. Proses Draft Ulang: Ubah status kembali menjadi 'Draft'
        $penilaianModel = new \App\Models\PenilaianModel();
        $penilaianModel->where('pegawai_id', $pegawai_id)
                       ->where('periode_id', $periodeAktif['id'])
                       ->set(['status_approval' => 'Draft'])
                       ->update();

        // 5. Catat Log Perubahan (Sesuai Syarat No. 2)
        // Pastikan Anda sudah "use App\Services\AuditService;" di bagian atas Controller
        $auditService = new \App\Services\AuditService();
        $auditService->log(
            session()->get('id'), 
            "Melakukan DRAFT ULANG pada penilaian pegawai: " . $pegawai['nama'] . " (NIP: " . $pegawai['nip'] . "). Penilaian kini dapat diedit kembali."
        );

        return redirect()->back()->with('success', "Penilaian atas nama {$pegawai['nama']} berhasil di-Draft Ulang. User terkait kini dapat mengeditnya kembali.");
    }

    /**
     * 1. Fungsi APPROVER meminta konfirmasi Draft Ulang
     */
    public function requestRedraft(int $pegawai_id)
    {
        if (session()->get('role') !== 'approver') {
            return redirect()->back()->with('error', 'Hanya Approver yang dapat mengajukan permintaan Draft Ulang.');
        }

        $this->penilaianModel->where('pegawai_id', $pegawai_id)
             ->set([
                 'is_redraft_requested' => 1,
                 'redraft_requested_by' => session()->get('id')
             ])->update();

        $auditService = new \App\Services\AuditService();
        $auditService->log(session()->get('id'), "Approver mengajukan permintaan DRAFT ULANG untuk pegawai ID: $pegawai_id");

        return redirect()->back()->with('success', 'Permintaan Draft Ulang berhasil dikirim ke Administrator.');
    }

    /**
     * 2. Fungsi ADMIN eksekusi Draft Ulang (PER PEGAWAI)
     */
    public function approveRedraftSingle(int $pegawai_id)
    {
        if (session()->get('role') !== 'admin') return redirect()->back();

        $this->penilaianModel->where('pegawai_id', $pegawai_id)
             ->where('is_redraft_requested', 1)
             ->set([
                 'status_approval' => 'Draft',
                 'is_redraft_requested' => 0
             ])->update();

        $auditService = new \App\Services\AuditService();
        $auditService->log(session()->get('id'), "Admin MENYETUJUI eksekusi Draft Ulang untuk pegawai ID: $pegawai_id. Penilaian kembali menjadi Draft.");

        return redirect()->back()->with('success', 'Penilaian berhasil di-Draft Ulang. User terkait dapat mengedit nilainya kembali.');
    }

    /**
     * 3. Fungsi ADMIN eksekusi Draft Ulang (MASSAL PER PERIODE)
     */
    public function approveRedraftMassal(int $periode_id)
    {
        if (session()->get('role') !== 'admin') return redirect()->back();
        
        $jumlahRequest = $this->penilaianModel->where('periode_id', $periode_id)
                              ->where('is_redraft_requested', 1)
                              ->countAllResults();

        if ($jumlahRequest == 0) {
            return redirect()->back()->with('error', 'Tidak ada permintaan Draft Ulang yang tertunda di periode ini.');
        }

        $this->penilaianModel->where('periode_id', $periode_id)
             ->where('is_redraft_requested', 1)
             ->set([
                 'status_approval' => 'Draft',
                 'is_redraft_requested' => 0
             ])->update();

        $auditService = new \App\Services\AuditService();
        $auditService->log(session()->get('id'), "Admin mengeksekusi Draft Ulang MASSAL untuk $jumlahRequest pegawai pada Periode ID: $periode_id");

        return redirect()->back()->with('success', "$jumlahRequest Penilaian berhasil di-Draft Ulang secara massal.");
    }
}