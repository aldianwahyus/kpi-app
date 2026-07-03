<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h5 class="mb-0 fw-semibold" style="color:#1F4E79">
      <i class="ti ti-user-check me-1"></i> KPI Per Pegawai
    </h5>
    <small class="text-muted">Setup KPI individual untuk setiap pegawai</small>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= base_url('kpi-pegawai/copy-massal') ?>"
       class="btn btn-outline-secondary btn-sm">
      <i class="ti ti-copy me-1"></i> Salin Massal
    </a>
    <a href="<?= base_url('kpi-pegawai/import') ?>"
       class="btn btn-outline-primary btn-sm">
      <i class="ti ti-file-import me-1"></i> Import Excel
    </a>
  </div>
</div>

<!-- Info alur -->
<div class="alert py-2 mb-3 d-flex align-items-center gap-2"
     style="background:#E6F1FB;border:1px solid #2E75B6;font-size:13px">
  <i class="ti ti-info-circle" style="color:#1F4E79"></i>
  <span style="color:#1F4E79">
    KPI Per Pegawai adalah turunan dari <strong>KPI Per Unit Kerja</strong>.
    Pastikan KPI Per Unit Kerja sudah mencapai <strong>100%</strong>
    sebelum setup KPI Per Pegawai.
  </span>
</div>

<?php
$dir_colors = [
    0 => ['bg'=>'#E6F1FB','border'=>'#2E75B6','text'=>'#1F4E79'],
    1 => ['bg'=>'#EAF3DE','border'=>'#70AD47','text'=>'#375623'],
    2 => ['bg'=>'#FFF3CD','border'=>'#BF9000','text'=>'#7F6000'],
    3 => ['bg'=>'#F3E5F5','border'=>'#9B59B6','text'=>'#5C2A6B'],
    4 => ['bg'=>'#FCE4D6','border'=>'#E05D1C','text'=>'#7B2D0A'],
];
$ci = 0;
?>

<?php foreach ($grouped as $divisi => $pegawais): ?>
<?php $c = $dir_colors[$ci++ % 5]; ?>
<div class="card mb-3 border-0 shadow-sm">
  <div class="card-header py-2 d-flex align-items-center
              justify-content-between"
       style="background:<?= $c['bg'] ?>;
              border-left:4px solid <?= $c['border'] ?>">
    <span class="fw-semibold"
          style="color:<?= $c['text'] ?>;font-size:13px">
      <i class="ti ti-building me-1"></i><?= esc($divisi) ?>
    </span>
    <span class="badge"
          style="background:<?= $c['border'] ?>;font-size:11px">
      <?= count($pegawais) ?> pegawai
    </span>
  </div>
  <div class="card-body p-0">
    <table class="table table-sm table-hover align-middle mb-0"
           style="font-size:13px">
      <thead style="background:#f8fafc">
        <tr>
          <th>Nama Pegawai</th>
          <th>Jabatan</th>
          <th class="text-center">KPI Unit Kerja</th>
          <th class="text-center">Jml KPI Pegawai</th>
          <th class="text-center">Total Bobot</th>
          <th class="text-center">Status</th>
          <th class="text-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pegawais as $p): ?>
        <?php $s = $status[$p['id']] ?? []; ?>
        <tr>
          <td class="fw-semibold"><?= esc($p['nama']) ?></td>
          <td class="text-muted"><?= esc($p['jabatan'] ?? '—') ?></td>
          <td class="text-center">
            <?php if ($s['divisi_ok'] ?? false): ?>
              <span class="badge"
                    style="background:#C6EFCE;color:#375623;font-size:11px">
                ✓ 100%
              </span>
            <?php else: ?>
              <span class="badge"
                    style="background:#FCE4D6;color:#C00000;font-size:11px">
                Belum 100%
              </span>
            <?php endif; ?>
          </td>
          <td class="text-center">
            <span class="badge bg-light text-dark border"
                  style="font-size:11px">
              <?= $s['jumlah_kpi'] ?? 0 ?> KPI
            </span>
          </td>
          <td class="text-center fw-semibold"
              style="color:<?= ($s['bobot_ok'] ?? false)
                             ? '#375623' : '#BF9000' ?>">
            <?= round(($s['total_bobot'] ?? 0) * 100, 2) ?>%
          </td>
          <td class="text-center">
            <?php if (!($s['divisi_ok'] ?? false)): ?>
              <span class="badge"
                    style="background:#FCE4D6;color:#C00000;font-size:10px">
                KPI Unit belum siap
              </span>
            <?php elseif ($s['jumlah_kpi'] == 0): ?>
              <span class="badge"
                    style="background:#FFF3CD;color:#7F6000;font-size:10px">
                Belum setup
              </span>
            <?php elseif (!($s['bobot_ok'] ?? false)): ?>
              <span class="badge"
                    style="background:#FFF3CD;color:#7F6000;font-size:10px">
                Bobot belum 100%
              </span>
            <?php else: ?>
              <span class="badge"
                    style="background:#C6EFCE;color:#375623;font-size:10px">
                ✓ Siap
              </span>
            <?php endif; ?>
          </td>
          <td class="text-center">
            <?php if ($s['divisi_ok'] ?? false): ?>
            <a href="<?= base_url("kpi-pegawai/edit/{$p['id']}") ?>"
               class="btn btn-primary btn-sm"
               style="font-size:12px;padding:3px 10px">
              <i class="ti ti-settings me-1"></i>
              <?= ($s['jumlah_kpi'] ?? 0) > 0 ? 'Kelola' : 'Setup' ?>
            </a>
            <?php else: ?>
            <span class="text-muted" style="font-size:12px">
              Setup KPI Unit dulu
            </span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endforeach; ?>