<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h5 class="mb-0 fw-semibold" style="color:#1F4E79">
      <i class="ti ti-list-check me-1"></i> Master KPI
    </h5>
    <small class="text-muted">Kelola 16 KPI beserta bobot dan polarity</small>
  </div>
  <a href="<?= base_url('master/kpi/create') ?>"
     class="btn btn-primary btn-sm d-flex align-items-center gap-1">
    <i class="ti ti-plus"></i> Tambah KPI
  </a>
</div>

<!-- Info total bobot -->
<?php
$bobot_pct = round($totalBobot * 100, 2);
$bobot_ok  = $bobot_pct == 100;
?>
<div class="alert d-flex align-items-center gap-2 py-2 mb-3
     <?= $bobot_ok ? 'alert-success' : 'alert-warning' ?>">
  <i class="ti ti-<?= $bobot_ok ? 'circle-check' : 'alert-triangle' ?> fs-5"></i>
  <span style="font-size:13px">
    Total bobot semua KPI: <strong><?= $bobot_pct ?>%</strong>
    <?= $bobot_ok ? '— Sudah tepat 100%' : '— Harus tepat 100%!' ?>
  </span>
</div>

<?php
$perspektif_colors = [
    'Financial'        => ['bg'=>'#E6F1FB','border'=>'#2E75B6','text'=>'#1F4E79'],
    'Customer'         => ['bg'=>'#EAF3DE','border'=>'#70AD47','text'=>'#375623'],
    'Internal Process' => ['bg'=>'#FFF3CD','border'=>'#BF9000','text'=>'#7F6000'],
    'Learning & Growth'=> ['bg'=>'#F3E5F5','border'=>'#9B59B6','text'=>'#5C2A6B'],
];
?>

<?php foreach ($grouped as $perspektif => $kpis): ?>
<?php $c = $perspektif_colors[$perspektif] ?? ['bg'=>'#f8f9fa','border'=>'#dee2e6','text'=>'#333']; ?>
<div class="card mb-3 border-0 shadow-sm">
  <div class="card-header d-flex align-items-center justify-content-between py-2"
       style="background:<?= $c['bg'] ?>;border-left:4px solid <?= $c['border'] ?>">
    <span class="fw-semibold" style="color:<?= $c['text'] ?>;font-size:13px">
      <?= esc($perspektif) ?>
    </span>
    <?php
    $total_perspektif = array_sum(array_column($kpis, 'bobot'));
    ?>
    <span class="badge" style="background:<?= $c['border'] ?>;font-size:11px">
      Total: <?= round($total_perspektif * 100, 2) ?>%
    </span>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-sm table-hover align-middle mb-0" style="font-size:13px">
        <thead style="background:#f8fafc">
          <tr>
            <th style="width:40px">No</th>
            <th>Nama KPI</th>
            <th style="width:80px">Kode</th>
            <th style="width:70px">Satuan</th>
            <th style="width:80px" class="text-center">Bobot</th>
            <th style="width:90px" class="text-center">Polarity</th>
            <th style="width:80px" class="text-center">Perubahan</th>
            <th style="width:70px" class="text-center">Kualitatif</th>
            <th style="width:70px" class="text-center">Status</th>
            <th style="width:100px" class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($kpis as $kpi): ?>
          <tr class="<?= !$kpi['is_active'] ? 'text-muted' : '' ?>">
            <td><?= $kpi['urutan'] ?></td>
            <td>
              <span class="fw-semibold"><?= esc($kpi['nama_kpi']) ?></span>
            </td>
            <td>
              <code style="font-size:11px;background:#f0f4ff;padding:2px 6px;
                           border-radius:4px;color:#2E75B6">
                <?= esc($kpi['kode']) ?>
              </code>
            </td>
            <td><?= esc($kpi['satuan']) ?></td>
            <td class="text-center fw-semibold" style="color:#1F4E79">
              <?= round($kpi['bobot'] * 100, 2) ?>%
            </td>
            <td class="text-center">
              <?php if ($kpi['polarity'] === 'max'): ?>
                <span class="badge" style="background:#E2EFDA;color:#375623;font-size:11px">
                  ↑ Maximize
                </span>
              <?php else: ?>
                <span class="badge" style="background:#FCE4D6;color:#C00000;font-size:11px">
                  ↓ Minimize
                </span>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <?php if ($kpi['perubahan_polarity'] === 'pos'): ?>
                <span class="badge" style="background:#E2EFDA;color:#375623;font-size:11px">
                  Positif
                </span>
              <?php else: ?>
                <span class="badge" style="background:#FCE4D6;color:#C00000;font-size:11px">
                  Negatif
                </span>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <?php if ($kpi['is_kualitatif']): ?>
                <span class="badge bg-purple text-white"
                      style="background:#5C2A6B!important;font-size:11px">
                  Ya
                </span>
              <?php else: ?>
                <span class="text-muted" style="font-size:12px">—</span>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <a href="<?= base_url("master/kpi/toggle/{$kpi['id']}") ?>"
                 class="badge text-decoration-none"
                 style="font-size:11px;background:<?= $kpi['is_active'] ? '#C6EFCE' : '#FCE4D6' ?>;
                        color:<?= $kpi['is_active'] ? '#375623' : '#C00000' ?>">
                <?= $kpi['is_active'] ? 'Aktif' : 'Nonaktif' ?>
              </a>
            </td>
            <td class="text-center">
              <div class="d-flex gap-1 justify-content-center">
                <a href="<?= base_url("master/kpi/edit/{$kpi['id']}") ?>"
                   class="btn btn-xs btn-outline-primary"
                   style="padding:2px 8px;font-size:11px">
                  <i class="ti ti-edit"></i>
                </a>
                <a href="<?= base_url("master/kpi/delete/{$kpi['id']}") ?>"
                   class="btn btn-xs btn-outline-danger"
                   style="padding:2px 8px;font-size:11px"
                   onclick="return confirmAction(event, { title: 'Hapus KPI', text: 'Hapus KPI ini?', confirmText: 'Ya, Hapus', danger: true })">
                  <i class="ti ti-trash"></i>
                </a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endforeach; ?>