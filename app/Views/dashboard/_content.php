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
          <div style="font-size:18px;font-weight:700;color:<?= $info['bg'] === '#1F4E79' ? '#1F4E79' : $info['color'] ?>">
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