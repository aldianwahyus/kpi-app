<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h5 class="mb-0 fw-semibold" style="color:#1F4E79">
      <i class="ti ti-building me-1"></i> Master Direktorat
    </h5>
    <small class="text-muted">Kelola direktorat dan KPI Unit-nya</small>
  </div>
  <a href="<?= base_url('master/direktorat/create') ?>"
     class="btn btn-primary btn-sm">
    <i class="ti ti-plus me-1"></i> Tambah Direktorat
  </a>
</div>

<div class="row g-3">
<?php foreach ($list as $d): ?>
<?php
$s         = $summary[$d['id']] ?? ['total_kpi'=>0,'total_bobot'=>0];
$bobot_pct = round($s['total_bobot'] * 100, 2);
$bobot_ok  = $bobot_pct == 100;
?>
<div class="col-md-6">
  <div class="card border-0 shadow-sm">
    <div class="card-body">
      <div class="d-flex align-items-start justify-content-between mb-2">
        <div>
          <div class="fw-semibold" style="color:#1F4E79;font-size:14px">
            <?= esc($d['nama']) ?>
          </div>
          <code style="font-size:11px;color:#888"><?= esc($d['kode']) ?></code>
          <?php if ($d['deskripsi']): ?>
            <div class="text-muted mt-1" style="font-size:12px">
              <?= esc($d['deskripsi']) ?>
            </div>
          <?php endif; ?>
        </div>
        <div class="d-flex gap-1">
          <a href="<?= base_url("master/direktorat/edit/{$d['id']}") ?>"
            class="btn btn-outline-primary btn-sm"
            style="padding:2px 8px;font-size:12px">
            <i class="ti ti-edit"></i>
          </a>
          <a href="<?= base_url("master/kpi-unit/{$d['id']}") ?>"
            class="btn btn-outline-success btn-sm"
            style="padding:2px 8px;font-size:12px">
            <i class="ti ti-list-check me-1"></i> KPI Unit
          </a>
          <!-- Tambahkan tombol hapus ini -->
          <a href="<?= base_url("master/direktorat/delete/{$d['id']}") ?>"
            class="btn btn-outline-danger btn-sm"
            style="padding:2px 8px;font-size:12px"
            onclick="return confirmAction(event, { title: 'Hapus Direktorat', text: 'Hapus direktorat <?= esc($d['nama'], 'js') ?>? Pastikan tidak ada unit kerja dan KPI Unit yang terhubung.', confirmText: 'Ya, Hapus', danger: true })">
            <i class="ti ti-trash"></i>
          </a>
        </div>
      </div>
      <hr class="my-2">
      <div class="row g-2 text-center">
        <div class="col-4">
          <div class="fw-bold" style="font-size:20px;color:#1F4E79">
            <?= $s['total_kpi'] ?>
          </div>
          <div style="font-size:11px;color:#888">KPI Unit</div>
        </div>
        <div class="col-4">
          <div class="fw-bold" style="font-size:20px;
            color:<?= $bobot_ok ? '#375623' : '#BF9000' ?>">
            <?= $bobot_pct ?>%
          </div>
          <div style="font-size:11px;color:#888">Total Bobot</div>
        </div>
        <div class="col-4">
          <?php if ($s['total_kpi'] == 0): ?>
            <span class="badge" style="background:#FCE4D6;color:#C00000;font-size:11px">
              Belum ada KPI
            </span>
          <?php elseif (!$bobot_ok): ?>
            <span class="badge" style="background:#FFF2CC;color:#7F6000;font-size:11px">
              Bobot belum 100%
            </span>
          <?php else: ?>
            <span class="badge" style="background:#C6EFCE;color:#375623;font-size:11px">
              ✓ Siap
            </span>
          <?php endif; ?>
          <div style="font-size:11px;color:#888;margin-top:2px">Status</div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endforeach; ?>
</div>