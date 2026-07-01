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

<!-- Fitur Pencarian Direktorat -->
<div class="mb-3">
  <div class="input-group input-group-sm" style="max-width:360px">
    <span class="input-group-text bg-light"><i class="ti ti-search text-muted"></i></span>
    <input type="text" id="cari-direktorat" class="form-control"
           placeholder="Cari direktorat..." autocomplete="off">
    <button type="button" class="btn btn-light border" id="reset-cari-direktorat"
            title="Reset pencarian" style="display:none">
      <i class="ti ti-x" style="font-size:12px"></i>
    </button>
  </div>
  <small class="text-muted" id="info-cari-direktorat" style="font-size:11px"></small>
</div>

<div class="row g-3" id="daftar-direktorat">
<?php foreach ($list as $d): ?>
<?php
$s = $summary[$d['id']] ?? ['total_kpi'=>0];
?>
<div class="col-md-6 direktorat-card"
     data-search="<?= esc(strtolower($d['nama'] . ' ' . $d['kode'] . ' ' . ($d['deskripsi'] ?? '') . ' ' . ($d['singkatan'] ?? ''))) ?>">
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
        <div class="col-6">
          <div class="fw-bold" style="font-size:20px;color:#1F4E79">
            <?= $s['total_kpi'] ?>
          </div>
          <div style="font-size:11px;color:#888">KPI Unit</div>
        </div>
        <div class="col-6">
          <?php if ($s['total_kpi'] == 0): ?>
            <span class="badge" style="background:#FCE4D6;color:#C00000;font-size:11px">
              Belum ada KPI
            </span>
          <?php else: ?>
            <span class="badge" style="background:#C6EFCE;color:#375623;font-size:11px">
              ✓ <?= $s['total_kpi'] ?> KPI tersedia
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
<script>
(function () {
    const input   = document.getElementById('cari-direktorat');
    const resetBtn= document.getElementById('reset-cari-direktorat');
    const info    = document.getElementById('info-cari-direktorat');
    const cards   = document.querySelectorAll('.direktorat-card');

    function filter() {
        const q = input.value.toLowerCase().trim();
        resetBtn.style.display = q ? 'block' : 'none';
        let tampil = 0;
        cards.forEach(c => {
            const match = !q || c.dataset.search.includes(q);
            c.style.display = match ? '' : 'none';
            if (match) tampil++;
        });
        info.textContent = q
            ? `${tampil} dari ${cards.length} direktorat ditemukan`
            : '';
    }

    input.addEventListener('input', filter);
    resetBtn.addEventListener('click', function () {
        input.value = '';
        filter();
        input.focus();
    });
})();
</script>