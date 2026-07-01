<?php
namespace App\Controllers;

use App\Services\AiAssistantService;
use App\Models\PenilaianModel;
use App\Models\PeriodeModel;
use App\Models\PegawaiModel;
use App\Services\KpiCalculationService;

class AiController extends BaseController
{
    protected AiAssistantService    $aiService;
    protected PenilaianModel        $penilaianModel;
    protected PeriodeModel          $periodeModel;
    protected PegawaiModel          $pegawaiModel;
    protected KpiCalculationService $calculator;

    public function __construct()
    {
        $this->aiService      = new AiAssistantService();
        $this->penilaianModel = new PenilaianModel();
        $this->periodeModel   = new PeriodeModel();
        $this->pegawaiModel   = new PegawaiModel();
        $this->calculator     = new KpiCalculationService();
    }

    // ── Halaman Chat AI ──────────────────────────────────────
    public function index(): string
    {
        $check = $this->checkMenuAccess('ai');
        if ($check !== true) return $check;

        return view('layouts/main', [
            'title'   => 'AI Asisten KPI',
            'content' => view('ai/_chat'),
        ]);
    }

    // ── AJAX Chat ────────────────────────────────────────────
    public function chat()
    {
        $check = $this->checkMenuAccess('ai');
        if ($check !== true) {
            return $this->response->setJSON(['reply' => 'Anda tidak memiliki akses ke fitur ini.', 'csrf_hash' => csrf_hash()]);
        }

        $message = trim($this->request->getPost('message') ?? '');
        if (empty($message)) {
            return $this->response->setJSON([
                'reply' => 'Pesan tidak boleh kosong.',
                'csrf_hash' => csrf_hash(),
            ]);
        }

        // Sertakan konteks data KPI rekapitulasi terkini
        $context = $this->buildContext();

        $reply = $this->aiService->chat($message, $context);

        // Format markdown sederhana ke HTML secara aman
        $reply = $this->formatReply($reply);

        return $this->response->setJSON(['reply' => $reply, 'csrf_hash' => csrf_hash()]);
    }

    // ── Analisis Otomatis Pegawai ────────────────────────────
    public function analisisPegawai(int $pegawaiId)
    {
        $check = $this->checkMenuAccess('ai');
        if ($check !== true) {
            return $this->response->setJSON(['reply' => 'Anda tidak memiliki akses ke fitur ini.']);
        }

        if (!$this->canAccessPegawai($pegawaiId)) {
            return $this->response->setJSON(['reply' => 'Anda tidak memiliki akses ke data pegawai ini.']);
        }

        $pegawai      = $this->pegawaiModel->find($pegawaiId);
        $periodeAktif = $this->periodeModel->getAktif();

        if (!$pegawai) {
            return $this->response->setJSON([
                'reply' => 'Data pegawai tidak ditemukan.',
            ]);
        }

        if (!$periodeAktif) {
            return $this->response->setJSON([
                'reply' => 'Tidak ada periode aktif untuk dianalisis.',
            ]);
        }

        $nilaiAkhir = $this->penilaianModel->getNilaiAkhir(
            $pegawaiId, $periodeAktif['id']
        );
        $grade      = $nilaiAkhir > 0
            ? $this->calculator->getGrade($nilaiAkhir) : '—';

        // Perbaikan: Amankan query binding untuk menghindari potensi SQL injection di klausa JOIN
        $detail = $this->penilaianModel->db->table('penilaian p')
            ->select('p.*, k.nama_kpi, k.perspektif, kp.bobot')
            ->join('kpi_unit k', 'k.id = p.kpi_id')
            ->join('kpi_pegawai kp', 'kp.kpi_id = p.kpi_id AND kp.pegawai_id = :pegawaiId:', false)
            ->where('p.pegawai_id', $pegawaiId)
            ->where('p.periode_id', $periodeAktif['id'])
            ->binds(['pegawaiId' => $pegawaiId])
            ->get()->getResultArray();

        $context = [
            'pegawai'     => $pegawai['nama'],
            'jabatan'     => $pegawai['jabatan'],
            'periode'     => $periodeAktif['nama'],
            'nilai_akhir' => $nilaiAkhir,
            'grade'       => $grade,
            'detail_kpi'  => array_map(fn($d) => [
                'nama'       => $d['nama_kpi'],
                'perspektif' => $d['perspektif'],
                'target'     => $d['target'],
                'realisasi'  => $d['realisasi'],
                'capaian'    => round((float)$d['capaian'] * 100, 2) . '%',
            ], $detail),
        ];

        $prompt = "Berikan analisis mendalam dan rekomendasi tindak lanjut "
                . "untuk pegawai {$pegawai['nama']} berdasarkan data KPI "
                . "di atas. Sertakan: 1) Ringkasan kinerja, "
                . "2) KPI terkuat dan terlemah, "
                . "3) Rekomendasi konkret, "
                . "4) Target perbaikan periode berikutnya.";

        $reply = $this->aiService->chat($prompt, $context);
        $reply = $this->formatReply($reply);

        return $this->response->setJSON(['reply' => $reply]);
    }

    private function buildContext(): array
    {
        $periodeAktif = $this->periodeModel->getAktif();
        if (!$periodeAktif) return [];

        $rekap = $this->penilaianModel->getRekapKombinasi($periodeAktif['id']) ?? [];

        // Proteksi jika rekap data kosong agar tidak terjadi division by zero
        $totalPegawai = count($rekap);
        if ($totalPegawai === 0) {
            return [
                'periode'          => $periodeAktif['nama'],
                'total_pegawai'    => 0,
                'rata_nilai'       => 0,
                'distribusi_grade' => [],
                'top_3'            => [],
            ];
        }

        // Urutkan rekap dari nilai tertinggi ke terendah secara manual untuk menjamin ketepatan top_3
        usort($rekap, fn($a, $b) => $b['nilai_akhir'] <=> $a['nilai_akhir']);

        return [
            'periode'       => $periodeAktif['nama'],
            'total_pegawai' => $totalPegawai,
            'rata_nilai'    => round(array_sum(array_column($rekap, 'nilai_akhir')) / $totalPegawai, 2),
            'distribusi_grade' => array_count_values(array_column($rekap, 'grade')),
            'top_3' => array_slice(
                array_map(fn($r) => [
                    'nama'  => $r['nama'],
                    'nilai' => $r['nilai_akhir'],
                    'grade' => $r['grade'],
                ], $rekap),
                0, 3
            ),
        ];
    }

    /**
     * Memformat teks markdown bawaan AI menjadi kode HTML yang valid dan rapi
     */
    private function formatReply(string $text): string
    {
        // 1. Bersihkan elemen HTML berbahaya untuk mencegah XSS jika diperlukan
        $text = esc($text, 'html'); 

        // 2. Mengubah tanda markdown bold (**) dan italic (*) ke tag HTML
        $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $text);

        // 3. Parser List Terstruktur (Mengubah nomor dan bullet secara aman baris demi baris)
        $lines = explode("\n", $text);
        $inOrderList = false;
        $inUnorderList = false;

        foreach ($lines as $i => $line) {
            // Cek Numbered List (contoh: 1. Sesuatu)
            if (preg_replace('/^\d+\.\s+(.+)$/', '<li>$1</li>', $line, 1, $count) && $count > 0) {
                $lines[$i] = ($inOrderList ? '' : "<ol>\n") . preg_replace('/^\d+\.\s+(.+)$/', '<li>$1</li>', $line);
                $inOrderList = true;
                if ($inUnorderList) { $lines[$i] = "</ul>\n" . $lines[$i]; $inUnorderList = false; }
            } 
            // Cek Bullet List (contoh: - Sesuatu atau * Sesuatu)
            elseif (preg_replace('/^[-•*]\s+(.+)$/', '<li>$1</li>', $line, 1, $count) && $count > 0) {
                $lines[$i] = ($inUnorderList ? '' : "<ul>\n") . preg_replace('/^[-•*]\s+(.+)$/', '<li>$1</li>', $line);
                $inUnorderList = true;
                if ($inOrderList) { $lines[$i] = "</ol>\n" . $lines[$i]; $inOrderList = false; }
            } 
            // Baris normal (bukan list)
            else {
                if ($inOrderList) { $lines[$i] = "</ol>\n" . $lines[$i]; $inOrderList = false; }
                if ($inUnorderList) { $lines[$i] = "</ul>\n" . $lines[$i]; $inUnorderList = false; }
            }
        }

        // Tutup tag list di akhir baris jika masih terbuka
        if ($inOrderList) $lines[] = "</ol>";
        if ($inUnorderList) $lines[] = "</ul>";

        $text = implode("\n", $lines);

        // 4. Ubah sisa baris baru menjadi tag <br> secara natural
        return nl2br($text);
    }
}