<div class="d-flex align-items-center gap-2 mb-3">
  <a href="<?= base_url('notifikasi') ?>"
     class="btn btn-sm btn-light border">
    <i class="ti ti-arrow-left"></i>
  </a>
  <h5 class="mb-0 fw-semibold" style="color:#1F4E79">
    <i class="ti ti-history me-1"></i> Histori Notifikasi Email
  </h5>
</div>

<!-- Filter -->
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body py-2">
    <form method="get" class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label small fw-semibold mb-1">Status</label>
        <select name="status" class="form-select form-select-sm">
          <option value="">Semua Status</option>
          <option value="terkirim" <?= $statusFilter==='terkirim'?'selected':'' ?>>
            Terkirim
          </option>
          <option value="gagal" <?= $statusFilter==='gagal'?'selected':'' ?>>
            Gagal
          </option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label small fw-semibold mb-1">Cari Email/Nama</label>
        <input type="text" name="search"
               class="form-control form-control-sm"
               value="<?= esc($search) ?>"
               placeholder="Cari...">
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-primary btn-sm w-100">
          <i class="ti ti-filter me-1"></i> Filter
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Statistik -->
<div class="row g-3 mb-3">
  <div class="col-md-4">
    <div class="stat-card text-center">
      <div class="stat-value" style="color:#1F4E79"><?= $statistik['total'] ?></div>
      <div class="stat-label">Total</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card text-center">
      <div class="stat-value" style="color:#375623"><?= $statistik['terkirim'] ?></div>
      <div class="stat-label">Terkirim</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card text-center">
      <div class="stat-value" style="color:#C00000"><?= $statistik['gagal'] ?></div>
      <div class="stat-label">Gagal</div>
    </div>
  </div>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <table class="table table-sm table-hover align-middle mb-0" style="font-size:13px">
      <thead style="background:#f8fafc">
        <tr>
          <th style="width:140px">Waktu</th>
          <th>Dikirim Ke</th>
          <th>Subject</th>
          <th class="text-center">Status</th>
          <th>Keterangan / Error</th>
          <th>Dikirim Oleh</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($histori as $h): ?>
        <tr>
          <td style="color:#888">
            <?= date('d M Y H:i:s', strtotime($h['created_at'])) ?>
          </td>
          <td>
            <div class="fw-semibold"><?= esc($h['to_nama']) ?></div>
            <small class="text-muted"><?= esc($h['to_email']) ?></small>
          </td>
          <td style="font-size:12px"><?= esc($h['subject']) ?></td>
          <td class="text-center">
            <?php if ($h['status'] === 'terkirim'): ?>
              <span class="badge" style="background:#C6EFCE;color:#375623">
                Terkirim
              </span>
            <?php else: ?>
              <span class="badge" style="background:#FCE4D6;color:#C00000">
                Gagal
              </span>
            <?php endif; ?>
          </td>
          <td style="font-size:11px;color:#888">
            <?= $h['error_message'] ? esc($h['error_message']) : '—' ?>
          </td>
          <td style="font-size:12px;color:#555">
            <?= esc($h['sent_by_nama'] ?? '—') ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>