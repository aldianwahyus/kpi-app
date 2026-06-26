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
          <th class="text-center">KPI Diisi</th>
          <th class="text-center">Nilai Akhir</th>
          <th class="text-center">Grade</th>
          <th class="text-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($list as $p): ?>
        <?php
          $r          = $rekap[$p['id']] ?? null;
          $nilai      = $r ? round((float)$r['nilai_akhir'], 2) : 0;
          $jumlah_kpi = $r['jumlah_kpi'] ?? 0; 
          
          // Penentuan visual badge status approval secara dinamis
          $status       = $r['status'] ?? 'draft';
          $status_text  = 'DF';
          $status_bg    = '#6c757d'; 
          
          if ($status === 'submitted') {
              $status_text = 'SB';
              $status_bg   = '#0dcaf0'; 
          } elseif ($status === 'approved') {
              $status_text = 'AP';
              $status_bg   = '#198754'; 
          } elseif ($status === 'rejected') {
              $status_text = 'RJ';
              $status_bg   = '#dc3545'; 
          }

          $grade       = '';
          $grade_bg    = '#f0f0f0';
          $grade_color = '#888';
          
          if ($nilai > 0) {
              // 2. Tinggal panggil objek $calculator yang sudah diinisiasi di atas
              $grade       = $calculator->getGrade($nilai);
              $gc          = $calculator->getGradeColor($grade);
              $grade_bg    = $gc['bg'];
              $grade_color = $gc['color'];
          }
        ?>
        <tr>
          <td class="fw-semibold">
            <?= esc($p['nama']) ?>
            <span class="badge text-white fw-bold ms-1" 
                  style="background:<?= $status_bg ?>; font-size:10px; padding:2px 5px;" 
                  title="<?= strtoupper($status) ?>">
               <?= $status_text ?>
            </span>
          </td>
          <td class="text-muted"><?= esc($p['jabatan'] ?? '—') ?></td>
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
            <?php if ($periodeAktif): ?>
            <a href="<?= base_url("penilaian/form/{$p['id']}") ?>"
               class="btn btn-sm <?= $status === 'approved' ? 'btn-outline-secondary' : 'btn-primary' ?>" style="font-size:12px">
              <?php if ($status === 'approved'): ?>
                <i class="ti ti-eye me-1"></i> Lihat
              <?php else: ?>
                <i class="ti ti-edit me-1"></i>
                <?= $jumlah_kpi > 0 ? 'Update' : 'Input' ?>
              <?php endif; ?>
            </a>
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