<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h5 class="mb-0 fw-semibold" style="color:#1F4E79">
      <i class="ti ti-bell me-1"></i> Notifikasi Email
    </h5>
    <small class="text-muted">
      Kirim reminder input KPI ke Manajer / Kepala Unit
    </small>
  </div>
  <?php if ($periodeAktif): ?>
  <form action="<?= base_url('notifikasi/send-all') ?>"
        method="post">
    <?= csrf_field() ?>
    <button type="submit"
            class="btn btn-primary btn-sm"
            onclick="return confirmAction(event, { title: 'Kirim Reminder', text: 'Kirim reminder ke semua user?', icon: 'question', confirmText: 'Ya, Kirim' })">
      <i class="ti ti-send me-1"></i>
      Kirim Reminder ke Semua
    </button>
  </form>
  <?php endif; ?>
</div>

<?php if (!$periodeAktif): ?>
<div class="alert alert-warning" style="font-size:13px">
  <i class="ti ti-alert-triangle me-1"></i>
  Tidak ada periode aktif. Aktifkan periode terlebih dahulu.
</div>
<?php else: ?>
<div class="alert py-2 mb-3"
     style="background:#E2EFDA;border:1px solid #70AD47;font-size:13px">
  <i class="ti ti-calendar-check me-1" style="color:#375623"></i>
  <strong>Periode Aktif:</strong> <?= esc($periodeAktif['nama']) ?>
  &nbsp;·&nbsp; Deadline:
  <strong><?= date('d F Y', strtotime($periodeAktif['tgl_selesai'])) ?></strong>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <table class="table table-sm table-hover align-middle mb-0"
           style="font-size:13px">
      <thead style="background:#f8fafc">
        <tr>
          <th>Nama</th>
          <th>Email</th>
          <th>Role</th>
          <th>Divisi</th>
          <th class="text-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($users)): ?>
        <tr>
          <td colspan="5" class="text-center py-4 text-muted">
            Tidak ada user dengan role Manajer / Kepala Unit / HR
          </td>
        </tr>
        <?php endif; ?>
        <?php foreach ($users as $u): ?>
        <?php
        $roleLabels = [
            'drafter'  => ['Drafter',      '#FEF3C7','#92400E'],
            'approver' => ['Approver',     '#E0F2FE','#0369A1'],
            'hr'       => ['HR Manager',   '#D1FAE5','#065F46'],
        ];
        $rl = $roleLabels[$u['role']] ?? [$u['role'],'#f0f0f0','#888'];
        ?>
        <tr>
          <td class="fw-semibold"><?= esc($u['nama']) ?></td>
          <td>
            <code style="font-size:11px;color:#555">
              <?= esc($u['email']) ?>
            </code>
          </td>
          <td>
            <span class="badge"
                  style="background:<?= $rl[1] ?>;
                         color:<?= $rl[2] ?>;font-size:11px">
              <?= $rl[0] ?>
            </span>
          </td>
          <td class="text-muted"><?= esc($u['divisi'] ?? '—') ?></td>
          <td class="text-center">
            <?php if ($periodeAktif && $u['email']): ?>
            <form action="<?= base_url("notifikasi/send/{$u['id']}") ?>"
                  method="post" class="d-inline">
              <?= csrf_field() ?>
              <button type="submit"
                      class="btn btn-outline-primary btn-sm"
                      style="font-size:11px;padding:2px 10px">
                <i class="ti ti-send me-1"></i> Kirim Reminder
              </button>
            </form>
            <?php else: ?>
              <span class="text-muted" style="font-size:11px">
                <?= !$u['email'] ? 'Tidak ada email' : 'Tidak ada periode' ?>
              </span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <!-- Statistik Email -->
    <div class="row g-3 mb-3 mt-4">
      <div class="col-md-4">
        <div class="stat-card text-center">
          <div class="stat-value" style="color:#1F4E79">
            <?= $statistik['total'] ?>
          </div>
          <div class="stat-label">Total Email Terkirim</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-card text-center">
          <div class="stat-value" style="color:#375623">
            <?= $statistik['terkirim'] ?>
          </div>
          <div class="stat-label">Berhasil</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-card text-center">
          <div class="stat-value" style="color:#C00000">
            <?= $statistik['gagal'] ?>
          </div>
          <div class="stat-label">Gagal</div>
        </div>
      </div>
    </div>

    <!-- Histori Pengiriman -->
    <div class="card border-0 shadow-sm">
      <div class="card-header py-2 d-flex align-items-center justify-content-between"
          style="background:#F3E5F5">
        <span class="fw-semibold" style="color:#5C2A6B;font-size:13px">
          <i class="ti ti-history me-1"></i> Histori Pengiriman Email
        </span>
        <a href="<?= base_url('notifikasi/histori') ?>"
          class="btn btn-sm btn-outline-secondary"
          style="font-size:11px">
          Lihat Semua
        </a>
      </div>
      <div class="card-body p-0" style="max-height:400px;overflow-y:auto">
        <table class="table table-sm table-hover align-middle mb-0"
              style="font-size:12px">
          <thead style="background:#f8fafc;position:sticky;top:0">
            <tr>
              <th style="width:130px">Waktu Kirim</th>
              <th>Dikirim Ke</th>
              <th>Subject</th>
              <th class="text-center" style="width:90px">Status</th>
              <th>Keterangan</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($histori)): ?>
            <tr>
              <td colspan="5" class="text-center py-4 text-muted">
                Belum ada histori pengiriman email
              </td>
            </tr>
            <?php endif; ?>
            <?php foreach ($histori as $h): ?>
            <tr>
              <td style="color:#888">
                <?= date('d M Y', strtotime($h['created_at'])) ?>
                <br><span style="font-size:11px">
                  <?= date('H:i:s', strtotime($h['created_at'])) ?>
                </span>
              </td>
              <td>
                <div class="fw-semibold"><?= esc($h['to_nama']) ?></div>
                <small class="text-muted"><?= esc($h['to_email']) ?></small>
              </td>
              <td style="font-size:11px;color:#555">
                <?= esc($h['subject']) ?>
              </td>
              <td class="text-center">
                <?php if ($h['status'] === 'terkirim'): ?>
                  <span class="badge"
                        style="background:#C6EFCE;color:#375623;font-size:11px">
                    <i class="ti ti-check"></i> Terkirim
                  </span>
                <?php else: ?>
                  <span class="badge"
                        style="background:#FCE4D6;color:#C00000;font-size:11px">
                    <i class="ti ti-x"></i> Gagal
                  </span>
                <?php endif; ?>
              </td>
              <td style="font-size:11px;color:#888">
                <?= $h['status'] === 'gagal'
                    ? esc($h['error_message'])
                    : '—' ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>