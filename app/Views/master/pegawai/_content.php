<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h5 class="mb-0 fw-semibold" style="color:#1F4E79">
      <i class="ti ti-users me-1"></i> Data Pegawai
    </h5>
    <small class="text-muted">Total <?= $total_pegawai ?> pegawai terdaftar</small>
  </div>
  <div class="d-flex gap-2">
    <!-- Tambahkan tombol import -->
    <a href="<?= base_url('pegawai/import') ?>"
       class="btn btn-outline-success btn-sm">
      <i class="ti ti-file-import me-1"></i> Import Excel
    </a>
    <a href="<?= base_url('pegawai/create') ?>"
       class="btn btn-primary btn-sm">
      <i class="ti ti-user-plus me-1"></i> Tambah Pegawai
    </a>
  </div>
</div>

<!-- Ringkasan per divisi -->
<div class="row g-2 mb-3">
  <?php foreach ($divisi_list as $div): ?>
  <?php
  $count = 0;
  foreach ($grouped as $key => $list) {
      if ($key === $div['nama']) $count = count($list);
  }
  ?>
  <div class="col-6 col-md-3">
    <div class="stat-card py-2 px-3">
      <div class="stat-value" style="font-size:20px"><?= $count ?></div>
      <div class="stat-label"><?= esc($div['nama']) ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php if (empty($grouped)): ?>
  <div class="text-center py-5 text-muted">
    <i class="ti ti-users-off fs-1 d-block mb-2"></i>
    Belum ada data pegawai.
    <a href="<?= base_url('pegawai/create') ?>">Tambah sekarang</a>
  </div>
<?php endif; ?>

<?php foreach ($grouped as $divisi_nama => $pegawais): ?>
<div class="card mb-3 border-0 shadow-sm">
  <div class="card-header py-2 d-flex align-items-center justify-content-between"
       style="background:#E6F1FB;border-left:4px solid #2E75B6">
    <span class="fw-semibold" style="color:#1F4E79;font-size:13px">
      <i class="ti ti-building me-1"></i><?= esc($divisi_nama) ?>
    </span>
    <span class="badge" style="background:#2E75B6;font-size:11px">
      <?= count($pegawais) ?> pegawai
    </span>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-sm table-hover align-middle mb-0" style="font-size:13px">
        <thead style="background:#f8fafc">
          <tr>
            <th style="width:120px">NIP</th>
            <th>Nama</th>
            <th>Jabatan</th>
            <th>Unit</th>
            <th>Atasan</th>
            <th class="text-center">Akun</th>
            <th class="text-center">Status</th>
            <th class="text-center" style="width:100px">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pegawais as $p): ?>
          <tr class="<?= !$p['is_active'] ? 'text-muted' : '' ?>">
            <td>
              <code style="font-size:11px;color:#6B7280">
                <?= esc($p['nip'] ?? '—') ?>
              </code>
            </td>
            <td class="fw-semibold"><?= esc($p['nama']) ?></td>
            <td><?= esc($p['jabatan'] ?? '—') ?></td>
            <td><?= esc($p['unit'] ?? '—') ?></td>
            <td class="text-muted"><?= esc($p['nama_atasan'] ?? '—') ?></td>
            <td class="text-center">
              <?php
              $hasUser = (bool) (new \App\Models\UserModel())
                  ->where('pegawai_id', $p['id'])->countAllResults();
              ?>
              <?php if ($hasUser): ?>
                <span class="badge bg-success" style="font-size:10px">
                  <i class="ti ti-check"></i> Ada
                </span>
              <?php else: ?>
                <span class="badge bg-light text-muted border" style="font-size:10px">
                  Belum
                </span>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <a href="<?= base_url("pegawai/toggle/{$p['id']}") ?>"
                 class="badge text-decoration-none"
                 style="font-size:11px;
                   background:<?= $p['is_active'] ? '#C6EFCE' : '#FCE4D6' ?>;
                   color:<?= $p['is_active'] ? '#375623' : '#C00000' ?>">
                <?= $p['is_active'] ? 'Aktif' : 'Nonaktif' ?>
              </a>
            </td>
            <td class="text-center">
              <div class="d-flex gap-1 justify-content-center">
                <a href="<?= base_url("pegawai/edit/{$p['id']}") ?>"
                   class="btn btn-outline-primary"
                   style="padding:2px 8px;font-size:11px">
                  <i class="ti ti-edit"></i>
                </a>
                <a href="<?= base_url("pegawai/delete/{$p['id']}") ?>"
                   class="btn btn-outline-danger"
                   style="padding:2px 8px;font-size:11px"
                   onclick="return confirmAction(event, { title: 'Hapus Pegawai', text: 'Hapus pegawai <?= esc($p['nama'], 'js') ?>?', confirmText: 'Ya, Hapus', danger: true })">
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