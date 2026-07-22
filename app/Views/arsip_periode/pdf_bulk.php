<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: Arial, sans-serif; font-size: 11px; color: #222; }

    .header { background: #1F4E79; color: white; padding: 12px 16px; margin-bottom: 12px; }
    .header h1 { font-size: 15px; margin-bottom: 2px; }
    .header p  { font-size: 10px; opacity: .8; }

    .pegawai-section { page-break-after: always; }
    .pegawai-section:last-child { page-break-after: auto; }

    .info-table { width: 100%; margin-bottom: 12px; border-collapse: collapse; }
    .info-table td { padding: 5px 8px; border: 0.5px solid #dde; font-size: 10px; }
    .info-table .label { background: #BDD7EE; font-weight: bold; color: #1F4E79; width: 140px; }

    table.kpi { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    table.kpi th { background: #2E75B6; color: white; padding: 5px 6px; font-size: 9px; text-align: center; }
    table.kpi td { padding: 4px 6px; border-bottom: 0.5px solid #e0e0e0; font-size: 9px; }
    table.kpi tr:nth-child(even) td { background: #f5f8fc; }
    table.kpi tr.turunan td { background: #FAFBFC; color: #555; }

    .persp-header { background: #E6F1FB; color: #1F4E79; font-weight: bold;
                    padding: 4px 8px; font-size: 10px;
                    border-left: 3px solid #2E75B6; margin: 8px 0 4px; }

    .grade-IS { background: #1E7A55 !important; color: white; }
    .grade-SB { background: #A9D18E !important; color: #1E4620; }
    .grade-B  { background: #FFC000 !important; color: #7F6000; }
    .grade-C  { background: #FCE4D6 !important; color: #C00000; }
    .grade--  { background: #f0f0f0 !important; color: #888; }

    .footer { font-size: 9px; color: #888; text-align: right; margin-top: 10px; }
  </style>
</head>
<body>

<div class="header">
  <h1>Arsip Rekap Penilaian KPI — <?= esc($periode['nama']) ?> (DITUTUP)</h1>
  <p>Kode Periode: <?= esc($periode['kode']) ?>&nbsp;|&nbsp; Dicetak: <?= $tanggal ?></p>
</div>

<?php
$persp_colors = [
    'Financial'         => ['E6F1FB','1F4E79','2E75B6'],
    'Customer'          => ['EAF3DE','375623','70AD47'],
    'Internal Process'  => ['FFF3CD','7F6000','BF9000'],
    'Learning & Growth' => ['F3E5F5','5C2A6B','9B59B6'],
];
$polarityLabels = [
    'max' => 'Max', 'min' => 'Min', 'precise' => 'Precise',
    'special' => 'Special', 'tertimbang' => 'Tertimbang',
];
?>

<?php if (empty($pegawaiList)): ?>
<p style="text-align:center;color:#888;padding:30px 0">Tidak ada data arsip untuk periode ini.</p>
<?php endif; ?>

<?php foreach ($pegawaiList as $pd): ?>
<?php $info = $pd['info']; $gradeCls = $pd['grade'] === '—' ? '--' : $pd['grade']; ?>
<div class="pegawai-section">
  <table class="info-table">
    <tr>
      <td class="label">Nama Pegawai</td>
      <td><?= esc($info['pegawai_nama']) ?></td>
      <td class="label">Nilai KPI Akhir</td>
      <td><b style="font-size:14px;color:#1F4E79"><?= number_format($pd['nilai_akhir'],2) ?></b></td>
    </tr>
    <tr>
      <td class="label">Jabatan</td>
      <td><?= esc($info['pegawai_jabatan'] ?? '—') ?></td>
      <td class="label">Grade</td>
      <td class="grade-<?= esc($gradeCls, 'attr') ?>">
        <b style="font-size:14px"><?= esc($pd['grade']) ?></b> — <?= esc($pd['grade_label']) ?>
      </td>
    </tr>
    <tr>
      <td class="label">Divisi / Direktorat</td>
      <td colspan="3"><?= esc($info['divisi_nama'] ?? '—') ?> / <?= esc($info['direktorat_nama'] ?? '—') ?></td>
    </tr>
  </table>

  <?php foreach ($pd['grouped'] as $perspektif => $kpis): ?>
  <?php $pc = $persp_colors[$perspektif] ?? ['f8f9fa','333','888']; ?>
  <div class="persp-header" style="background:#<?= $pc[0] ?>; color:#<?= $pc[1] ?>; border-left-color:#<?= $pc[2] ?>">
    <?= esc($perspektif) ?> — Kontribusi: <?= round(array_sum(array_column($kpis,'nilai_kontribusi')),2) ?>
  </div>

  <table class="kpi">
    <thead>
      <tr>
        <th style="text-align:left;width:32%">Nama Parameter KPI</th>
        <th>Kode</th>
        <th>Polarity</th>
        <th>Bobot</th>
        <th>Target</th>
        <th>Realisasi</th>
        <th>Skor</th>
        <th>Total</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($kpis as $kpi): ?>
      <?php $punyaTurunan = !empty($kpi['turunan']); ?>
      <tr>
        <td style="text-align:left"><?= esc($kpi['kpi_nama']) ?></td>
        <td style="text-align:center"><?= esc($kpi['kpi_kode']) ?></td>
        <td style="text-align:center"><?= $polarityLabels[$kpi['polarity']] ?? '—' ?></td>
        <td style="text-align:center"><?= round((float)$kpi['bobot']*100,1) ?>%</td>
        <td style="text-align:center">
          <?= $kpi['polarity'] === 'special' ? '—' : number_format((float)($kpi['target'] ?? 0),2) ?>
        </td>
        <td style="text-align:center">
          <?php if ($punyaTurunan): ?>
            Lihat Turunan
          <?php elseif ($kpi['polarity'] === 'special'): ?>
            <?= ((float)($kpi['realisasi'] ?? 0) == 1.0) ? 'Ada' : 'Tidak Ada' ?>
          <?php elseif ($kpi['polarity'] === 'tertimbang'): ?>
            <?= number_format((float)($kpi['realisasi'] ?? 0),2) ?> / H:<?= number_format((float)($kpi['realisasi_harian'] ?? 0),2) ?>%
          <?php else: ?>
            <?= number_format((float)($kpi['realisasi'] ?? 0),2) ?>
          <?php endif; ?>
        </td>
        <td style="text-align:center"><?= $kpi['skor'] !== null ? number_format((float)$kpi['skor'],2) : '—' ?></td>
        <td style="text-align:center"><b><?= $kpi['nilai_kontribusi'] !== null ? number_format((float)$kpi['nilai_kontribusi'],2) : '—' ?></b></td>
      </tr>
      <?php foreach ($kpi['turunan'] as $t): ?>
      <tr class="turunan">
        <td style="text-align:left;padding-left:16px">↳ <?= esc($t['nama_turunan']) ?></td>
        <td style="text-align:center">—</td>
        <td style="text-align:center"><?= $polarityLabels[$t['polarity']] ?? '—' ?></td>
        <td style="text-align:center"><?= round((float)$t['bobot']*100,1) ?>%</td>
        <td style="text-align:center">
          <?= $t['polarity'] === 'special' ? '—' : number_format((float)($t['target'] ?? 0),2) ?>
        </td>
        <td style="text-align:center">
          <?php if ($t['polarity'] === 'special'): ?>
            <?= ((float)($t['realisasi'] ?? 0) == 1.0) ? 'Ada' : 'Tidak Ada' ?>
          <?php elseif ($t['polarity'] === 'tertimbang'): ?>
            <?= number_format((float)($t['realisasi'] ?? 0),2) ?> / H:<?= number_format((float)($t['realisasi_harian'] ?? 0),2) ?>%
          <?php else: ?>
            <?= number_format((float)($t['realisasi'] ?? 0),2) ?>
          <?php endif; ?>
        </td>
        <td style="text-align:center"><?= $t['skor'] !== null ? number_format((float)$t['skor'],2) : '—' ?></td>
        <td style="text-align:center"><?= $t['nilai_kontribusi'] !== null ? number_format((float)$t['nilai_kontribusi'],4) : '—' ?></td>
      </tr>
      <?php endforeach; ?>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endforeach; ?>
</div>
<?php endforeach; ?>

<div class="footer">Dokumen ini dihasilkan otomatis dari sistem KPI — Arsip Periode Tertutup.</div>
</body>
</html>
