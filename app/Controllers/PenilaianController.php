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
        $rec = $this->penilaianModel->where(['pegawai_id' => $pegawaiId, 'periode_id' => $periodeId])->first();
        return ['current' => $rec['status'] ?? 'draft', 'reject_note' => $rec['reject_note'] ?? null];
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
}