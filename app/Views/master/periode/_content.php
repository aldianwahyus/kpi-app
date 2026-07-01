<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h5 class="mb-0 fw-semibold" style="color:#1F4E79">
      <i class="ti ti-calendar me-1"></i> Periode Penilaian
    </h5>
    <small class="text-muted">Kelola periode penilaian KPI</small>
  </div>
  <a href="<?= base_url('master/periode/create') ?>"
     class="btn btn-primary btn-sm d-flex align-items-center gap-1">
    <i class="ti ti-plus"></i> Buat Periode
  </a>
</div>

<!-- Banner periode aktif -->
<?php if ($periode_aktif): ?>
<div class="alert d-flex align-items-center gap-3 py-3 mb-3"
     style="background:#E2EFDA;border:1px solid #70AD47;border-radius:10px">
  <i class="ti ti-calendar-check fs-3" style="color:#375623"></i>
  <div class="flex-grow-1">
    <div class="fw-semibold" style="color:#375623;font-size:14px">
      Periode Aktif: <?= esc($periode_aktif['nama']) ?>
    </div>
    <div style="font-size:12px;color:#375623">
      <?= date('d M Y', strtotime($periode_aktif['tgl_mulai'])) ?>
      — <?= date('d M Y', strtotime($periode_aktif['tgl_selesai'])) ?>
      &nbsp;·&nbsp; Kode: <code><?= esc($periode_aktif['kode']) ?></code>
    </div>
  </div>
  <a href="<?= base_url("master/periode/status/{$periode_aktif['id']}/tutup") ?>"
     class="btn btn-sm btn-outline-danger"
     onclick="return confirmAction(event, { title: 'Tutup Periode', text: 'Tutup periode ini?', confirmText: 'Ya, Tutup', danger: true })"
     style="font-size:12px">
    <i class="ti ti-lock me-1"></i> Tutup Periode
  </a>
</div>
<?php else: ?>
<div class="alert d-flex align-items-center gap-2 py-2 mb-3"
     style="background:#FFF3CD;border:1px solid #BF9000">
  <i class="ti ti-alert-triangle" style="color:#BF9000"></i>
  <span style="font-size:13px;color:#7F6000">
    Tidak ada periode aktif. Input penilaian tidak bisa dilakukan.
  </span>
</div>
<?php endif; ?>

<!-- Tabel semua periode -->
<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-sm table-hover align-middle mb-0" style="font-size:13px">
        <thead style="background:#f8fafc">
          <tr>
            <th style="width:40px">#</th>
            <th>Nama Periode</th>
            <th style="width:100px">Kode</th>
            <th style="width:120px">Tanggal Mulai</th>
            <th style="width:120px">Tanggal Selesai</th>
            <th style="width:80px" class="text-center">Durasi</th>
            <th style="width:100px" class="text-center">Status</th>
            <th style="width:150px" class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($periodes)): ?>
          <tr>
            <td colspan="8" class="text-center py-4 text-muted">
              <i class="ti ti-calendar-off d-block fs-2 mb-1"></i>
              Belum ada periode. Buat periode pertama!
            </td>
          </tr>
          <?php endif; ?>
          <?php foreach ($periodes as $i => $p): ?>
          <?php
          $tgl_mulai   = new DateTime($p['tgl_mulai']);
          $tgl_selesai = new DateTime($p['tgl_selesai']);
          $durasi      = $tgl_mulai->diff($tgl_selesai)->days;
          $status_conf = [
            'draft' => ['bg'=>'#F2F2F2','color'=>'#888','label'=>'Draft'],
            'aktif' => ['bg'=>'#C6EFCE','color'=>'#375623','label'=>'Aktif'],
            'tutup' => ['bg'=>'#FCE4D6','color'=>'#C00000','label'=>'Tutup'],
          ];
          $sc = $status_conf[$p['status']] ?? $status_conf['draft'];
          ?>
          <tr>
            <td class="text-muted"><?= $i + 1 ?></td>
            <td class="fw-semibold"><?= esc($p['nama']) ?></td>
            <td>
              <code style="font-size:11px;background:#f0f4ff;
                           padding:2px 6px;border-radius:4px;color:#2E75B6">
                <?= esc($p['kode']) ?>
              </code>
            </td>
            <td><?= date('d M Y', strtotime($p['tgl_mulai'])) ?></td>
            <td><?= date('d M Y', strtotime($p['tgl_selesai'])) ?></td>
            <td class="text-center text-muted"><?= $durasi ?> hari</td>
            <td class="text-center">
              <span class="badge" style="background:<?= $sc['bg'] ?>;
                    color:<?= $sc['color'] ?>;font-size:11px">
                <?= $sc['label'] ?>
              </span>
            </td>
            <td class="text-center">
              <div class="d-flex gap-1 justify-content-center flex-wrap">
                <!-- Tombol status cepat -->
                <?php if ($p['status'] === 'draft'): ?>
                  <a href="<?= base_url("master/periode/status/{$p['id']}/aktif") ?>"
                     class="btn btn-outline-success"
                     style="padding:2px 8px;font-size:11px"
                     onclick="return confirmAction(event, { title: 'Aktifkan Periode', text: 'Aktifkan periode ini?', icon: 'question', confirmText: 'Ya, Aktifkan' })">
                    <i class="ti ti-player-play"></i> Aktifkan
                  </a>
                <?php elseif ($p['status'] === 'aktif'): ?>
                  <a href="<?= base_url("master/periode/status/{$p['id']}/tutup") ?>"
                     class="btn btn-outline-warning"
                     style="padding:2px 8px;font-size:11px"
                     onclick="return confirmAction(event, { title: 'Tutup Periode', text: 'Tutup periode ini?', confirmText: 'Ya, Tutup', danger: true })">
                    <i class="ti ti-lock"></i> Tutup
                  </a>
                <?php elseif ($p['status'] === 'tutup'): ?>
                  <a href="<?= base_url("master/periode/status/{$p['id']}/draft") ?>"
                     class="btn btn-outline-secondary"
                     style="padding:2px 8px;font-size:11px"
                     onclick="return confirmAction(event, { title: 'Buka Kembali Periode', text: 'Kembalikan periode ini ke status Draft?', icon: 'question', confirmText: 'Ya, Buka Kembali' })">
                    <i class="ti ti-refresh"></i> Buka Lagi
                  </a>
                <?php endif; ?>

                <a href="<?= base_url("master/periode/edit/{$p['id']}") ?>"
                   class="btn btn-outline-primary"
                   style="padding:2px 8px;font-size:11px">
                  <i class="ti ti-edit"></i>
                </a>

                <?php if ($p['status'] !== 'aktif'): ?>
                <a href="<?= base_url("master/periode/delete/{$p['id']}") ?>"
                   class="btn btn-outline-danger"
                   style="padding:2px 8px;font-size:11px"
                   onclick="return confirmAction(event, { title: 'Hapus Periode', text: 'Hapus periode <?= esc($p['nama'], 'js') ?>?', confirmText: 'Ya, Hapus', danger: true })">
                  <i class="ti ti-trash"></i>
                </a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>