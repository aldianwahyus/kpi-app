<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h5 class="mb-0 fw-semibold" style="color:#1F4E79">
      <i class="ti ti-sitemap me-1"></i> KPI per Divisi
    </h5>
    <small class="text-muted">
      Assign dan kelola KPI yang berlaku untuk setiap divisi
    </small>
  </div>
</div>

<div class="row g-3">
  <?php foreach ($divisiList as $div): ?>
  <?php
  $s          = $summary[$div['id']] ?? ['jumlah_kpi'=>0,'total_bobot'=>0];
  $bobot_pct  = round($s['total_bobot'] * 100, 2);
  $bobot_ok   = $bobot_pct == 100;
  $has_kpi    = $s['jumlah_kpi'] > 0;
  ?>
  <div class="col-md-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div>
            <div class="fw-semibold" style="color:#1F4E79;font-size:14px">
              <?= esc($div['nama']) ?>
            </div>
            <code style="font-size:11px;color:#888"><?= esc($div['kode']) ?></code>
          </div>
          <a href="<?= base_url("master/kpi-divisi/edit/{$div['id']}") ?>"
             class="btn btn-sm btn-primary" style="font-size:12px">
            <i class="ti ti-edit me-1"></i>
            <?= $has_kpi ? 'Kelola KPI' : 'Setup KPI' ?>
          </a>
        </div>

        <hr class="my-2">

        <div class="row g-2 text-center">
          <div class="col-4">
            <div class="fw-bold" style="font-size:20px;color:#1F4E79">
              <?= $s['jumlah_kpi'] ?>
            </div>
            <div style="font-size:11px;color:#888">KPI Aktif</div>
          </div>
          <div class="col-4">
            <div class="fw-bold" style="font-size:20px;
              color:<?= $bobot_ok ? '#375623' : '#C00000' ?>">
              <?= $bobot_pct ?>%
            </div>
            <div style="font-size:11px;color:#888">Total Bobot</div>
          </div>
          <div class="col-4">
            <?php if (!$has_kpi): ?>
              <span class="badge" style="background:#FCE4D6;color:#C00000;font-size:11px">
                Belum setup
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

<?php if (empty($divisiList)): ?>
  <div class="text-center py-5 text-muted">
    <i class="ti ti-building-off fs-1 d-block mb-2"></i>
    Belum ada divisi.
    <a href="<?= base_url('master/divisi') ?>">Buat divisi dulu</a>
  </div>
<?php endif; ?>