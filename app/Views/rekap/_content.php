<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h5 class="mb-0 fw-semibold" style="color:#1F4E79">
      <i class="ti ti-table me-1"></i> Rekap & Ranking KPI Pegawai
    </h5>
    <small class="text-muted">Lihat dan bandingkan nilai KPI semua pegawai</small>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= base_url('laporan/excel?periode_id=' . $periodeId) ?>"
       class="btn btn-sm btn-outline-success">
      <i class="ti ti-file-spreadsheet me-1"></i> Export Excel
    </a>
    <a href="<?= base_url('laporan/pdf?periode_id=' . $periodeId) ?>"
       class="btn btn-sm btn-outline-danger">
      <i class="ti ti-file-text me-1"></i> Export PDF
    </a>
  </div>
</div>

<?= view('components/grade_info') ?>
<br>
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body py-2">
    <form method="get" action="<?= base_url('rekap') ?>"
          class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label small fw-semibold mb-1">Periode</label>
        <select name="periode_id" class="form-select form-select-sm">
          <?php foreach ($periodes as $p): ?>
            <option value="<?= $p['id'] ?>"
              <?= $periodeId == $p['id'] ? 'selected' : '' ?>>
              <?= esc($p['nama']) ?>
              (<?= ucfirst($p['status']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label small fw-semibold mb-1">Direktorat</label>
        <select name="direktorat_id" class="form-select form-select-sm">
          <option value="">Semua Direktorat</option>
          <?php foreach ($direktoratList as $d): ?>
            <option value="<?= $d['id'] ?>"
              <?= $direktoratId == $d['id'] ? 'selected' : '' ?>>
              <?= esc($d['nama']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small fw-semibold mb-1">Divisi</label>
        <select name="divisi_id" class="form-select form-select-sm">
          <option value="">Semua Divisi</option>
          <?php foreach ($divisiList as $d): ?>
            <option value="<?= $d['id'] ?>"
              <?= $divisiId == $d['id'] ? 'selected' : '' ?>>
              <?= esc($d['nama']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small fw-semibold mb-1">Cari Nama</label>
        <input type="text" name="search"
               class="form-control form-control-sm"
               value="<?= esc($search) ?>"
               placeholder="Nama pegawai...">
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-primary btn-sm w-100">
          <i class="ti ti-filter me-1"></i> Filter
        </button>
      </div>
    </form>
  </div>
</div>

<?php if (!empty($rekap)): ?>
<div class="row g-3 mb-3">
  <div class="col-6 col-md-3">
    <div class="stat-card text-center">
      <div class="stat-value" style="color:#1F4E79">
        <?= $stats['count'] ?>
      </div>
      <div class="stat-label">Pegawai Dinilai</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card text-center">
      <div class="stat-value" style="color:#375623">
        <?= number_format($stats['avg'], 2) ?>
      </div>
      <div class="stat-label">Rata-rata Nilai</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card text-center">
      <div class="stat-value" style="color:#2E75B6">
        <?= number_format($stats['max'], 2) ?>
      </div>
      <div class="stat-label">Nilai Tertinggi</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card text-center">
      <div class="stat-value" style="color:#BF9000">
        <?= number_format($stats['min'], 2) ?>
      </div>
      <div class="stat-label">Nilai Terendah</div>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-sm table-hover align-middle mb-0"
             style="font-size:13px">
        <thead style="background:#f8fafc">
          <tr>
            <th style="width:50px" class="text-center">Rank</th>
            <th>Nama Pegawai</th>
            <th>Divisi</th>
            <th>Direktorat</th>
            <th class="text-center" style="width:80px">KPI Diisi</th>
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
              Belum ada data penilaian untuk filter ini
            </td>
          </tr>
          <?php endif; ?>

          <?php foreach ($rekap as $i => $r): ?>
          <?php
          $nilai = (float)$r['nilai_akhir'];
          $grade = $r['grade'] ?? '—';
          
          // Kode warna mengikuti standarisasi grade Yudisium (IS, SB, B, C)
          $gc = match($grade) {
              'IS' => ['#1E7A55', '#FFFFFF'], // Istimewa: Hijau Tua (Teks Putih)
              'SB' => ['#A9D18E', '#1E4620'], // Sangat Baik: Hijau Muda (Teks Hijau Tua)
              'B'  => ['#FFC000', '#7F6000'], // Baik: Oranye (Teks Cokelat Tua)
              'C'  => ['#FCE4D6', '#C00000'], // Cukup: Merah Soft (Teks Merah Tua)
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
                <span class="text-muted fw-semibold"><?= $i+1 ?></span>
              <?php endif; ?>
            </td>
            <td>
              <div class="fw-semibold"><?= esc($r['nama']) ?></div>
              <small class="text-muted">
                <?= esc($r['jabatan'] ?? '') ?>
              </small>
            </td>
            <td style="font-size:12px">
              <?= esc($r['divisi'] ?? '—') ?>
            </td>
            <td style="font-size:12px;color:#888">
              <?= esc($r['direktorat'] ?? '—') ?>
            </td>
            <td class="text-center">
              <span class="badge bg-light text-dark border"
                    style="font-size:11px">
                <?= $r['jumlah_kpi'] ?> KPI
              </span>
            </td>
            <td class="text-center">
              <div style="font-size:15px;font-weight:700;color:#1F4E79">
                <?= number_format($nilai, 2) ?>
              </div>
              <div class="progress mt-1" style="height:4px">
                <div class="progress-bar"
                     style="width:<?= min(100, $nilai / 4 * 100) ?>%;
                            background:<?= $gc[0] // Warna Utama Grade ?>">
                </div>
              </div>
            </td>
            <td class="text-center">
              <span class="badge fw-bold"
                    style="background:<?= $gc[0] ?>;
                           color:<?= $gc[1] ?>;
                           font-size:13px;padding:4px 10px">
                <?= $grade ?>
              </span>
            </td>
            <td class="text-center">
              <a href="<?= base_url("rekap/detail/{$r['pegawai_id']}?periode_id=$periodeId") ?>"
                 class="btn btn-outline-primary btn-sm"
                 style="padding:2px 8px;font-size:11px">
                <i class="ti ti-eye"></i> Detail
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      
      <?php if ($totalPages > 1): ?>
      <div class="d-flex justify-content-between align-items-center mt-3">
        <small class="text-muted">
          Menampilkan <?= count($rekap) ?> dari <?= $total ?> data
        </small>
        <nav>
          <ul class="pagination pagination-sm mb-0">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
              <a class="page-link"
                href="?<?= http_build_query(array_merge(
                    $_GET, ['page' => $i]
                )) ?>"
                style="font-size:12px">
                <?= $i ?>
              </a>
            </li>
            <?php endfor; ?>
          </ul>
        </nav>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>