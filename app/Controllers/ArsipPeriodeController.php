<?php

namespace App\Controllers;

use App\Models\PenilaianArsipModel;
use App\Models\PenilaianTurunanArsipModel;
use App\Models\PeriodeModel;
use App\Services\KpiCalculationService;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

/**
 * Arsip Periode — menampilkan & mengekspor rekapan Penilaian dari Periode
 * yang sudah DITUTUP, bersumber dari snapshot beku (penilaian_arsip /
 * penilaian_turunan_arsip), BUKAN dari data KPI/pegawai yang bisa berubah
 * seiring waktu. Khusus Administrator ("Super Admin"), sesuai kerahasiaan
 * data HR — akses tidak diperluas ke role lain tanpa permintaan eksplisit.
 */
class ArsipPeriodeController extends BaseController
{
    protected PenilaianArsipModel        $arsipModel;
    protected PenilaianTurunanArsipModel $arsipTurunanModel;
    protected PeriodeModel               $periodeModel;
    protected KpiCalculationService      $calculator;

    public function __construct()
    {
        $this->arsipModel        = new PenilaianArsipModel();
        $this->arsipTurunanModel = new PenilaianTurunanArsipModel();
        $this->periodeModel      = new PeriodeModel();
        $this->calculator        = new KpiCalculationService();
    }

    /**
     * Guard admin-only diperiksa di AWAL setiap action (bukan di
     * constructor) — constructor CI4 tidak bisa menghentikan pemanggilan
     * action method dengan me-return Response, sehingga pola lama di
     * beberapa controller lain memakai header()+exit() langsung di
     * constructor. Itu bekerja normal di server sungguhan, tapi exit()
     * ikut menghentikan proses PHPUnit yang berbagi satu proses PHP untuk
     * seluruh test suite — jadi di sini dipakai pola redirect() biasa
     * (seperti checkMenuAccess()) yang aman diuji.
     */
    private function checkAdminOnly()
    {
        if (session()->get('role') !== 'admin') {
            return redirect()->to(base_url('dashboard'))
                             ->with('error', 'Halaman Arsip Periode hanya dapat diakses oleh Administrator.');
        }
        return true;
    }

    // ── Daftar Periode yang sudah ditutup ────────────────────
    public function index()
    {
        $check = $this->checkAdminOnly();
        if ($check !== true) return $check;

        $periodesTutup = $this->periodeModel->where('status', 'tutup')
                                             ->orderBy('tgl_mulai', 'DESC')
                                             ->findAll();

        $ringkasan = [];
        foreach ($periodesTutup as $p) {
            $jumlahBaris   = $this->arsipModel->where('periode_id', $p['id'])->countAllResults();
            $jumlahPegawai = $jumlahBaris > 0
                ? count(array_unique($this->arsipModel->where('periode_id', $p['id'])->findColumn('pegawai_id') ?? []))
                : 0;

            $ringkasan[] = [
                'periode'        => $p,
                'jumlah_pegawai' => $jumlahPegawai,
                'jumlah_baris'   => $jumlahBaris,
            ];
        }

        return view('layouts/main', [
            'title'   => 'Arsip Periode',
            'content' => view('arsip_periode/_content', [
                'ringkasan' => $ringkasan,
            ]),
        ]);
    }

    // ── Rekap ranking (Nilai Akhir + Grade) untuk satu periode arsip ──
    public function detail(int $periodeId)
    {
        $check = $this->checkAdminOnly();
        if ($check !== true) return $check;

        $periode = $this->periodeModel->find($periodeId);
        if (!$periode || $periode['status'] !== 'tutup') {
            return redirect()->to(base_url('arsip-periode'))
                             ->with('error', 'Periode tidak ditemukan atau belum ditutup.');
        }

        $rekap = $this->arsipModel->getRekapPeriode($periodeId);
        foreach ($rekap as &$row) {
            $nilai = (float)$row['nilai_akhir'];
            $grade = $nilai > 0 ? $this->calculator->getGrade($nilai) : '—';
            $row['grade']       = $grade;
            $row['grade_label'] = $grade !== '—' ? $this->calculator->getGradeLabel($grade) : '—';
        }
        unset($row);

        return view('layouts/main', [
            'title'   => 'Arsip Periode — ' . $periode['nama'],
            'content' => view('arsip_periode/_detail', [
                'periode' => $periode,
                'rekap'   => $rekap,
            ]),
        ]);
    }

    // ── Breakdown lengkap Induk + Turunan untuk satu pegawai ──
    public function detailPegawai(int $periodeId, int $pegawaiId)
    {
        $check = $this->checkAdminOnly();
        if ($check !== true) return $check;

        $periode = $this->periodeModel->find($periodeId);
        if (!$periode || $periode['status'] !== 'tutup') {
            return redirect()->to(base_url('arsip-periode'))
                             ->with('error', 'Periode tidak ditemukan atau belum ditutup.');
        }

        [$pegawaiInfo, $grouped, $nilaiAkhir, $grade, $gradeLabel] =
            $this->rangkumDetailPegawai($periodeId, $pegawaiId);

        if (!$pegawaiInfo) {
            return redirect()->to(base_url("arsip-periode/detail/{$periodeId}"))
                             ->with('error', 'Data pegawai tidak ditemukan pada arsip periode ini.');
        }

        return view('layouts/main', [
            'title'   => 'Arsip Detail — ' . $pegawaiInfo['pegawai_nama'],
            'content' => view('arsip_periode/_detail_pegawai', [
                'periode'    => $periode,
                'pegawai'    => $pegawaiInfo,
                'grouped'    => $grouped,
                'nilaiAkhir' => $nilaiAkhir,
                'grade'      => $grade,
                'gradeLabel' => $gradeLabel,
            ]),
        ]);
    }

    /**
     * Kumpulkan detail Induk (dikelompokkan per perspektif, masing-masing
     * membawa daftar Turunannya jika ada) + Nilai Akhir/Grade untuk satu
     * pegawai — dipakai bersama oleh detailPegawai() dan exportPdf().
     */
    private function rangkumDetailPegawai(int $periodeId, int $pegawaiId): array
    {
        $rows = $this->arsipModel->getDetailPegawai($periodeId, $pegawaiId);
        if (empty($rows)) {
            return [null, [], 0.0, '—', '—'];
        }

        $pegawaiInfo = [
            'pegawai_nama'    => $rows[0]['pegawai_nama'],
            'pegawai_nip'     => $rows[0]['pegawai_nip'],
            'pegawai_jabatan' => $rows[0]['pegawai_jabatan'],
            'divisi_nama'     => $rows[0]['divisi_nama'],
            'direktorat_nama' => $rows[0]['direktorat_nama'],
        ];

        $arsipIds       = array_column($rows, 'id');
        $turunanGrouped = $this->arsipTurunanModel->getGroupedByPenilaianArsipIds($arsipIds);

        $grouped    = [];
        $nilaiAkhir = 0.0;
        foreach ($rows as $row) {
            $row['turunan'] = $turunanGrouped[$row['id']] ?? [];
            $grouped[$row['kpi_perspektif'] ?? '—'][] = $row;
            $nilaiAkhir += (float)($row['nilai_kontribusi'] ?? 0);
        }

        $grade      = $nilaiAkhir > 0 ? $this->calculator->getGrade($nilaiAkhir) : '—';
        $gradeLabel = $grade !== '—' ? $this->calculator->getGradeLabel($grade) : '—';

        return [$pegawaiInfo, $grouped, round($nilaiAkhir, 2), $grade, $gradeLabel];
    }

    // ── Export PDF — seluruh pegawai, lengkap Induk + Turunan ──
    public function exportPdf(int $periodeId)
    {
        $check = $this->checkAdminOnly();
        if ($check !== true) return $check;

        $periode = $this->periodeModel->find($periodeId);
        if (!$periode || $periode['status'] !== 'tutup') {
            return redirect()->to(base_url('arsip-periode'))
                             ->with('error', 'Periode tidak ditemukan atau belum ditutup.');
        }

        $pegawaiIds = array_values(array_unique(
            $this->arsipModel->where('periode_id', $periodeId)->findColumn('pegawai_id') ?? []
        ));

        $pegawaiList = [];
        foreach ($pegawaiIds as $pid) {
            [$info, $grouped, $nilaiAkhir, $grade, $gradeLabel] = $this->rangkumDetailPegawai($periodeId, $pid);
            if (!$info) continue;
            $pegawaiList[] = [
                'info'        => $info,
                'grouped'     => $grouped,
                'nilai_akhir' => $nilaiAkhir,
                'grade'       => $grade,
                'grade_label' => $gradeLabel,
            ];
        }

        // Urutkan berdasarkan Nilai Akhir tertinggi, konsisten dengan Rekap.
        usort($pegawaiList, fn($a, $b) => $b['nilai_akhir'] <=> $a['nilai_akhir']);

        $html = view('arsip_periode/pdf_bulk', [
            'periode'     => $periode,
            'pegawaiList' => $pegawaiList,
            'tanggal'     => date('d F Y'),
        ]);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('defaultFont', 'Arial');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream("Arsip_KPI_{$periode['kode']}.pdf", ['Attachment' => true]);
        exit;
    }

    // ── Export Excel — seluruh pegawai, lengkap Induk + Turunan ──
    public function exportExcel(int $periodeId)
    {
        $check = $this->checkAdminOnly();
        if ($check !== true) return $check;

        $periode = $this->periodeModel->find($periodeId);
        if (!$periode || $periode['status'] !== 'tutup') {
            return redirect()->to(base_url('arsip-periode'))
                             ->with('error', 'Periode tidak ditemukan atau belum ditutup.');
        }

        $rekap = $this->arsipModel->getRekapPeriode($periodeId);
        foreach ($rekap as &$row) {
            $nilai = (float)$row['nilai_akhir'];
            $grade = $nilai > 0 ? $this->calculator->getGrade($nilai) : '—';
            $row['grade']       = $grade;
            $row['grade_label'] = $grade !== '—' ? $this->calculator->getGradeLabel($grade) : '—';
        }
        unset($row);

        $detailRows = $this->arsipModel->getAllDetailPeriode($periodeId);
        $arsipIds   = array_column($detailRows, 'id');
        $turunanGrouped = $this->arsipTurunanModel->getGroupedByPenilaianArsipIds($arsipIds);

        $spreadsheet = new Spreadsheet();

        $BIRU_TUA  = '1F4E79'; $BIRU_MID = '2E75B6';
        $BIRU_MUDA = 'BDD7EE'; $ABU      = 'F2F2F2';

        // ── Sheet 1: Ringkasan ──
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Ringkasan');
        $sheet1->setCellValue('A1', 'ARSIP REKAP KPI — ' . $periode['nama'] . ' (DITUTUP)');
        $this->styleCellExcel($sheet1, 'A1', [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 13],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $BIRU_TUA]],
        ]);
        $sheet1->mergeCells('A1:F1');

        $headers1 = ['No', 'Nama Pegawai', 'Jabatan', 'Divisi', 'Nilai Akhir', 'Grade'];
        foreach ($headers1 as $i => $h) {
            $col = chr(65 + $i);
            $sheet1->setCellValue("{$col}3", $h);
            $this->styleCellExcel($sheet1, "{$col}3", [
                'font'  => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill'  => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $BIRU_MID]],
                'align' => ['h' => Alignment::HORIZONTAL_CENTER],
                'border'=> true,
            ]);
        }
        $sheet1->getColumnDimension('B')->setWidth(28);
        $sheet1->getColumnDimension('C')->setWidth(22);
        $sheet1->getColumnDimension('D')->setWidth(20);

        $r = 4;
        foreach ($rekap as $i => $row) {
            $sheet1->fromArray([
                $i + 1, $row['nama'], $row['jabatan'] ?? '—', $row['divisi'] ?? '—',
                round((float)$row['nilai_akhir'], 2), $row['grade_label'],
            ], null, "A{$r}");
            $r++;
        }

        // ── Sheet 2: Detail Parameter KPI (Induk + Turunan) ──
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Detail Parameter KPI');
        $headers2 = ['Nama Pegawai', 'Perspektif', 'Tipe', 'Kode/Parameter', 'Nama KPI', 'Polarity', 'Bobot (%)', 'Target', 'Realisasi', 'Skor', 'Kontribusi'];
        foreach ($headers2 as $i => $h) {
            $col = chr(65 + $i);
            $sheet2->setCellValue("{$col}1", $h);
            $this->styleCellExcel($sheet2, "{$col}1", [
                'font'  => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill'  => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $BIRU_MID]],
                'align' => ['h' => Alignment::HORIZONTAL_CENTER],
                'border'=> true,
            ]);
        }
        foreach (['A'=>28,'B'=>16,'C'=>10,'D'=>14,'E'=>32,'F'=>12,'G'=>10,'H'=>10,'I'=>10,'J'=>8,'K'=>12] as $col => $w) {
            $sheet2->getColumnDimension($col)->setWidth($w);
        }

        $r = 2;
        foreach ($detailRows as $row) {
            $sheet2->fromArray([
                $row['pegawai_nama'], $row['kpi_perspektif'], 'Induk', $row['kpi_kode'], $row['kpi_nama'],
                $row['polarity'], round((float)$row['bobot'] * 100, 2),
                $row['target'], $row['realisasi'], $row['skor'], $row['nilai_kontribusi'],
            ], null, "A{$r}");
            $r++;

            foreach (($turunanGrouped[$row['id']] ?? []) as $t) {
                $sheet2->fromArray([
                    $row['pegawai_nama'], $row['kpi_perspektif'], 'Turunan', '—', $t['nama_turunan'],
                    $t['polarity'], round((float)$t['bobot'] * 100, 2),
                    $t['target'], $t['realisasi'], $t['skor'], $t['nilai_kontribusi'],
                ], null, "A{$r}");
                $r++;
            }
        }

        $spreadsheet->setActiveSheetIndex(0);

        $filename = "Arsip_KPI_{$periode['kode']}_" . date('Ymd') . ".xlsx";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private function styleCellExcel(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        string $cell,
        array $style
    ): void {
        $s = [];
        if (!empty($style['font']))  $s['font']      = $style['font'];
        if (!empty($style['fill']))  $s['fill']      = $style['fill'];
        if (!empty($style['align'])) $s['alignment'] = $style['align'];
        if (!empty($style['border'])) {
            $s['borders'] = [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']],
            ];
        }
        $sheet->getStyle($cell)->applyFromArray($s);
    }
}
