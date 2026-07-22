<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-icon" style="background:#EFF6FF">
          <i class="ti ti-users" style="color:#2E75B6"></i>
        </div>
        <div>
          <div class="stat-value"><?= $total_pegawai ?></div>
          <div class="stat-label">Total Pegawai</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-icon" style="background:#F0FDF4">
          <i class="ti ti-clipboard-check" style="color:#375623"></i>
        </div>
        <div>
          <div class="stat-value"><?= $sudah_dinilai ?></div>
          <div class="stat-label">Sudah Dinilai</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-icon" style="background:#FFFBEB">
          <i class="ti ti-clock" style="color:#BF9000"></i>
        </div>
        <div>
          <div class="stat-value"><?= $belum_dinilai ?></div>
          <div class="stat-label">Belum Dinilai</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-icon" style="background:#F5F3FF">
          <i class="ti ti-calendar" style="color:#5C2A6B"></i>
        </div>
        <div>
          <div class="stat-value" style="font-size:14px;font-weight:600">
            <?= esc($periode_aktif) ?>
          </div>
          <div class="stat-label">Periode Aktif</div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php if ($role === 'pegawai' && $nilai_sendiri !== null): ?>
<div class="row g-3 mb-4">
  <div class="col-md-6">
    <div class="stat-card" style="border-left:4px solid #2E75B6">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-icon" style="background:#EFF6FF">
          <i class="ti ti-award" style="color:#2E75B6;font-size:28px"></i>
        </div>
        <div>
          <div style="font-size:28px;font-weight:700;color:#1F4E79">
            <?= $nilai_sendiri > 0 ? number_format($nilai_sendiri,2) : '—' ?>
          </div>
          <div class="stat-label">Nilai KPI Saya</div>
        </div>
        <?php if ($grade_sendiri): ?>
        <?php
        // PENERAPAN LANGSUNG: Mengambil warna terpusat dari KpiCalculationService
        $svc = new \App\Services\KpiCalculationService();
        $gc = $svc->getGradeColor($grade_sendiri);
        ?>
        <div class="ms-auto text-center">
          <div style="font-size:36px;font-weight:700;
                      color:<?= $gc['color'] ?>;background:<?= $gc['bg'] ?>;
                      border-radius:10px;padding:6px 16px">
            <?= $grade_sendiri ?>
          </div>
          <div style="font-size:11px;color:#888">Grade</div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="row g-3 mb-3">
  <div class="col-md-7">
    <div class="stat-card">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="mb-0 fw-semibold" style="color:#1F4E79">
          <i class="ti ti-chart-bar me-1"></i>
          Rata-rata Capaian per Perspektif
        </h6>
        <span class="badge bg-light text-secondary" style="font-size:11px">
          <?= esc($periode_aktif) ?>
        </span>
      </div>
      <canvas id="chartPerspektif" height="200"></canvas>
    </div>
  </div>
  <div class="col-md-5">
    <div class="stat-card">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="mb-0 fw-semibold" style="color:#1F4E79">
          <i class="ti ti-chart-donut me-1"></i>
          Distribusi Grade
        </h6>
      </div>
      <canvas id="chartGrade" height="200"></canvas>
      <div class="row g-1 mt-2">
        <?php
        // PENERAPAN LANGSUNG: Memanggil info terpusat dari KpiCalculationService agar deskripsi dan warna sinkron
        $svc = new \App\Services\KpiCalculationService();
        $grade_info = $svc->getGradeInfo();
        ?>
        <?php foreach ($grade_info as $g => $info): ?>
        <div class="col-3 text-center">
          <div style="font-size:18px;font-weight:700;color:<?= $info['bg'] ?>">
            <?= $grade_counts[$g] ?? 0 ?>
          </div>
          <div style="font-size:10px;color:#888"><?= $g ?> — <?= $info['label'] ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-body">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h6 class="mb-0 fw-semibold" style="color:#1F4E79">
        <i class="ti ti-medal me-1"></i> Top Pegawai
      </h6>
      <a href="<?= base_url('rekap') ?>"
         class="btn btn-sm btn-outline-primary"
         style="font-size:12px">
        Lihat semua
      </a>
    </div>

    <?php if (!empty($top_pegawai)): ?>
    <div class="table-responsive">
      <table class="table table-sm table-hover align-middle"
             style="font-size:13px">
        <thead style="background:#f8fafc">
          <tr>
            <th style="width:40px">#</th>
            <th>Nama Pegawai</th>
            <th>Divisi</th>
            <th class="text-center">Nilai KPI</th>
            <th class="text-center">Grade</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($top_pegawai as $i => $p): ?>
          <tr>
            <td>
              <?php if ($i === 0): ?>
                <i class="ti ti-medal" style="color:#FFD700;font-size:18px"></i>
              <?php elseif ($i === 1): ?>
                <i class="ti ti-medal" style="color:#C0C0C0;font-size:18px"></i>
              <?php elseif ($i === 2): ?>
                <i class="ti ti-medal" style="color:#CD7F32;font-size:18px"></i>
              <?php else: ?>
                <span class="text-muted"><?= $i+1 ?></span>
              <?php endif; ?>
            </td>
            <td>
              <div class="fw-semibold"><?= esc($p['nama']) ?></div>
              <small class="text-muted"><?= esc($p['jabatan'] ?? '') ?></small>
            </td>
            <td class="text-muted" style="font-size:12px">
              <?= esc($p['divisi'] ?? '—') ?>
            </td>
            <td class="text-center fw-bold" style="color:#2E75B6;font-size:15px">
              <?= number_format((float)$p['nilai_akhir'], 2) ?>
            </td>
            <td class="text-center">
              <?php
              $grade = $p['grade'] ?? '—';
              // PENERAPAN LANGSUNG: Badge warna otomatis berdasarkan service penilai
              $gc2 = $svc->getGradeColor($grade);
              ?>
              <span class="badge"
                    style="background:<?= $gc2['bg'] ?>;
                           color:<?= $gc2['color'] ?>;
                           font-size:12px;padding:4px 10px">
                <?= $grade ?>
              </span>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
      <div class="text-center py-4 text-muted" style="font-size:13px">
        <i class="ti ti-database-off fs-2 d-block mb-2"></i>
        Belum ada data penilaian untuk periode ini
      </div>
    <?php endif; ?>
  </div>
</div>

<?php if (!empty($daftar_status_pegawai)): ?>
<?php
// Pisahkan menjadi dua kelompok: belum dinilai dan sudah dinilai
$belumDinilaiList = array_values(array_filter($daftar_status_pegawai,
    fn($p) => empty($p['jumlah_kpi_dinilai']) || (int)$p['jumlah_kpi_dinilai'] === 0
));
$sudahDinilaiList = array_values(array_filter($daftar_status_pegawai,
    fn($p) => !empty($p['jumlah_kpi_dinilai']) && (int)$p['jumlah_kpi_dinilai'] > 0
));
?>
<div class="card border-0 shadow-sm mt-3">
  <div class="card-header py-2 d-flex align-items-center justify-content-between"
       style="background:#fff;border-bottom:2px solid #e5e7eb">
    <div>
      <h6 class="mb-0 fw-semibold" style="color:#1F4E79;font-size:14px">
        <i class="ti ti-users me-1"></i> Status Penilaian Pegawai
        <span class="text-muted fw-normal" style="font-size:12px">
          — <?= count($daftar_status_pegawai) ?> pegawai total
        </span>
      </h6>
    </div>
    <!-- Filter / Search real-time -->
    <div class="input-group input-group-sm" style="max-width:240px">
      <span class="input-group-text bg-light border-end-0">
        <i class="ti ti-search" style="font-size:13px"></i>
      </span>
      <input type="text" id="cari-status-pegawai" class="form-control border-start-0"
             placeholder="Cari nama pegawai..." autocomplete="off">
    </div>
  </div>

  <!-- Tab Belum / Sudah -->
  <div class="px-3 pt-2">
    <ul class="nav nav-tabs nav-tabs-sm" id="tabStatusPegawai" role="tablist"
        style="border-bottom:none;gap:4px">
      <li class="nav-item">
        <button class="nav-link active py-1 px-3" data-bs-toggle="tab"
                data-bs-target="#tabBelum" style="font-size:13px">
          <i class="ti ti-clock me-1 text-warning"></i>
          Belum Dinilai
          <span class="badge ms-1"
                style="background:#FCE4D6;color:#C00000;font-size:11px">
            <?= count($belumDinilaiList) ?>
          </span>
        </button>
      </li>
      <li class="nav-item">
        <button class="nav-link py-1 px-3" data-bs-toggle="tab"
                data-bs-target="#tabSudah" style="font-size:13px">
          <i class="ti ti-circle-check me-1 text-success"></i>
          Sudah Dinilai
          <span class="badge ms-1"
                style="background:#C6EFCE;color:#375623;font-size:11px">
            <?= count($sudahDinilaiList) ?>
          </span>
        </button>
      </li>
    </ul>
  </div>

  <div class="tab-content" id="tabStatusPegawaiContent">

    <!-- TAB BELUM DINILAI -->
    <div class="tab-pane fade show active" id="tabBelum">
      <?php if (empty($belumDinilaiList)): ?>
        <div class="text-center py-5 text-muted">
          <i class="ti ti-circle-check fs-1 d-block mb-2" style="color:#70AD47"></i>
          <div class="fw-semibold">Semua pegawai sudah dinilai!</div>
          <small>Tidak ada yang tersisa untuk periode ini.</small>
        </div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0"
               style="font-size:13px" id="tabelBelumDinilai">
          <thead style="background:#FFF3F3">
            <tr>
              <th class="ps-3" style="width:30px">#</th>
              <th>Nama Pegawai</th>
              <th>Divisi</th>
              <th>Jabatan</th>
              <th class="text-center" style="width:100px">Status</th>
              <th class="text-center" style="width:80px">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($belumDinilaiList as $i => $p): ?>
            <tr class="status-pegawai-row"
                data-search="<?= esc(strtolower($p['nama'] . ' ' . ($p['divisi'] ?? '') . ' ' . ($p['jabatan'] ?? ''))) ?>">
              <td class="ps-3 text-muted"><?= $i + 1 ?></td>
              <td>
                <div class="fw-semibold"><?= esc($p['nama']) ?></div>
                <?php if ($p['unit'] ?? ''): ?>
                  <small class="text-muted"><?= esc($p['unit']) ?></small>
                <?php endif; ?>
              </td>
              <td class="text-muted" style="font-size:12px">
                <?= esc($p['divisi'] ?? '—') ?>
              </td>
              <td class="text-muted" style="font-size:12px">
                <?= esc($p['jabatan'] ?? '—') ?>
              </td>
              <td class="text-center">
                <span class="badge" style="background:#FCE4D6;color:#C00000;font-size:11px;padding:3px 8px">
                  Belum Dinilai
                </span>
              </td>
              <td class="text-center">
                <?php if (in_array($role, ['admin', 'drafter'])): ?>
                <a href="<?= base_url('penilaian/form/' . $p['id']) ?>"
                   class="btn btn-outline-primary btn-sm py-0 px-2"
                   style="font-size:11px">
                  Input
                </a>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

    <!-- TAB SUDAH DINILAI -->
    <div class="tab-pane fade" id="tabSudah">
      <?php if (empty($sudahDinilaiList)): ?>
        <div class="text-center py-5 text-muted">
          <i class="ti ti-clipboard-list fs-1 d-block mb-2"></i>
          <div class="fw-semibold">Belum ada penilaian yang selesai.</div>
        </div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0"
               style="font-size:13px" id="tabelSudahDinilai">
          <thead style="background:#F0FDF4">
            <tr>
              <th class="ps-3" style="width:30px">#</th>
              <th>Nama Pegawai</th>
              <th>Divisi</th>
              <th class="text-center">Nilai Akhir</th>
              <th class="text-center">Status</th>
              <th class="text-center" style="width:80px">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($sudahDinilaiList as $i => $p): ?>
            <?php
              $nilaiAkhir   = (float)($p['nilai_akhir'] ?? 0);
              $statusPen    = $p['status_penilaian'] ?? 'draft';
              $statusLabel  = match($statusPen) {
                  'approved'  => ['Disetujui',   '#C6EFCE', '#375623'],
                  'submitted' => ['Menunggu',     '#BDD7EE', '#1F4E79'],
                  'rejected'  => ['Ditolak',      '#FCE4D6', '#C00000'],
                  default     => ['Draft',         '#F5F5F5', '#666'],
              };
            ?>
            <tr class="status-pegawai-row"
                data-search="<?= esc(strtolower($p['nama'] . ' ' . ($p['divisi'] ?? '') . ' ' . ($p['jabatan'] ?? ''))) ?>">
              <td class="ps-3 text-muted"><?= $i + 1 ?></td>
              <td>
                <div class="fw-semibold"><?= esc($p['nama']) ?></div>
                <small class="text-muted"><?= esc($p['jabatan'] ?? '') ?></small>
              </td>
              <td class="text-muted" style="font-size:12px">
                <?= esc($p['divisi'] ?? '—') ?>
              </td>
              <td class="text-center fw-bold" style="color:#2E75B6;font-size:14px">
                <?= number_format($nilaiAkhir, 2) ?>
              </td>
              <td class="text-center">
                <span class="badge"
                      style="background:<?= $statusLabel[1] ?>;color:<?= $statusLabel[2] ?>;font-size:11px;padding:3px 8px">
                  <?= $statusLabel[0] ?>
                </span>
              </td>
              <td class="text-center">
                <a href="<?= base_url('penilaian/form/' . $p['id']) ?>"
                   class="btn btn-outline-secondary btn-sm py-0 px-2"
                   style="font-size:11px">
                  Lihat
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

  </div><!-- /.tab-content -->
</div><!-- /.card -->

<script>
// Pencarian real-time di tabel status pegawai Dashboard
document.getElementById('cari-status-pegawai').addEventListener('input', function () {
    const q = this.value.toLowerCase().trim();
    document.querySelectorAll('.status-pegawai-row').forEach(function (row) {
        row.style.display = (!q || row.dataset.search.includes(q)) ? '' : 'none';
    });
});
</script>
<?php endif; ?>