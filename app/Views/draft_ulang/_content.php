<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h5 class="mb-0 fw-semibold" style="color:#1F4E79">
      <i class="ti ti-refresh-dot me-1"></i> Permintaan Draft Ulang
    </h5>
    <small class="text-muted">
      Konfirmasi permintaan draft ulang dari Approver
    </small>
  </div>
  <?php if (count($pending) > 0): ?>
  <span class="badge bg-warning text-dark" style="font-size:13px">
    <?= count($pending) ?> menunggu konfirmasi
  </span>
  <?php endif; ?>
</div>

<!-- Permintaan Pending -->
<?php if (!empty($pending)): ?>
<div class="card mb-4 border-0 shadow-sm" style="border:2px solid #BF9000">
  <div class="card-header py-2" style="background:#FFF3CD">
    <span class="fw-semibold" style="color:#7F6000;font-size:13px">
      <i class="ti ti-clock me-1"></i> Menunggu Konfirmasi
    </span>
  </div>
  <div class="card-body p-0">
    <table class="table table-sm align-middle mb-0" style="font-size:13px">
      <thead style="background:#f8fafc">
        <tr>
          <th>Tipe</th>
          <th>Target</th>
          <th>Periode</th>
          <th>Diajukan Oleh</th>
          <th>Tanggal</th>
          <th>Alasan</th>
          <th style="width:220px">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pending as $p): ?>
        <tr>
          <td>
            <span class="badge"
                  style="background:<?= $p['tipe']==='periode' ? '#FCE4D6' : '#E6F1FB' ?>;
                         color:<?= $p['tipe']==='periode' ? '#C00000' : '#1F4E79' ?>;
                         font-size:11px">
              <?= $p['tipe'] === 'periode' ? 'Seluruh Periode' : 'Per Pegawai' ?>
            </span>
          </td>
          <td class="fw-semibold">
            <?= $p['tipe'] === 'pegawai'
                ? esc($p['nama_pegawai'])
                : 'Semua pegawai di periode' ?>
          </td>
          <td><?= esc($p['nama_periode']) ?></td>
          <td>
            <div class="fw-semibold"><?= esc($p['requested_by_nama']) ?></div>
            <small class="text-muted">Approver</small>
          </td>
          <td style="color:#888">
            <?= date('d M Y H:i', strtotime($p['requested_at'])) ?>
          </td>
          <td style="font-size:12px;color:#555;max-width:200px">
            <?= esc($p['alasan']) ?>
          </td>
          <td>
            <div class="d-flex gap-1">
              <button type="button"
                      class="btn btn-success btn-sm"
                      style="font-size:11px"
                      data-bs-toggle="modal"
                      data-bs-target="#modalConfirm<?= $p['id'] ?>">
                <i class="ti ti-check"></i> Konfirmasi
              </button>
              <button type="button"
                      class="btn btn-outline-danger btn-sm"
                      style="font-size:11px"
                      data-bs-toggle="modal"
                      data-bs-target="#modalDecline<?= $p['id'] ?>">
                <i class="ti ti-x"></i> Tolak
              </button>
            </div>
          </td>
        </tr>

        <!-- Modal Konfirmasi -->
        <tr style="display:none">
        <td colspan="7">
        <div class="modal fade" id="modalConfirm<?= $p['id'] ?>" tabindex="-1">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h6 class="modal-title fw-semibold text-success">
                  <i class="ti ti-check me-1"></i> Konfirmasi Draft Ulang
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <form action="<?= base_url("draft-ulang/confirm/{$p['id']}") ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-body">
                  <div class="alert alert-warning py-2" style="font-size:12px">
                    Anda akan mengembalikan status penilaian
                    <strong><?= $p['tipe']==='pegawai' ? esc($p['nama_pegawai']) : 'seluruh pegawai periode ini' ?></strong>
                    dari <strong>Approved</strong> menjadi <strong>Draft</strong>.
                  </div>
                  <p style="font-size:12px"><strong>Alasan dari Approver:</strong><br><?= esc($p['alasan']) ?></p>
                  <label class="form-label small fw-semibold">Catatan Admin (opsional)</label>
                  <textarea name="catatan_admin" class="form-control" rows="2"></textarea>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                  <button type="submit" class="btn btn-success btn-sm">Konfirmasi Draft Ulang</button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- Modal Tolak -->
        <div class="modal fade" id="modalDecline<?= $p['id'] ?>" tabindex="-1">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h6 class="modal-title fw-semibold text-danger">Tolak Permintaan</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <form action="<?= base_url("draft-ulang/decline/{$p['id']}") ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-body">
                  <label class="form-label small fw-semibold">Alasan Penolakan</label>
                  <textarea name="catatan_admin" class="form-control" rows="2" required></textarea>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                  <button type="submit" class="btn btn-danger btn-sm">Tolak Permintaan</button>
                </div>
              </form>
            </div>
          </div>
        </div>
        </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- Histori Semua Permintaan -->
<div class="card border-0 shadow-sm">
  <div class="card-header py-2" style="background:#F3E5F5">
    <span class="fw-semibold" style="color:#5C2A6B;font-size:13px">
      <i class="ti ti-history me-1"></i> Histori Permintaan Draft Ulang
    </span>
  </div>
  <div class="card-body p-0">
    <table class="table table-sm align-middle mb-0" style="font-size:12px">
      <thead style="background:#f8fafc">
        <tr>
          <th>Target</th>
          <th>Diajukan</th>
          <th>Status</th>
          <th>Dikonfirmasi/Ditolak Oleh</th>
          <th>Tanggal</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($semua as $s): ?>
        <?php
        $statusConf = [
            'pending'      => ['bg'=>'#FFF3CD','color'=>'#7F6000','label'=>'Pending'],
            'dikonfirmasi' => ['bg'=>'#C6EFCE','color'=>'#375623','label'=>'Dikonfirmasi'],
            'ditolak'      => ['bg'=>'#FCE4D6','color'=>'#C00000','label'=>'Ditolak'],
        ];
        $sc = $statusConf[$s['status']];
        ?>
        <tr>
          <td>
            <?= $s['tipe']==='pegawai' ? esc($s['nama_pegawai']) : 'Periode ' . esc($s['nama_periode']) ?>
          </td>
          <td><?= esc($s['requested_by_nama']) ?></td>
          <td>
            <span class="badge" style="background:<?= $sc['bg'] ?>;color:<?= $sc['color'] ?>">
              <?= $sc['label'] ?>
            </span>
          </td>
          <td><?= esc($s['confirmed_by_nama'] ?? '—') ?></td>
          <td style="color:#888">
            <?= $s['confirmed_at'] ? date('d M Y H:i', strtotime($s['confirmed_at'])) : '—' ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>