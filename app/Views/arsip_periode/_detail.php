<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h5 class="mb-0 fw-semibold" style="color:#1F4E79">
      <i class="ti ti-archive me-1"></i> Arsip Periode — <?= esc($periode['nama']) ?>
    </h5>
    <small class="text-muted">
      <?= date('d M Y', strtotime($periode['tgl_mulai'])) ?> — <?= date('d M Y', strtotime($periode['tgl_selesai'])) ?>
      &nbsp;·&nbsp; Kode: <code><?= esc($periode['kode']) ?></code>
      &nbsp;·&nbsp; <span class="badge" style="background:#FCE4D6;color:#C00000">Ditutup</span>
    </small>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= base_url('arsip-periode') ?>" class="btn btn-sm btn-light border">
      <i class="ti ti-arrow-left me-1"></i> Kembali
    </a>
    <a href="<?= base_url("arsip-periode/export-excel/{$periode['id']}") ?>" class="btn btn-sm btn-outline-success">
      <i class="ti ti-file-spreadsheet me-1"></i> Export Excel
    </a>
    <a href="<?= base_url("arsip-periode/export-pdf/{$periode['id']}") ?>" class="btn btn-sm btn-outline-danger">
      <i class="ti ti-file-text me-1"></i> Export PDF
    </a>
  </div>
</div>

<div class="alert py-2 mb-3 d-flex align-items-center gap-2" style="background:#E6F1FB;border:1px solid #2E75B6;font-size:12px">
  <i class="ti ti-info-circle" style="color:#1F4E79"></i>
  <span style="color:#1F4E79">
    Data di halaman ini adalah snapshot beku pada saat Periode ditutup — tidak berubah walau konfigurasi KPI pegawai diubah setelahnya.
  </span>
</div>

<?= view('components/grade_info') ?>
<br>

<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-sm table-hover align-middle mb-0" style="font-size:13px">
        <thead style="background:#f8fafc">
          <tr>
            <th style="width:50px" class="text-center">Rank</th>
            <th>Nama Pegawai</th>
            <th>Divisi</th>
            <th>Direktorat</th>
            <th class="text-center" style="width:80px">Jumlah KPI</th>
            <th class="text-center" style="width:110px">Nilai KPI</th>
            <th class="text-center" style="width:80px">Grade</th>
            <th class="text-center" style="width:80px">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rekap)): ?>
          <tr>
            <td colspan="8" class="text-center py-5 text-muted">
              <i class="ti ti-database-off fs-1 d-block mb-2"></i>
              Tidak ada data arsip untuk periode ini.
            </td>
          </tr>
          <?php endif; ?>

          <?php foreach ($rekap as $i => $r): ?>
          <?php
          $nilai = (float)$r['nilai_akhir'];
          $grade = $r['grade'] ?? '—';
          $gc = match($grade) {
              'IS' => ['#1E7A55', '#FFFFFF'],
              'SB' => ['#A9D18E', '#1E4620'],
              'B'  => ['#FFC000', '#7F6000'],
              'C'  => ['#FCE4D6', '#C00000'],
              default => ['#f0f0f0', '#888888'],
          };
          ?>
          <tr>
            <td class="text-center">
              <?php if ($i === 0): ?>
                <i class="ti ti-medal" style="color:#FFD700;font-size:18px"></i>
              <?php elseif ($i === 1): ?>
                <i class="ti ti-medal" style="color:#C0C0C0;font-size:18px"></i>
              <?php elseif ($i === 2): ?>
                <i class="ti ti-medal" style="color:#CD7F32;font-size:18px"></i>
              <?php else: ?>
                <span class="text-muted fw-semibold"><?= $i + 1 ?></span>
              <?php endif; ?>
            </td>
            <td>
              <div class="fw-semibold"><?= esc($r['nama']) ?></div>
              <small class="text-muted"><?= esc($r['jabatan'] ?? '') ?></small>
            </td>
            <td style="font-size:12px"><?= esc($r['divisi'] ?? '—') ?></td>
            <td style="font-size:12px;color:#888"><?= esc($r['direktorat'] ?? '—') ?></td>
            <td class="text-center">
              <span class="badge bg-light text-dark border" style="font-size:11px">
                <?= $r['jumlah_kpi'] ?> KPI
              </span>
            </td>
            <td class="text-center">
              <div style="font-size:15px;font-weight:700;color:#1F4E79">
                <?= number_format($nilai, 2) ?>
              </div>
            </td>
            <td class="text-center">
              <span class="badge fw-bold" style="background:<?= $gc[0] ?>;color:<?= $gc[1] ?>;font-size:12px">
                <?= $grade ?>
              </span>
            </td>
            <td class="text-center">
              <a href="<?= base_url("arsip-periode/detail/{$periode['id']}/pegawai/{$r['pegawai_id']}") ?>"
                 class="btn btn-outline-primary" style="padding:2px 8px;font-size:11px">
                <i class="ti ti-eye"></i>
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
