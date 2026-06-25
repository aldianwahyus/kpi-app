<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: Arial, sans-serif; font-size: 11px; color: #222; }

    .header { background: #1F4E79; color: white; padding: 12px 16px; margin-bottom: 12px; }
    .header h1 { font-size: 16px; margin-bottom: 2px; }
    .header p  { font-size: 10px; opacity: .8; }

    table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    th { background: #2E75B6; color: white; padding: 6px 8px; text-align: center; font-size: 10px; }
    td { padding: 5px 8px; border-bottom: 0.5px solid #e0e0e0; font-size: 10px; }
    tr:nth-child(even) td { background: #f5f8fc; }

    /* ── FIX: Update Class CSS Warna Skema Grade Baru ── */
    .grade-M  { background: #1F4E79 !important; color: white; font-weight: bold; }
    .grade-SB { background: #E2EFDA !important; color: #375623; font-weight: bold; }
    .grade-B  { background: #DEEBF7 !important; color: #1F4E79; font-weight: bold; }
    .grade-C  { background: #FFF2CC !important; color: #7F6000; font-weight: bold; }

    .text-center { text-align: center; }
    .text-right  { text-align: right; }
    .footer { font-size: 9px; color: #888; text-align: right; margin-top: 8px; }
  </style>
</head>
<body>

<div class="header">
  <h1>Rekap Penilaian KPI Pegawai</h1>
  <p>Periode: <?= esc($periode['nama']) ?>&nbsp;|&nbsp; Dicetak: <?= $tanggal ?></p>
</div>

<?php
$total = count($rekap);
$avg   = $total > 0 ? round(array_sum(array_column($rekap,'nilai_akhir'))/$total,2) : 0;
$max   = $total > 0 ? round(max(array_column($rekap,'nilai_akhir')),2) : 0;
$min   = $total > 0 ? round(min(array_column($rekap,'nilai_akhir')),2) : 0;
?>

<table style="width:100%; margin-bottom:12px; table-layout: fixed;">
  <tr>
    <td style="padding:4px 8px; background:#f0f4f8; border:1px solid #dde; text-align:center;">
      <b>Total Pegawai</b><br>
      <span style="font-size:16px; color:#1F4E79; font-weight:bold"><?= $total ?></span>
    </td>
    <td style="padding:4px 8px; background:#f0f4f8; border:1px solid #dde; text-align:center;">
      <b>Rata-rata Nilai</b><br>
      <span style="font-size:16px; color:#1F4E79; font-weight:bold"><?= $avg ?></span>
    </td>
    <td style="padding:4px 8px; background:#f0f4f8; border:1px solid #dde; text-align:center;">
      <b>Nilai Tertinggi</b><br>
      <span style="font-size:16px; color:#375623; font-weight:bold"><?= $max ?></span>
    </td>
    <td style="padding:4px 8px; background:#f0f4f8; border:1px solid #dde; text-align:center;">
      <b>Nilai Terendah</b><br>
      <span style="font-size:16px; color:#C00000; font-weight:bold"><?= $min ?></span>
    </td>
    
    <td style="padding:4px 8px; border:1px solid #dde; text-align:center;" class="grade-M">
      <b>Grade M</b><br>
      <span style="font-size:16px;"><?= $distribusi['M'] ?? 0 ?></span>
    </td>
    <td style="padding:4px 8px; border:1px solid #dde; text-align:center;" class="grade-SB">
      <b>Grade SB</b><br>
      <span style="font-size:16px;"><?= $distribusi['SB'] ?? 0 ?></span>
    </td>
    <td style="padding:4px 8px; border:1px solid #dde; text-align:center;" class="grade-B">
      <b>Grade B</b><br>
      <span style="font-size:16px;"><?= $distribusi['B'] ?? 0 ?></span>
    </td>
    <td style="padding:4px 8px; border:1px solid #dde; text-align:center;" class="grade-C">
      <b>Grade C</b><br>
      <span style="font-size:16px;"><?= $distribusi['C'] ?? 0 ?></span>
    </td>
  </tr>
</table>

<table>
  <thead>
    <tr>
      <th style="width:30px">No</th>
      <th style="text-align:left">Nama Pegawai</th>
      <th style="text-align:left">Jabatan</th>
      <th style="text-align:left">Divisi</th>
      <th>Jml KPI</th>
      <th>Nilai KPI</th>
      <th>Grade</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($rekap as $i => $r): ?>
    <tr>
      <td class="text-center"><?= $i+1 ?></td>
      <td><b><?= esc($r['nama']) ?></b></td>
      <td><?= esc($r['jabatan'] ?? '—') ?></td>
      <td><?= esc($r['divisi'] ?? '—') ?></td>
      <td class="text-center"><?= $r['jumlah_kpi'] ?></td>
      <td class="text-center">
        <b><?= number_format((float)$r['nilai_akhir'],2) ?></b>
      </td>
      <td class="text-center grade-<?= $r['grade'] ?? '' ?>">
        <?= $r['grade'] ?? '—' ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<div class="footer">
  Dicetak oleh: <?= esc(session()->get('nama')) ?> &nbsp;|&nbsp; <?= date('d F Y H:i') ?>
</div>
</body>
</html>