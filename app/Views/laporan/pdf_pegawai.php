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

    .info-table { width: 100%; margin-bottom: 12px; border-collapse: collapse; }
    .info-table td { padding: 5px 8px; border: 0.5px solid #dde; font-size: 10px; }
    .info-table .label { background: #BDD7EE; font-weight: bold; color: #1F4E79; width: 140px; }

    table.kpi { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    table.kpi th { background: #2E75B6; color: white; padding: 5px 6px; font-size: 9px; text-align: center; }
    table.kpi td { padding: 4px 6px; border-bottom: 0.5px solid #e0e0e0; font-size: 9px; }
    table.kpi tr:nth-child(even) td { background: #f5f8fc; }

    .persp-header { background: #E6F1FB; color: #1F4E79; font-weight: bold;
                    padding: 4px 8px; font-size: 10px;
                    border-left: 3px solid #2E75B6; margin: 8px 0 4px; }

    /* Warna box biodata sesuai skema kriteria pencapaian (Istimewa/Baik/Cukup/Kurang) */
    .grade-IS { background: #1E7A55 !important; color: white; }
    .grade-SB { background: #A9D18E !important; color: #1E4620; }
    .grade-B  { background: #FFC000 !important; color: #7F6000; }
    .grade-C  { background: #FCE4D6 !important; color: #C00000; }

    .cap-A { background: #C6EFCE; }
    .cap-B { background: #BDD7EE; }
    .cap-C { background: #FFF2CC; }
    .cap-D { background: #FCE4D6; }

    .footer { font-size: 9px; color: #888; text-align: right; margin-top: 10px; }
  </style>
</head>
<body>

<div class="header">
  <h1>Laporan Penilaian KPI — <?= esc($pegawai['nama']) ?></h1>
  <p>Periode: <?= esc($periode['nama']) ?>&nbsp;|&nbsp; Dicetak: <?= $tanggal ?></p>
</div>

<table class="info-table">
  <tr>
    <td class="label">Nama Pegawai</td>
    <td><?= esc($pegawai['nama']) ?></td>
    <td class="label">Nilai KPI Akhir</td>
    <td><b style="font-size:14px;color:#1F4E79"><?= number_format($nilaiAkhir,2) ?></b></td>
  </tr>
  <tr>
    <td class="label">Jabatan</td>
    <td><?= esc($pegawai['jabatan'] ?? '—') ?></td>
    <td class="label">Grade</td>
    <td class="grade-<?= esc($grade, 'attr') ?>">
      <b style="font-size:14px"><?= esc($grade) ?></b> — <?= esc($gradeLabel) ?>
    </td>
  </tr>
  <tr>
    <td class="label">Periode</td>
    <td><?= esc($periode['nama']) ?></td>
    <td class="label">Capaian per Perspektif</td>
    <td>
      <?php foreach ($perspektifRekap as $pr): ?>
        <?= esc($pr['perspektif']) ?>: <b><?= round($pr['avg_capaian'],2) ?> / 4</b> &nbsp;
      <?php endforeach; ?>
    </td>
  </tr>
</table>

<?php
$persp_colors = [
    'Financial'         => ['E6F1FB','1F4E79','2E75B6'],
    'Customer'          => ['EAF3DE','375623','70AD47'],
    'Internal Process'  => ['FFF3CD','7F6000','BF9000'],
    'Learning & Growth' => ['F3E5F5','5C2A6B','9B59B6'],
];
?>

<?php foreach ($grouped as $perspektif => $kpis): ?>
<?php $pc = $persp_colors[$perspektif] ?? ['f8f9fa','333','888']; ?>
<div class="persp-header" style="background:#<?= $pc[0] ?>; color:#<?= $pc[1] ?>; border-left-color:#<?= $pc[2] ?>">
  <?= esc($perspektif) ?> — Kontribusi: <?= round(array_sum(array_column($kpis,'nilai_kontribusi')),2) ?>
</div>

<table class="kpi">
  <thead>
    <tr>
      <th style="text-align:left;width:35%">Nama KPI</th>
      <th>Kode</th>
      <th>Bobot</th>
      <th>Polarity</th>
      <th>Target</th>
      <th>Realisasi</th>
      <th>Capaian %</th>
      <th>Kontribusi</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($kpis as $kpi): ?>
    <?php
    $cap = (float)$kpi['capaian'];
    $capCls = $cap>=1 ? 'cap-A' : ($cap>=0.76 ? 'cap-B' : ($cap>=0.61 ? 'cap-C' : 'cap-D'));
    ?>
    <tr>
      <td><b><?= esc($kpi['nama_kpi']) ?></b></td>
      <td style="text-align:center"><?= esc($kpi['kode']) ?></td>
      <td style="text-align:center"><?= round($kpi['bobot']*100,1) ?>%</td>
      <?php
        $pdfPolarityIcons = ['max'=>'↑','min'=>'↓','precise'=>'◎','special'=>'⚑','tertimbang'=>'⚖'];
      ?>
      <td style="text-align:center"><?= $pdfPolarityIcons[$kpi['polarity']] ?? '—' ?></td>
      <td style="text-align:center"><?= $kpi['polarity'] === 'special' ? '—' : number_format($kpi['target'],2) ?></td>
      <td style="text-align:center">
        <?php if ($kpi['polarity'] === 'special'): ?>
          <?= ((float)($kpi['realisasi'] ?? 0) == 1.0) ? 'Ada' : 'Tidak Ada' ?>
        <?php elseif ($kpi['polarity'] === 'tertimbang'): ?>
          <?= number_format((float)($kpi['realisasi'] ?? 0),2) ?> /
          H: <?= number_format((float)($kpi['realisasi_harian'] ?? 0),2) ?>%
        <?php else: ?>
          <?= number_format($kpi['realisasi'],2) ?>
        <?php endif; ?>
      </td>
      <td style="text-align:center" class="<?= $capCls ?>"><b><?= round($cap*100,2) ?>%</b></td>
      <td style="text-align:center"><?= round($kpi['nilai_kontribusi'],2) ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endforeach; ?>

<div class="footer">
  Dicetak oleh: <?= esc(session()->get('nama')) ?> &nbsp;|&nbsp; <?= date('d F Y H:i') ?>
</div>
</body>
</html>