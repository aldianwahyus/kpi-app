<div class="d-flex align-items-center gap-2 mb-3">
  <a href="<?= base_url('rekap?periode_id=' . $periodeId) ?>"
     class="btn btn-sm btn-light border">
    <i class="ti ti-arrow-left"></i>
  </a>
  <div>
    <h5 class="mb-0 fw-semibold" style="color:#1F4E79">
      Detail KPI — <?= esc($pegawai['nama']) ?>
    </h5>
    <small class="text-muted">
      <?= esc($pegawai['jabatan'] ?? '') ?>
      &nbsp;·&nbsp; Periode: <strong><?= esc($periode['nama']) ?></strong>
    </small>
  </div>
  <div class="ms-auto d-flex gap-2">
    <a href="<?= base_url("laporan/pdf-pegawai/{$pegawai['id']}?periode_id=$periodeId") ?>"
       class="btn btn-sm btn-outline-danger">
      <i class="ti ti-file-text me-1"></i> Export PDF
    </a>
  </div>
</div>

<!-- Summary nilai -->
<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="stat-card text-center"
         style="border:2px solid #1F4E79">
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
          'M'  => ['#1F4E79','#FFFFFF'],
          'SB' => ['#C6EFCE','#375623'],
          'B'  => ['#BDD7EE','#1F4E79'],
          'C'  => ['#FFF2CC','#7F6000'],
          default => ['#f0f0f0','#888'],
      };
      ?>
      <div style="font-size:42px;font-weight:700;
                  color:<?= $gc[1] ?>;background:<?= $gc[0] ?>;
                  border-radius:12px;padding:8px 20px;display:inline-block">
        <?= $grade ?? '—' ?>
      </div>
      <div class="stat-label mt-1"><?= $gradeLabel ?></div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="stat-card">
      <div class="fw-semibold mb-2" style="font-size:13px;color:#1F4E79">
        Capaian per Perspektif
      </div>
      <?php
      $persp_colors = [
          'Financial'        => ['#2E75B6','#BDD7EE'],
          'Customer'         => ['#375623','#C6EFCE'],
          'Internal Process' => ['#BF9000','#FFF2CC'],
          'Learning & Growth'=> ['#5C2A6B','#F3E5F5'],
      ];
      ?>
      <?php foreach ($perspektifRekap as $row): ?>
      <?php 
        $pc = $persp_colors[$row['perspektif']] ?? ['#888','#f0f0f0']; 
        // Ambil nilai capaian dengan aman dari key yang mungkin tersedia
        $nilaiCapaian = (float)($row['avg_capaian'] ?? $row['capaian'] ?? $row['rata_rata'] ?? 0);
      ?>
      <div class="d-flex align-items-center gap-2 mb-2">
        <div style="width:130px;font-size:11px;color:#555;flex-shrink:0">
          <?= esc($row['perspektif']) ?>
        </div>
        <div class="flex-grow-1">
          <div class="progress" style="height:8px">
            <div class="progress-bar"
                 style="width:<?= min(100, round($nilaiCapaian, 1)) ?>%;
                        background:<?= $pc[0] ?>">
            </div>
          </div>
        </div>
        <div style="width:50px;text-align:right;font-size:12px;
                    font-weight:600;color:<?= $pc[0] ?>">
          <?= round($nilaiCapaian, 1) ?>%
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Detail per KPI -->
<?php
$persp_style = [
    'Financial'        => ['bg'=>'#E6F1FB','border'=>'#2E75B6','text'=>'#1F4E79'],
    'Customer'         => ['bg'=>'#EAF3DE','border'=>'#70AD47','text'=>'#375623'],
    'Internal Process' => ['bg'=>'#FFF3CD','border'=>'#BF9000','text'=>'#7F6000'],
    'Learning & Growth'=> ['bg'=>'#F3E5F5','border'=>'#9B59B6','text'=>'#5C2A6B'],
];
?>

<?php foreach ($detailGrouped as $perspektif => $kpis): ?>
<?php $ps = $persp_style[$perspektif] ?? ['bg'=>'#f8f9fa','border'=>'#dee2e6','text'=>'#333']; ?>
<div class="card mb-3 border-0 shadow-sm">
  <div class="card-header py-2 d-flex align-items-center
              justify-content-between"
       style="background:<?= $ps['bg'] ?>;
              border-left:4px solid <?= $ps['border'] ?>">
    <span class="fw-semibold" style="color:<?= $ps['text'] ?>;font-size:13px">
      <?= esc($perspektif) ?>
    </span>
    <?php
    $kontribusi_persp = array_sum(array_column($kpis, 'nilai_kontribusi'));
    ?>
    <span class="badge"
          style="background:<?= $ps['border'] ?>;font-size:11px">
      Kontribusi: <?= round($kontribusi_persp * 100, 2) ?>
    </span>
  </div>
  <div class="card-body p-0">
    <table class="table table-sm align-middle mb-0" style="font-size:13px">
      <thead style="background:#f8fafc">
        <tr>
          <th>KPI</th>
          <th style="width:60px">Satuan</th>
          <th style="width:70px" class="text-center">Bobot</th>
          <th style="width:70px" class="text-center">Polarity</th>
          <th style="width:100px" class="text-center">Target</th>
          <th style="width:100px" class="text-center">Realisasi</th>
          <th style="width:90px" class="text-center">Capaian %</th>
          <th style="width:100px" class="text-center">Kontribusi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($kpis as $kpi): ?>
        <?php
        $cap = (float)$kpi['capaian'];
        if ($cap >= 1)      { $cbg='#C6EFCE'; $cc='#375623'; }
        elseif ($cap>=0.76) { $cbg='#BDD7EE'; $cc='#1F4E79'; }
        elseif ($cap>=0.61) { $cbg='#FFF2CC'; $cc='#7F6000'; }
        else                { $cbg='#FCE4D6'; $cc='#C00000'; }
        ?>
        <tr>
          <td>
            <div class="fw-semibold"><?= esc($kpi['nama_kpi']) ?></div>
            <small class="text-muted">
              <code style="font-size:10px"><?= esc($kpi['kode']) ?></code>
            </small>
          </td>
          <td><?= esc($kpi['satuan']) ?></td>
          <td class="text-center fw-semibold" style="color:#1F4E79">
            <?= round($kpi['bobot']*100,1) ?>%
          </td>
          <td class="text-center">
            <span style="color:<?= $kpi['polarity']==='max'?'#375623':'#C00000' ?>;
                         font-weight:600">
              <?= $kpi['polarity']==='max' ? '↑' : '↓' ?>
            </span>
          </td>
          <td class="text-center"><?= number_format($kpi['target'],2) ?></td>
          <td class="text-center"><?= number_format($kpi['realisasi'],2) ?></td>
          <td class="text-center">
            <span class="badge"
                  style="background:<?= $cbg ?>;color:<?= $cc ?>;
                         font-size:12px;min-width:60px">
              <?= round($cap*100,2) ?>%
            </span>
          </td>
          <td class="text-center fw-semibold" style="color:#1F4E79">
            <?= round($kpi['nilai_kontribusi']*100,2) ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endforeach; ?>

<?php if (empty($detailGrouped)): ?>
  <div class="text-center py-5 text-muted">
    <i class="ti ti-clipboard-off fs-1 d-block mb-2"></i>
    Belum ada data penilaian untuk pegawai ini pada periode ini.
  </div>
<?php endif; ?>