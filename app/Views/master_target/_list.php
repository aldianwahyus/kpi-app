<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h5 class="mb-0 fw-semibold" style="color:#1F4E79">
      <i class="ti ti-target-arrow me-1"></i> Master Target
    </h5>
    <small class="text-muted">Isi Target (per bulan, 1 tahun penuh) & Bobot (per tahun) untuk setiap pegawai</small>
  </div>
</div>

<div class="alert py-2 mb-3 d-flex align-items-center gap-2" style="background:#E6F1FB;border:1px solid #2E75B6;font-size:13px">
  <i class="ti ti-info-circle" style="color:#1F4E79"></i>
  <span style="color:#1F4E79">
    Hanya pegawai yang sudah memiliki KPI (Setup KPI Per Pegawai) yang tampil di sini.
    Target Penilaian pada Periode Bulanan/Triwulan/Semester/Tahunan dihitung otomatis dari data di sini.
  </span>
</div>

<?php if (empty($grouped)): ?>
<div class="text-center py-5 text-muted">
  <i class="ti ti-playlist-x fs-1 d-block mb-2"></i>
  Belum ada pegawai dengan KPI yang sudah di-setup.
</div>
<?php else: ?>

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
  <div class="card-header py-2 d-flex align-items-center justify-content-between"
       style="background:<?= $c['bg'] ?>;border-left:4px solid <?= $c['border'] ?>">
    <span class="fw-semibold" style="color:<?= $c['text'] ?>;font-size:13px">
      <i class="ti ti-building me-1"></i><?= esc($divisi) ?>
    </span>
    <span class="badge" style="background:<?= $c['border'] ?>;font-size:11px">
      <?= count($pegawais) ?> pegawai
    </span>
  </div>
  <div class="card-body p-0">
    <table class="table table-sm table-hover align-middle mb-0" style="font-size:13px">
      <thead style="background:#f8fafc">
        <tr>
          <th>Nama Pegawai</th>
          <th>Jabatan</th>
          <th class="text-center">Jml KPI</th>
          <th class="text-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pegawais as $p): ?>
        <tr>
          <td class="fw-semibold"><?= esc($p['nama']) ?></td>
          <td class="text-muted"><?= esc($p['jabatan'] ?? '—') ?></td>
          <td class="text-center">
            <span class="badge bg-light text-dark border" style="font-size:11px">
              <?= $p['jumlah_kpi'] ?> KPI
            </span>
          </td>
          <td class="text-center">
            <a href="<?= base_url("master-target/edit/{$p['id']}") ?>" class="btn btn-outline-primary btn-sm">
              <i class="ti ti-edit me-1"></i> Isi Target
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
