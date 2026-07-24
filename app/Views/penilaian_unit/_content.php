<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h5 class="mb-0 fw-semibold" style="color:#1F4E79">
      <i class="ti ti-building-community me-1"></i> Penilaian KPI Unit
    </h5>
    <small class="text-muted">
      <?php if ($periodeAktif): ?>
        Periode aktif: <strong><?= esc($periodeAktif['nama']) ?></strong>
      <?php else: ?>
        Belum ada periode aktif — input KPI Unit tidak bisa dilakukan.
      <?php endif; ?>
    </small>
  </div>
</div>

<?php if (empty($grouped)): ?>
  <div class="text-center py-5 text-muted">
    <i class="ti ti-building-off fs-1 d-block mb-2"></i>
    Belum ada divisi.
  </div>
<?php endif; ?>

<?php foreach ($grouped as $direktoratNama => $divisiList): ?>
  <div class="mb-2 fw-semibold" style="color:#1F4E79;font-size:13px">
    <i class="ti ti-building me-1"></i> <?= esc($direktoratNama) ?>
  </div>
  <div class="row g-3 mb-4">
    <?php foreach ($divisiList as $div): ?>
    <?php
    $r        = $rekap[$div['id']] ?? null;
    $capaian  = $r['rata_capaian'] ?? null;
    $jumlah   = $r['jumlah_kpi_diisi'] ?? 0;
    ?>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div>
              <div class="fw-semibold" style="color:#1F4E79;font-size:14px">
                <?= esc($div['nama']) ?>
              </div>
              <code style="font-size:11px;color:#888"><?= esc($div['kode']) ?></code>
            </div>
            <?php if ($periodeAktif): ?>
            <a href="<?= base_url("penilaian-unit/form/{$div['id']}") ?>"
               class="btn btn-sm btn-primary" style="font-size:12px">
              <i class="ti ti-edit me-1"></i>
              <?= $jumlah > 0 ? 'Kelola' : 'Isi KPI' ?>
            </a>
            <?php endif; ?>
          </div>

          <hr class="my-2">

          <div class="row g-2 text-center">
            <div class="col-6">
              <div class="fw-bold" style="font-size:20px;color:#1F4E79">
                <?= $capaian !== null ? number_format($capaian, 2) . '%' : '—' ?>
              </div>
              <div style="font-size:11px;color:#888">Rata-rata Capaian</div>
            </div>
            <div class="col-6">
              <div class="fw-bold" style="font-size:20px;color:#1F4E79">
                <?= $jumlah ?>
              </div>
              <div style="font-size:11px;color:#888">KPI Sudah Diisi</div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
<?php endforeach; ?>
