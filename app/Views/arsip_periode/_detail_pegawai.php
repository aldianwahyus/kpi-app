<div class="d-flex align-items-center gap-2 mb-3">
  <a href="<?= base_url("arsip-periode/detail/{$periode['id']}") ?>" class="btn btn-sm btn-light border">
    <i class="ti ti-arrow-left"></i>
  </a>
  <div>
    <h5 class="mb-0 fw-semibold" style="color:#1F4E79">
      Arsip Detail — <?= esc($pegawai['pegawai_nama']) ?>
    </h5>
    <small class="text-muted">
      <?= esc($pegawai['pegawai_jabatan'] ?? '') ?>
      &nbsp;·&nbsp; <?= esc($pegawai['divisi_nama'] ?? '—') ?>
      &nbsp;·&nbsp; Periode: <strong><?= esc($periode['nama']) ?></strong>
      &nbsp;·&nbsp; <span class="badge" style="background:#FCE4D6;color:#C00000">Ditutup</span>
    </small>
  </div>
</div>

<div class="alert py-2 mb-3 d-flex align-items-center gap-2" style="background:#E6F1FB;border:1px solid #2E75B6;font-size:12px">
  <i class="ti ti-info-circle" style="color:#1F4E79"></i>
  <span style="color:#1F4E79">
    Data ini adalah snapshot beku pada saat Periode ditutup, termasuk konfigurasi Bobot/Target KPI saat itu.
  </span>
</div>

<!-- Summary nilai -->
<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="stat-card text-center" style="border:2px solid #1F4E79">
      <div style="font-size:36px;font-weight:700;color:#1F4E79">
        <?= number_format($nilaiAkhir, 2) ?>
      </div>
      <div class="stat-label fw-semibold">Nilai KPI Akhir</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card text-center">
      <?php
      $gc = match($grade ?? '') {
          'IS' => ['#1E7A55','#FFFFFF'],
          'SB' => ['#A9D18E','#1E4620'],
          'B'  => ['#FFC000','#7F6000'],
          'C'  => ['#FCE4D6','#C00000'],
          default => ['#f0f0f0','#888'],
      };
      ?>
      <div style="font-size:42px;font-weight:700;color:<?= $gc[1] ?>;background:<?= $gc[0] ?>;border-radius:12px;padding:8px 20px;display:inline-block">
        <?= $grade ?? '—' ?>
      </div>
      <div class="stat-label mt-1"><?= $gradeLabel ?></div>
    </div>
  </div>
</div>

<?php
$persp_style = [
    'Financial'         => ['bg'=>'#E6F1FB','border'=>'#2E75B6','text'=>'#1F4E79'],
    'Customer'          => ['bg'=>'#EAF3DE','border'=>'#70AD47','text'=>'#375623'],
    'Internal Process'  => ['bg'=>'#FFF3CD','border'=>'#BF9000','text'=>'#7F6000'],
    'Learning & Growth' => ['bg'=>'#F3E5F5','border'=>'#9B59B6','text'=>'#5C2A6B'],
];
$polarityLabels = [
    'max'        => '↑ Max', 'min' => '↓ Min', 'precise' => '◎ Precise',
    'special'    => '⚑ Special', 'tertimbang' => '⚖ Tertimbang',
];
?>

<?php foreach ($grouped as $perspektif => $kpis): ?>
<?php $ps = $persp_style[$perspektif] ?? ['bg'=>'#f8f9fa','border'=>'#dee2e6','text'=>'#333']; ?>
<div class="card mb-3 border-0 shadow-sm">
  <div class="card-header py-2 d-flex align-items-center justify-content-between"
       style="background:<?= $ps['bg'] ?>;border-left:4px solid <?= $ps['border'] ?>">
    <span class="fw-semibold" style="color:<?= $ps['text'] ?>;font-size:13px"><?= esc($perspektif) ?></span>
    <?php $kontribusiPersp = array_sum(array_column($kpis, 'nilai_kontribusi')); ?>
    <span class="badge" style="background:<?= $ps['border'] ?>;font-size:11px">
      Kontribusi: <?= round($kontribusiPersp, 2) ?>
    </span>
  </div>
  <div class="card-body p-0">
    <table class="table table-sm align-middle mb-0" style="font-size:13px">
      <thead style="background:#f8fafc">
        <tr>
          <th>Nama Parameter KPI</th>
          <th style="width:90px" class="text-center">Polarity</th>
          <th style="width:80px" class="text-center">Bobot</th>
          <th style="width:90px" class="text-center">Target</th>
          <th style="width:90px" class="text-center">Realisasi</th>
          <th style="width:70px" class="text-center">Skor</th>
          <th style="width:90px" class="text-center">Total</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($kpis as $kpi): ?>
        <?php $punyaTurunan = !empty($kpi['turunan']); ?>
        <tr>
          <td>
            <span class="fw-semibold"><?= esc($kpi['kpi_nama']) ?></span>
            <small class="text-muted d-block" style="font-size:11px">
              <code style="font-size:10px"><?= esc($kpi['kpi_kode']) ?></code>
              &nbsp;<?= esc($kpi['kpi_satuan'] ?? '') ?>
            </small>
          </td>
          <td class="text-center" style="font-size:11px"><?= $polarityLabels[$kpi['polarity']] ?? '—' ?></td>
          <td class="text-center fw-semibold" style="color:#1F4E79"><?= round((float)$kpi['bobot'] * 100, 1) ?>%</td>
          <td class="text-center">
            <?= $kpi['polarity'] === 'special' ? '—' : number_format((float)($kpi['target'] ?? 0), 2) ?>
          </td>
          <td class="text-center">
            <?php if ($punyaTurunan): ?>
              <span class="text-muted" style="font-size:11px">Lihat Turunan</span>
            <?php elseif ($kpi['polarity'] === 'special'): ?>
              <?= ((float)($kpi['realisasi'] ?? 0) == 1.0) ? 'Ada' : 'Tidak Ada' ?>
            <?php elseif ($kpi['polarity'] === 'tertimbang'): ?>
              <?= number_format((float)($kpi['realisasi'] ?? 0), 2) ?>
              <small class="text-muted d-block" style="font-size:10px">Harian: <?= number_format((float)($kpi['realisasi_harian'] ?? 0), 2) ?>%</small>
            <?php else: ?>
              <?= number_format((float)($kpi['realisasi'] ?? 0), 2) ?>
            <?php endif; ?>
          </td>
          <td class="text-center fw-semibold"><?= $kpi['skor'] !== null ? number_format((float)$kpi['skor'], 2) : '—' ?></td>
          <td class="text-center fw-bold" style="color:#1F4E79">
            <?= $kpi['nilai_kontribusi'] !== null ? number_format((float)$kpi['nilai_kontribusi'], 2) : '—' ?>
          </td>
        </tr>
        <?php foreach ($kpi['turunan'] as $t): ?>
        <tr style="background:#FAFBFC">
          <td style="padding-left:32px">
            <i class="ti ti-corner-down-right me-1" style="color:#aaa"></i>
            <span style="font-size:12px"><?= esc($t['nama_turunan']) ?></span>
          </td>
          <td class="text-center" style="font-size:11px"><?= $polarityLabels[$t['polarity']] ?? '—' ?></td>
          <td class="text-center" style="font-size:12px"><?= round((float)$t['bobot'] * 100, 1) ?>%</td>
          <td class="text-center" style="font-size:12px">
            <?= $t['polarity'] === 'special' ? '—' : number_format((float)($t['target'] ?? 0), 2) ?>
          </td>
          <td class="text-center" style="font-size:12px">
            <?php if ($t['polarity'] === 'special'): ?>
              <?= ((float)($t['realisasi'] ?? 0) == 1.0) ? 'Ada' : 'Tidak Ada' ?>
            <?php elseif ($t['polarity'] === 'tertimbang'): ?>
              <?= number_format((float)($t['realisasi'] ?? 0), 2) ?>
              <small class="text-muted d-block" style="font-size:10px">Harian: <?= number_format((float)($t['realisasi_harian'] ?? 0), 2) ?>%</small>
            <?php else: ?>
              <?= number_format((float)($t['realisasi'] ?? 0), 2) ?>
            <?php endif; ?>
          </td>
          <td class="text-center" style="font-size:12px"><?= $t['skor'] !== null ? number_format((float)$t['skor'], 2) : '—' ?></td>
          <td class="text-center fw-semibold" style="font-size:12px;color:#1F4E79">
            <?= $t['nilai_kontribusi'] !== null ? number_format((float)$t['nilai_kontribusi'], 4) : '—' ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endforeach; ?>
