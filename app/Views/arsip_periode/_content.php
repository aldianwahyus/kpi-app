<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h5 class="mb-0 fw-semibold" style="color:#1F4E79">
      <i class="ti ti-archive me-1"></i> Arsip Periode
    </h5>
    <small class="text-muted">
      Rekapan beku Penilaian untuk Periode yang sudah ditutup — tidak berubah walau konfigurasi KPI diubah kemudian.
    </small>
  </div>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-sm table-hover align-middle mb-0" style="font-size:13px">
        <thead style="background:#f8fafc">
          <tr>
            <th style="width:40px">#</th>
            <th>Nama Periode</th>
            <th style="width:100px">Kode</th>
            <th style="width:150px">Periode</th>
            <th style="width:110px" class="text-center">Jumlah Pegawai</th>
            <th style="width:110px" class="text-center">Jumlah Baris KPI</th>
            <th style="width:180px" class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($ringkasan)): ?>
          <tr>
            <td colspan="7" class="text-center py-4 text-muted">
              <i class="ti ti-archive-off d-block fs-2 mb-1"></i>
              Belum ada Periode yang ditutup.
            </td>
          </tr>
          <?php endif; ?>
          <?php foreach ($ringkasan as $i => $r): ?>
          <?php $p = $r['periode']; ?>
          <tr>
            <td class="text-muted"><?= $i + 1 ?></td>
            <td class="fw-semibold"><?= esc($p['nama']) ?></td>
            <td>
              <code style="font-size:11px;background:#f0f4ff;padding:2px 6px;border-radius:4px;color:#2E75B6">
                <?= esc($p['kode']) ?>
              </code>
            </td>
            <td style="font-size:12px">
              <?= date('d M Y', strtotime($p['tgl_mulai'])) ?> — <?= date('d M Y', strtotime($p['tgl_selesai'])) ?>
            </td>
            <td class="text-center fw-semibold" style="color:#1F4E79"><?= $r['jumlah_pegawai'] ?></td>
            <td class="text-center text-muted"><?= $r['jumlah_baris'] ?></td>
            <td class="text-center">
              <?php if ($r['jumlah_baris'] > 0): ?>
              <div class="d-flex gap-1 justify-content-center flex-wrap">
                <a href="<?= base_url("arsip-periode/detail/{$p['id']}") ?>"
                   class="btn btn-outline-primary" style="padding:2px 8px;font-size:11px">
                  <i class="ti ti-eye"></i> Lihat
                </a>
                <a href="<?= base_url("arsip-periode/export-excel/{$p['id']}") ?>"
                   class="btn btn-outline-success" style="padding:2px 8px;font-size:11px">
                  <i class="ti ti-file-spreadsheet"></i>
                </a>
                <a href="<?= base_url("arsip-periode/export-pdf/{$p['id']}") ?>"
                   class="btn btn-outline-danger" style="padding:2px 8px;font-size:11px">
                  <i class="ti ti-file-text"></i>
                </a>
              </div>
              <?php else: ?>
                <span class="text-muted" style="font-size:11px">Belum ada data diarsipkan</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
