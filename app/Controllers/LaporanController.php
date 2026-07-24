<?php
namespace App\Controllers;

use App\Models\PenilaianModel;
use App\Models\PegawaiModel;
use App\Models\PeriodeModel;
use App\Models\DivisiModel;
use App\Models\DirektoratModel;
use App\Models\KpiPegawaiBobotTahunanModel;
use App\Services\KpiCalculationService;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;

class LaporanController extends BaseController
{
    protected PenilaianModel        $penilaianModel;
    protected PegawaiModel          $pegawaiModel;
    protected PeriodeModel          $periodeModel;
    protected DivisiModel           $divisiModel;
    protected DirektoratModel       $direktoratModel;
    protected KpiCalculationService $calculator;

    public function __construct()
    {
        $this->penilaianModel  = new PenilaianModel();
        $this->pegawaiModel    = new PegawaiModel();
        $this->periodeModel    = new PeriodeModel();
        $this->divisiModel     = new DivisiModel();
        $this->direktoratModel = new DirektoratModel();
        $this->calculator      = new KpiCalculationService();
    }

    // ════════════════════════════════════════════════════════
    // PDF — Rekap semua pegawai
    // ════════════════════════════════════════════════════════
    // ════════════════════════════════════════════════════════
    // PDF — Rekap semua pegawai (DIUPDATE UNTUK DISTRIBUSI GRADE BARU)
    // ════════════════════════════════════════════════════════
    public function pdf()
    {
        $check = $this->checkMenuAccess('laporan_pdf');
        if ($check !== true) return $check;

        $periodeId = $this->request->getGet('periode_id');
        if (!$periodeId) {
            $periodeAktif = $this->periodeModel->getAktif();
            $periodeId    = $periodeAktif['id'] ?? null;
        }

        if (!$periodeId) {
            return redirect()->back()->with('error', 'Pilih periode terlebih dahulu.');
        }

        $periode = $this->periodeModel->find($periodeId);
        if (!$periode) {
            return redirect()->back()->with('error', 'Periode tidak ditemukan.');
        }

        // Drafter & Approver HANYA boleh melihat rekap untuk divisinya sendiri —
        // scope WAJIB ini diterapkan di level SQL, sama seperti RekapController,
        // agar export PDF tidak pernah membocorkan data divisi lain.
        $divisiScope = null;
        $role        = session()->get('role');
        $myPegawaiId = session()->get('pegawai_id');
        if (in_array($role, ['drafter', 'approver']) && $myPegawaiId) {
            $myPegawai   = $this->pegawaiModel->find($myPegawaiId);
            $divisiScope = $myPegawai['divisi_id'] ?? null;
        }

        $rekap   = $this->penilaianModel->getRekapKombinasi((int)$periodeId, $divisiScope);

        // Distribusi grade sesuai skema Yudisium (Istimewa/Sangat Baik/Baik/Cukup)
        $distribusi = [
            'IS' => 0,
            'SB' => 0,
            'B'  => 0,
            'C'  => 0,
        ];

        foreach ($rekap as $row) {
            $g = $row['grade'] ?? '';
            if (array_key_exists($g, $distribusi)) {
                $distribusi[$g]++;
            }
        }

        $html = view('laporan/pdf_rekap', [
            'periode'    => $periode,
            'rekap'      => $rekap,
            'distribusi' => $distribusi, // Variabel baru berisi counter skema grade baru
            'tanggal'    => date('d F Y'),
        ]);

        $this->generatePdf($html, "Rekap_KPI_{$periode['kode']}.pdf");
    }

    // ════════════════════════════════════════════════════════
    // PDF — Detail satu pegawai
    // ════════════════════════════════════════════════════════
    public function pdfPegawai(int $pegawaiId)
    {
        $check = $this->checkMenuAccess('laporan_pdf');
        if ($check !== true) return $check;

        if (!$this->canAccessPegawai($pegawaiId)) return $this->forbidden();

        $periodeId = $this->request->getGet('periode_id');
        if (!$periodeId) {
            $aktif     = $this->periodeModel->getAktif();
            $periodeId = $aktif['id'] ?? null;
        }

        if (!$periodeId) {
            return redirect()->back()->with('error', 'Pilih periode terlebih dahulu.');
        }

        $pegawai = $this->pegawaiModel->find($pegawaiId);
        if (!$pegawai) {
            return redirect()->back()->with('error', 'Pegawai tidak ditemukan.');
        }

        $periode = $this->periodeModel->find($periodeId);
        if (!$periode) {
            return redirect()->back()->with('error', 'Periode tidak ditemukan.');
        }

        $nilaiAkhir = $this->penilaianModel->getNilaiAkhir($pegawaiId, (int)$periodeId);
        $grade      = $nilaiAkhir > 0 ? $this->calculator->getGrade($nilaiAkhir) : '—';
        $gradeLabel = $grade !== '—' ? $this->calculator->getGradeLabel($grade) : '—';

        // Detail per KPI
        $detail = $this->penilaianModel->db->table('penilaian p')
            ->select('p.*, k.nama_kpi, k.kode, k.satuan,
                      k.polarity, k.perspektif, kp.id as kpi_pegawai_id')
            ->join('kpi_unit k', 'k.id = p.kpi_id')
            ->join('kpi_pegawai kp',
                   'kp.kpi_id = p.kpi_id AND kp.pegawai_id = p.pegawai_id')
            ->where('p.pegawai_id', $pegawaiId)
            ->where('p.periode_id', $periodeId)
            ->orderBy('k.perspektif', 'ASC')
            ->get()->getResultArray();
        $this->resolveBobotUntukDetail($detail, $periode);

        // Kelompokkan per perspektif
        $grouped = [];
        foreach ($detail as $row) {
            $grouped[$row['perspektif']][] = $row;
        }

        // Rekap perspektif
        $perspektifRekap = $this->penilaianModel->getRekapPerspektif(
            $pegawaiId, (int)$periodeId
        );

        $html = view('laporan/pdf_pegawai', [
            'pegawai'        => $pegawai,
            'periode'        => $periode,
            'nilaiAkhir'     => $nilaiAkhir,
            'grade'          => $grade,
            'gradeLabel'     => $gradeLabel,
            'grouped'        => $grouped,
            'perspektifRekap'=> $perspektifRekap,
            'tanggal'        => date('d F Y'),
        ]);

        $nama = str_replace(' ', '_', $pegawai['nama']);
        $this->generatePdf($html, "KPI_{$nama}_{$periode['kode']}.pdf");
    }

    // ════════════════════════════════════════════════════════
    // EXCEL — Rekap semua pegawai (Sudah Diperbaiki)
    // ════════════════════════════════════════════════════════
    public function excel()
    {
        $check = $this->checkMenuAccess('laporan_excel');
        if ($check !== true) return $check;

        $periodeId = $this->request->getGet('periode_id');
        if (!$periodeId) {
            $aktif     = $this->periodeModel->getAktif();
            $periodeId = $aktif['id'] ?? null;
        }

        if (!$periodeId) {
            return redirect()->back()->with('error', 'Pilih periode terlebih dahulu.');
        }

        $periode = $this->periodeModel->find($periodeId);
        if (!$periode) {
            return redirect()->back()->with('error', 'Periode tidak ditemukan.');
        }

        // Drafter & Approver HANYA boleh melihat rekap untuk divisinya sendiri —
        // scope WAJIB ini diterapkan di level SQL, sama seperti RekapController,
        // agar export Excel tidak pernah membocorkan data divisi lain.
        $divisiScope = null;
        $role        = session()->get('role');
        $myPegawaiId = session()->get('pegawai_id');
        if (in_array($role, ['drafter', 'approver']) && $myPegawaiId) {
            $myPegawai   = $this->pegawaiModel->find($myPegawaiId);
            $divisiScope = $myPegawai['divisi_id'] ?? null;
        }

        $rekap   = $this->penilaianModel->getRekapKombinasi((int)$periodeId, $divisiScope);

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap KPI');

        // ── Warna Utama Aplikasi ───────────────────────────
        $BIRU_TUA  = '1F4E79';
        $BIRU_MID  = '2E75B6';
        $BIRU_MUDA = 'BDD7EE';
        $ABU       = 'F2F2F2';

        // ── Header utama ───────────────────────────────────
        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', 'REKAP PENILAIAN KPI PEGAWAI');
        $this->styleCell($sheet, 'A1', [
            'font'  => ['bold'=>true,'color'=>['rgb'=>'FFFFFF'],'size'=>14],
            'fill'  => ['fillType'=>Fill::FILL_SOLID,'color'=>['rgb'=>$BIRU_TUA]],
            'align' => ['h'=>Alignment::HORIZONTAL_CENTER,'v'=>Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        // ── Sub header ─────────────────────────────────────
        $sheet->mergeCells('A2:H2');
        $sheet->setCellValue('A2',
            "Periode: {$periode['nama']}  |  Tanggal: " . date('d F Y'));
        $this->styleCell($sheet, 'A2', [
            'font'  => ['italic'=>true,'color'=>['rgb'=>$BIRU_TUA]],
            'fill'  => ['fillType'=>Fill::FILL_SOLID,'color'=>['rgb'=>$BIRU_MUDA]],
            'align' => ['h'=>Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(18);

        // ── Header tabel ───────────────────────────────────
        $headers = ['No','Nama Pegawai','Jabatan','Divisi',
                    'Direktorat','Jml KPI','Nilai KPI','Grade'];
        $widths  = [5, 30, 25, 30, 35, 10, 12, 10];

        foreach ($headers as $i => $h) {
            $col  = chr(65 + $i);
            $sheet->setCellValue("{$col}3", $h);
            $this->styleCell($sheet, "{$col}3", [
                'font'   => ['bold'=>true,'color'=>['rgb'=>'FFFFFF']],
                'fill'   => ['fillType'=>Fill::FILL_SOLID,'color'=>['rgb'=>$BIRU_MID]],
                'align'  => ['h'=>Alignment::HORIZONTAL_CENTER,
                             'v'=>Alignment::VERTICAL_CENTER,'wrap'=>true],
                'border' => true,
            ]);
            $sheet->getColumnDimension($col)->setWidth($widths[$i]);
        }
        $sheet->getRowDimension(3)->setRowHeight(22);

        // Standarisasi warna & teks sesuai skema Yudisium (Istimewa/Sangat Baik/Baik/Cukup)
        $grade_styles = [
            'IS' => ['bg' => '1E7A55', 'fg' => 'FFFFFF'], // Istimewa (Hijau Tua, Teks Putih)
            'SB' => ['bg' => 'A9D18E', 'fg' => '1E4620'], // Sangat Baik (Hijau Muda, Teks Hijau Tua)
            'B'  => ['bg' => 'FFC000', 'fg' => '7F6000'], // Baik (Oranye, Teks Cokelat Tua)
            'C'  => ['bg' => 'FCE4D6', 'fg' => 'C00000'], // Cukup (Merah Soft, Teks Merah Tua)
        ];

        foreach ($rekap as $i => $r) {
            $row   = $i + 4;
            $grade = $r['grade'] ?? '—';
            $bg    = ($i % 2 === 0) ? 'FFFFFF' : $ABU;

            $rowData = [
                $i + 1,
                $r['nama'],
                $r['jabatan'] ?? '',
                $r['divisi'] ?? '',
                $r['direktorat'] ?? '',
                $r['jumlah_kpi'],
                round((float)$r['nilai_akhir'], 2),
                $grade,
            ];

            foreach ($rowData as $j => $val) {
                $col = chr(65 + $j);
                $sheet->setCellValue("{$col}{$row}", $val);

                // Default style untuk baris data
                $cellBg = $bg;
                $cellFg = '000000';
                $isBold = false;

                // Jika kolom Grade (H), terapkan custom style berdasarkan grade yang aktif
                if ($col === 'H' && isset($grade_styles[$grade])) {
                    $cellBg = $grade_styles[$grade]['bg'];
                    $cellFg = $grade_styles[$grade]['fg'];
                    $isBold = true;
                }

                $this->styleCell($sheet, "{$col}{$row}", [
                    'fill'   => ['fillType'=>Fill::FILL_SOLID, 'color'=>['rgb'=>$cellBg]],
                    'align'  => ['h'=> in_array($col,['A','F','G','H'])
                                 ? Alignment::HORIZONTAL_CENTER
                                 : Alignment::HORIZONTAL_LEFT,
                                 'v'=>Alignment::VERTICAL_CENTER],
                    'border' => true,
                    'font'   => $col === 'G' 
                                ? ['bold'=>true, 'color'=>['rgb'=>$BIRU_TUA]] 
                                : ($isBold ? ['bold'=>true, 'color'=>['rgb'=>$cellFg]] : []),
                ]);
            }
            $sheet->getRowDimension($row)->setRowHeight(18);
        }

        // ── Baris total ────────────────────────────────────
        $totalRow = count($rekap) + 4;
        $sheet->mergeCells("A{$totalRow}:F{$totalRow}");
        $sheet->setCellValue("A{$totalRow}", 'TOTAL PEGAWAI DINILAI');
        $sheet->setCellValue("G{$totalRow}",
            count($rekap) > 0
            ? round(array_sum(array_column($rekap,'nilai_akhir'))/count($rekap),2)
            : 0
        );
        $sheet->setCellValue("H{$totalRow}", 'Rata-rata');

        foreach (['A','G','H'] as $col) {
            $this->styleCell($sheet, "{$col}{$totalRow}", [
                'font'  => ['bold'=>true,'color'=>['rgb'=>'FFFFFF']],
                'fill'  => ['fillType'=>Fill::FILL_SOLID,'color'=>['rgb'=>$BIRU_TUA]],
                'align' => ['h'=>Alignment::HORIZONTAL_CENTER],
                'border'=> true,
            ]);
        }

        // ── Output ─────────────────────────────────────────
        $filename = "Rekap_KPI_{$periode['kode']}_" . date('Ymd') . ".xlsx";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // ════════════════════════════════════════════════════════
    // EXCEL — Detail satu pegawai
    // ════════════════════════════════════════════════════════
    public function excelPegawai(int $pegawaiId)
    {
        $check = $this->checkMenuAccess('laporan_excel');
        if ($check !== true) return $check;

        if (!$this->canAccessPegawai($pegawaiId)) return $this->forbidden();

        $periodeId = $this->request->getGet('periode_id');
        if (!$periodeId) {
            $aktif     = $this->periodeModel->getAktif();
            $periodeId = $aktif['id'] ?? null;
        }

        if (!$periodeId) {
            return redirect()->back()->with('error', 'Pilih periode terlebih dahulu.');
        }

        $pegawai = $this->pegawaiModel->find($pegawaiId);
        if (!$pegawai) {
            return redirect()->back()->with('error', 'Pegawai tidak ditemukan.');
        }

        $periode = $this->periodeModel->find($periodeId);
        if (!$periode) {
            return redirect()->back()->with('error', 'Periode tidak ditemukan.');
        }

        $nilaiAkhir = $this->penilaianModel->getNilaiAkhir($pegawaiId, (int)$periodeId);
        $grade      = $nilaiAkhir > 0 ? $this->calculator->getGrade($nilaiAkhir) : '—';

        $detail = $this->penilaianModel->db->table('penilaian p')
            ->select('p.*, k.nama_kpi, k.kode, k.satuan,
                      k.polarity, k.perspektif, kp.id as kpi_pegawai_id')
            ->join('kpi_unit k', 'k.id = p.kpi_id')
            ->join('kpi_pegawai kp',
                   'kp.kpi_id = p.kpi_id AND kp.pegawai_id = p.pegawai_id')
            ->where('p.pegawai_id', $pegawaiId)
            ->where('p.periode_id', $periodeId)
            ->orderBy('k.perspektif', 'ASC')
            ->get()->getResultArray();
        $this->resolveBobotUntukDetail($detail, $periode);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Detail KPI');

        $BIRU_TUA = '1F4E79'; $BIRU_MID = '2E75B6';
        $BIRU_MUDA = 'BDD7EE'; $ABU = 'F2F2F2';

        // Header
        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', 'LAPORAN PENILAIAN KPI PEGAWAI');
        $this->styleCell($sheet, 'A1', [
            'font' => ['bold'=>true,'color'=>['rgb'=>'FFFFFF'],'size'=>13],
            'fill' => ['fillType'=>Fill::FILL_SOLID,'color'=>['rgb'=>$BIRU_TUA]],
            'align'=> ['h'=>Alignment::HORIZONTAL_CENTER,'v'=>Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Info pegawai
        $infoData = [
            ['Nama Pegawai', $pegawai['nama']],
            ['Jabatan',      $pegawai['jabatan'] ?? '—'],
            ['Periode',      $periode['nama']],
            ['Nilai KPI Akhir', number_format($nilaiAkhir, 2)],
            ['Grade',        $grade],
        ];
        foreach ($infoData as $i => [$label, $val]) {
            $row = $i + 2;
            $sheet->setCellValue("A{$row}", $label);
            $sheet->mergeCells("B{$row}:H{$row}");
            $sheet->setCellValue("B{$row}", $val);
            $this->styleCell($sheet, "A{$row}", [
                'font' => ['bold'=>true,'color'=>['rgb'=>$BIRU_TUA]],
                'fill' => ['fillType'=>Fill::FILL_SOLID,'color'=>['rgb'=>$BIRU_MUDA]],
                'border'=> true,
            ]);
            $this->styleCell($sheet, "B{$row}", [
                'fill' => ['fillType'=>Fill::FILL_SOLID,'color'=>['rgb'=>$ABU]],
                'border'=> true,
            ]);
            $sheet->getRowDimension($row)->setRowHeight(18);
        }

        // Header tabel KPI
        $headerRow = 8;
        $kpiHeaders = ['No','Perspektif','Nama KPI','Kode',
                       'Bobot','Target','Realisasi','Capaian %'];
        $kpiWidths  = [5, 18, 35, 12, 8, 12, 12, 12];
        foreach ($kpiHeaders as $i => $h) {
            $col = chr(65+$i);
            $sheet->setCellValue("{$col}{$headerRow}", $h);
            $this->styleCell($sheet, "{$col}{$headerRow}", [
                'font'  => ['bold'=>true,'color'=>['rgb'=>'FFFFFF']],
                'fill'  => ['fillType'=>Fill::FILL_SOLID,'color'=>['rgb'=>$BIRU_MID]],
                'align' => ['h'=>Alignment::HORIZONTAL_CENTER,
                            'v'=>Alignment::VERTICAL_CENTER],
                'border'=> true,
            ]);
            $sheet->getColumnDimension($col)->setWidth($kpiWidths[$i]);
        }
        $sheet->getRowDimension($headerRow)->setRowHeight(20);

        // Data KPI
        $dataRow = $headerRow + 1;
        foreach ($detail as $i => $kpi) {
            $cap = (float)$kpi['capaian'];
            $capBg = $cap >= 1 ? 'C6EFCE'
                   : ($cap >= 0.76 ? $BIRU_MUDA
                   : ($cap >= 0.61 ? 'FFF2CC' : 'FCE4D6'));
            $bg = $i % 2 === 0 ? 'FFFFFF' : $ABU;

            $realisasiDisplay = $kpi['realisasi'];
            $targetDisplay    = $kpi['target'];
            if ($kpi['polarity'] === 'special') {
                $realisasiDisplay = ((float)($kpi['realisasi'] ?? 0) == 1.0) ? 'Ada' : 'Tidak Ada';
                $targetDisplay    = '—'; // Tidak berlaku untuk Special Scoring
            } elseif ($kpi['polarity'] === 'tertimbang') {
                $realisasiDisplay = $kpi['realisasi'] . ' / Harian: ' . ($kpi['realisasi_harian'] ?? 0) . '%';
            }

            $rowData = [
                $i+1,
                $kpi['perspektif'],
                $kpi['nama_kpi'],
                $kpi['kode'],
                round($kpi['bobot']*100,1).'%',
                $targetDisplay,
                $realisasiDisplay,
                round($cap*100,2).'%',
            ];

            foreach ($rowData as $j => $val) {
                $col = chr(65+$j);
                $sheet->setCellValue("{$col}{$dataRow}", $val);
                $cellBg = $col === 'H' ? $capBg : $bg;
                $this->styleCell($sheet, "{$col}{$dataRow}", [
                    'fill'  => ['fillType'=>Fill::FILL_SOLID,'color'=>['rgb'=>$cellBg]],
                    'align' => ['h'=> in_array($col,['A','D','E','F','G','H'])
                                ? Alignment::HORIZONTAL_CENTER
                                : Alignment::HORIZONTAL_LEFT],
                    'border'=> true,
                ]);
            }
            $sheet->getRowDimension($dataRow)->setRowHeight(18);
            $dataRow++;
        }

        $nama     = str_replace(' ','_',$pegawai['nama']);
        $filename = "KPI_{$nama}_{$periode['kode']}_".date('Ymd').".xlsx";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // ════════════════════════════════════════════════════════
    // Helper — Generate PDF
    // ════════════════════════════════════════════════════════
    private function generatePdf(string $html, string $filename): void
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('defaultFont', 'Arial');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream($filename, ['Attachment' => true]);
        exit;
    }

    // Helper — Style cell Excel
    private function styleCell(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        string $cell,
        array $style
    ): void {
        $s = [];

        if (!empty($style['font'])) {
            $s['font'] = $style['font'];
        }
        if (!empty($style['fill'])) {
            $s['fill'] = $style['fill'];
        }
        if (!empty($style['align'])) {
            $s['alignment'] = $style['align'];
        }
        if (!empty($style['border'])) {
            $s['borders'] = [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => 'CCCCCC'],
                ],
            ];
        }

        $sheet->getStyle($cell)->applyFromArray($s);
    }

    /**
     * Isi ulang 'bobot' pada setiap baris $detail dari Master Target (Bobot
     * Tahunan) untuk tahun Periode yang bersangkutan — kolom legacy
     * kpi_pegawai.bobot sudah tidak lagi dikelola sejak Bobot dipindah
     * sepenuhnya ke menu "Master Target", jadi tidak boleh lagi dipakai
     * sebagai sumber tampilan laporan.
     */
    private function resolveBobotUntukDetail(array &$detail, ?array $periode): void
    {
        if (empty($detail) || !$periode) {
            return;
        }

        $tahun = (int)date('Y', strtotime($periode['tgl_mulai']));
        $kpiPegawaiIds = array_values(array_unique(array_filter(array_column($detail, 'kpi_pegawai_id'))));
        $bobotIndexed = (new KpiPegawaiBobotTahunanModel())->getIndexedByRefAndTahun($kpiPegawaiIds, $tahun);

        foreach ($detail as &$row) {
            $row['bobot'] = $bobotIndexed[$row['kpi_pegawai_id']] ?? 0;
        }
        unset($row);
    }
}