<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h5 class="mb-0 fw-semibold" style="color:#1F4E79">
      <i class="ti ti-clipboard-list me-1"></i> Input Penilaian KPI
    </h5>
    <small class="text-muted">Pilih pegawai untuk mulai input penilaian</small>
  </div>
</div>

<?php if ($periodeAktif): ?>
<div class="alert py-2 mb-3 d-flex align-items-center gap-2"
     style="background:#E2EFDA;border:1px solid #70AD47">
  <i class="ti ti-calendar-check" style="color:#375623"></i>
  <span style="font-size:13px;color:#375623">
    Periode aktif: <strong><?= esc($periodeAktif['nama']) ?></strong>
    &nbsp;·&nbsp;
    <?= date('d M Y', strtotime($periodeAktif['tgl_mulai'])) ?>
    — <?= date('d M Y', strtotime($periodeAktif['tgl_selesai'])) ?>
  </span>
</div>
<?php else: ?>
<div class="alert alert-warning py-2 mb-3" style="font-size:13px">
  <i class="ti ti-alert-triangle me-1"></i>
  Tidak ada periode aktif. Hubungi Admin untuk membuka periode penilaian.
</div>
<?php endif; ?>

<?php
// 1. Pindahkan Instansiasi Service ke sini agar menghemat penggunaan memori server (Clean Code)
$calculator = new \App\Services\KpiCalculationService();

$grouped = [];
foreach ($pegawai as $p) {
    $grouped[$p['nama_divisi'] ?? 'Belum Ada Divisi'][] = $p;
}
?>

<?php foreach ($grouped as $divisi => $list): ?>
<div class="card mb-3 border-0 shadow-sm">
  <div class="card-header py-2"
       style="background:#E6F1FB;border-left:4px solid #2E75B6">
    <span class="fw-semibold" style="color:#1F4E79;font-size:13px">
      <i class="ti ti-building me-1"></i><?= esc($divisi) ?>
    </span>
  </div>
  <div class="card-body p-0">
    <table class="table table-sm table-hover align-middle mb-0" style="font-size:13px">
      <thead style="background:#f8fafc">
        <tr>
          <th>Nama Pegawai</th>
          <th>Jabatan</th>
          <th class="text-center">Setup KPI</th>
          <th class="text-center">KPI Diisi</th>
          <th class="text-center">Nilai Akhir</th>
          <th class="text-center">Grade</th>
          <th class="text-center">Status</th>
          <th class="text-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($list as $p): ?>
        <?php
          $r          = $rekap[$p['id']] ?? null;
          $nilai      = $r ? round((float)$r['nilai_akhir'], 2) : 0;
          $jumlah_kpi = $r['jumlah_kpi'] ?? 0;
          $status     = $r['status'] ?? 'draft';
          $sudahDiisi = $jumlah_kpi > 0;

          $grade       = '';
          $grade_bg    = '#f0f0f0';
          $grade_color = '#888';

          if ($nilai > 0) {
              $grade       = $calculator->getGrade($nilai);
              $gc          = $calculator->getGradeColor($grade);
              $grade_bg    = $gc['bg'];
              $grade_color = $gc['color'];
          }

          // ── Tentukan label & warna kolom Status, serta tombol Aksi per role ──
          // Mengikuti matriks: Drafter & Approver melihat informasi dan tombol
          // yang berbeda pada status yang sama, sesuai ketentuan alur approval.
          if (!$sudahDiisi || $status === 'draft') {
              $statusLabel = 'DRAFT — Belum disubmit';
              $statusBg    = '#f0f0f0';
              $statusColor = '#888';
              $aksiDrafter  = ['label' => $sudahDiisi ? 'Update' : 'Input', 'icon' => 'ti-edit',  'class' => 'btn-primary'];
              $aksiApprover = null; // Approver belum bisa apa-apa, belum disubmit
          } elseif ($status === 'submitted') {
              $statusLabel = 'SUBMITTED — Menunggu Approval';
              $statusBg    = '#FFF3CD';
              $statusColor = '#7F6000';
              $aksiDrafter  = ['label' => 'Lihat', 'icon' => 'ti-eye', 'class' => 'btn-outline-secondary'];
              $aksiApprover = ['label' => 'Review', 'icon' => 'ti-checklist', 'class' => 'btn-warning'];
          } elseif ($status === 'approved') {
              $statusLabel = 'APPROVED — Disetujui';
              $statusBg    = '#C6EFCE';
              $statusColor = '#375623';
              $aksiDrafter  = ['label' => 'Lihat', 'icon' => 'ti-eye', 'class' => 'btn-outline-secondary'];
              $aksiApprover = ['label' => 'Lihat', 'icon' => 'ti-eye', 'class' => 'btn-outline-secondary'];
          } elseif ($status === 'rejected') {
              $statusLabel = 'REJECTED — Ditolak';
              $statusBg    = '#FCE4D6';
              $statusColor = '#C00000';
              $aksiDrafter  = ['label' => 'Update', 'icon' => 'ti-edit', 'class' => 'btn-danger'];
              $aksiApprover = null; // sudah kembali ke tangan Drafter
          } else {
              $statusLabel = 'Belum Dilakukan Penginputan Penilaian';
              $statusBg    = '#f0f0f0';
              $statusColor = '#888';
              $aksiDrafter  = ['label' => 'Input', 'icon' => 'ti-edit', 'class' => 'btn-primary'];
              $aksiApprover = null;
          }

          $aksi = ($role === 'approver') ? $aksiApprover : $aksiDrafter;
        ?>
        <tr>
          <td class="fw-semibold">
            <?= esc($p['nama']) ?>
          </td>
          <td class="text-muted"><?= esc($p['jabatan'] ?? '—') ?></td>
          <td class="text-center">
            <?php $jumlahSetup = $kpiSetupCount[$p['id']] ?? 0; ?>
            <?php $bobotTotal  = $kpiBobotTotal[$p['id']] ?? 0; ?>
            <?php if ($jumlahSetup > 0 && round($bobotTotal, 2) == 1.00): ?>
              <span class="badge" style="background:#C6EFCE;color:#375623;font-size:11px">
                ✓ <?= $jumlahSetup ?> KPI
              </span>
            <?php elseif ($jumlahSetup > 0): ?>
              <span class="badge" style="background:#FCE4D6;color:#C00000;font-size:11px"
                    title="Total bobot KPI belum 100%, penginputan penilaian belum bisa dilakukan">
                Bobot <?= round($bobotTotal * 100, 2) ?>%
              </span>
            <?php else: ?>
              <span class="badge" style="background:#FCE4D6;color:#C00000;font-size:11px"
                    title="KPI belum di-setup oleh Admin">
                Belum di-setup
              </span>
            <?php endif; ?>
          </td>
          <td class="text-center">
            <?php if ($jumlah_kpi > 0): ?>
              <span class="badge bg-light text-dark border" style="font-size:11px">
                <?= $jumlah_kpi ?> KPI
              </span>
            <?php else: ?>
              <span class="text-muted" style="font-size:12px">Belum ada</span>
            <?php endif; ?>
          </td>
          <td class="text-center fw-bold" style="color:#1F4E79">
            <?= $nilai > 0 ? number_format($nilai, 2) : '—' ?>
          </td>
          <td class="text-center">
            <?php if ($grade): ?>
              <span class="badge fw-bold"
                    style="background:<?= $grade_bg ?>;
                           color:<?= $grade_color ?>;
                           font-size:12px;padding:4px 10px">
                <?= $grade ?>
              </span>
            <?php else: ?>
              <span class="text-muted">—</span>
            <?php endif; ?>
          </td>
          <td class="text-center">
            <span class="badge fw-semibold"
                  style="background:<?= $statusBg ?>;color:<?= $statusColor ?>;
                         font-size:11px;padding:4px 8px;white-space:normal">
              <?= esc($statusLabel) ?>
            </span>
          </td>
          <td class="text-center">
            <?php if ($periodeAktif && $aksi): ?>
            <a href="<?= base_url("penilaian/form/{$p['id']}") ?>"
               class="btn btn-sm <?= $aksi['class'] ?>" style="font-size:12px">
              <i class="ti <?= $aksi['icon'] ?> me-1"></i> <?= esc($aksi['label']) ?>
            </a>
            <?php elseif ($periodeAktif && !$aksi): ?>
              <span class="text-muted" style="font-size:11px">Menunggu Drafter</span>
            <?php else: ?>
            <span class="text-muted" style="font-size:12px">—</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endforeach; ?>