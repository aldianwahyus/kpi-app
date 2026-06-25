<div class="d-flex align-items-center gap-2 mb-3">
  <a href="<?= base_url('penilaian') ?>" class="btn btn-sm btn-light border">
    <i class="ti ti-arrow-left"></i>
  </a>
  <div>
    <h5 class="mb-0 fw-semibold" style="color:#1F4E79">
      Input Penilaian — <?= esc($pegawai['nama']) ?>
    </h5>
    <small class="text-muted">
      <?= esc($pegawai['jabatan'] ?? '') ?>
      &nbsp;·&nbsp; Periode: <strong><?= esc($periodeAktif['nama']) ?></strong>
    </small>
  </div>
</div>

<div class="card border-0 shadow-sm mb-3">
  <div class="card-header py-2" style="background:#F8F9FA">
    <span class="fw-semibold" style="color:#1F4E79;font-size:12px">
      <i class="ti ti-info-circle me-1"></i> Panduan Klasifikasi Nilai
    </span>
  </div>
  <div class="card-body py-2">
    <div class="row g-2 text-center">
      <?php $gradeInfo = (new \App\Services\KpiCalculationService())->getGradeInfo(); ?>
      <?php foreach ($gradeInfo as $g => $info): ?>
      <div class="col-3">
        <div style="background:<?= $info['bg'] ?>;color:<?= $info['color'] ?>;border-radius:6px;padding:6px">
          <div style="font-size:14px;font-weight:700"><?= $g ?></div>
          <div style="font-size:10px"><?= $info['range'] ?></div>
          <div style="font-size:10px;opacity:.85"><?= $info['label'] ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php
$current = $statusApproval['current'] ?? 'draft';
$bannerConfig = [
    'draft'     => ['bg'=>'bg-secondary', 'label'=>'DRAFT — Belum disubmit'],
    'submitted' => ['bg'=>'bg-warning text-dark', 'label'=>'SUBMITTED — Menunggu Approval'],
    'approved'  => ['bg'=>'bg-success', 'label'=>'APPROVED — Disetujui'],
    'rejected'  => ['bg'=>'bg-danger', 'label'=>'REJECTED — Ditolak'],
];
$bc = $bannerConfig[$current] ?? ['bg'=>'bg-secondary', 'label'=>'UNKNOWN'];
$isAdmin = (session()->get('role') === 'admin'); // Shortcut flag penentu Admin
?>

<div class="alert alert-info d-flex justify-content-between align-items-center mb-4">
    <div>
        <strong>Status Penilaian:</strong> 
        <span class="badge <?= $bc['bg'] ?>">
            <?= $bc['label'] ?>
        </span>
        <?php if ($current === 'rejected' && !empty($rejectNote)): ?>
            <div class="text-danger small mt-1"><strong>Alasan Reject:</strong> <?= esc($rejectNote) ?></div>
        <?php endif; ?>
    </div>

    <div>
        <?php if ($current === 'draft' || $current === 'rejected'): ?>
            <form action="<?= base_url('penilaian/submit/' . $pegawai['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin mengirim penilaian ini untuk diapprove?')">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-warning btn-sm">
                    <i class="ti ti-send"></i> Submit untuk Approval
                </button>
            </form>
        <?php endif; ?>

        <?php if ($current === 'submitted' && in_array($role, ['admin', 'hr', 'kepala_unit'])): ?>
            <form action="<?= base_url('penilaian/approve/' . $pegawai['id']) ?>" method="POST" class="d-inline me-2" onsubmit="return confirm('Setujui penilaian ini?')">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-success btn-sm">
                    <i class="ti ti-check"></i> Approve
                </button>
            </form>
            
            <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalReject">
                <i class="ti ti-x"></i> Reject
            </button>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="modalReject" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title fw-semibold text-danger">
          <i class="ti ti-x me-1"></i> Reject Penilaian
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="<?= base_url("penilaian/reject/{$pegawai['id']}") ?>" method="post">
        <?= csrf_field() ?>
        <div class="modal-body">
          <label class="form-label fw-semibold small">
            Catatan / Alasan Penolakan <span class="text-danger">*</span>
          </label>
          <textarea name="reject_note" class="form-control" rows="3" placeholder="Tuliskan alasan penolakan..." required></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-danger btn-sm">
            <i class="ti ti-x me-1"></i> Konfirmasi Reject
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php if (!$isAdmin): ?>
<div class="alert py-2 mb-3 d-flex align-items-center gap-2" style="background:#FFF3CD;border:1px solid #BF9000;font-size:13px">
  <i class="ti ti-info-circle" style="color:#BF9000"></i>
  <span style="color:#7F6000">
    <strong>Perhatian:</strong> Master <strong>Target & Polarity</strong> hanya dapat dikelola oleh Administrator. Anda bertugas mengisi pencapaian pada kolom <strong>Realisasi</strong>.
  </span>
</div>
<?php endif; ?>

<div class="alert py-2 mb-3 d-flex align-items-center gap-2" style="background:#E6F1FB;border:1px solid #2E75B6;font-size:13px">
  <i class="ti ti-info-circle" style="color:#1F4E79"></i>
  <span style="color:#1F4E79">
    <strong>Panduan:</strong> Masukkan capaian angka riil di lapangan pada kolom <strong>Realisasi</strong>. Sistem akan menghitung <strong>Skor</strong> &amp; <strong>Kontribusi</strong> secara otomatis berdasarkan konfigurasi target dan karakteristik KPI.
  </span>
</div>

<form action="<?= base_url("penilaian/store/{$pegawai['id']}") ?>" method="post" id="form-penilaian">
  <?= csrf_field() ?>

  <?php
  $perspektif_colors = [
      'Financial'         => ['bg'=>'#E6F1FB','border'=>'#2E75B6','text'=>'#1F4E79'],
      'Customer'          => ['bg'=>'#EAF3DE','border'=>'#70AD47','text'=>'#375623'],
      'Internal Process'  => ['bg'=>'#FFF3CD','border'=>'#BF9000','text'=>'#7F6000'],
      'Learning & Growth' => ['bg'=>'#F3E5F5','border'=>'#9B59B6','text'=>'#5C2A6B'],
  ];

  $grouped = [];
  foreach ($kpiList as $kpi) {
      $grouped[$kpi['perspektif']][] = $kpi;
  }
  ?>

  <?php foreach ($grouped as $perspektif => $kpis): ?>
  <?php $c = $perspektif_colors[$perspektif] ?? ['bg'=>'#f8f9fa','border'=>'#dee2e6','text'=>'#333']; ?>
  <div class="card mb-3 border-0 shadow-sm">
    <div class="card-header py-2 d-flex align-items-center justify-content-between" style="background:<?= $c['bg'] ?>;border-left:4px solid <?= $c['border'] ?>">
      <span class="fw-semibold" style="color:<?= $c['text'] ?>;font-size:13px">
        <?= esc($perspektif) ?>
      </span>
      <?php $bobot_perspektif = array_sum(array_column($kpis, 'bobot')); ?>
      <span class="badge" style="background:<?= $c['border'] ?>;font-size:11px">
        Bobot Perspektif: <?= round($bobot_perspektif * 100, 1) ?>%
      </span>
    </div>
    <div class="card-body p-0">
      <table class="table table-sm align-middle mb-0" style="font-size:13px">
        <thead style="background:#f8fafc">
          <tr>
            <th style="width:30px"></th>
            <th>Nama Parameter KPI</th>
            <th style="width:70px" class="text-center">Bobot</th>
            <th style="width:110px" class="text-center">Target</th>
            <th style="width:140px" class="text-center">Realisasi</th>
            <th style="width:90px" class="text-center">Skor</th>
            <th style="width:100px" class="text-center">Kontribusi</th>
            <th style="width:180px">Catatan</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($kpis as $kpi): ?>
          <?php 
            $ex       = $existing[$kpi['kpi_id']] ?? null; 
            $target   = (float)($kpi['target'] ?? 100); 
            $bobot    = (float)($kpi['bobot'] ?? 0);
            $polarity = $kpi['polarity'] ?? 'max';
            $isCapped = isset($kpi['is_capped']) ? (int)$kpi['is_capped'] : 1;
          ?>
          <tr class="kpi-row" 
              data-kpi="<?= $kpi['kpi_id'] ?>" 
              data-target="<?= $target ?>" 
              data-bobot="<?= $bobot * 100 ?>" 
              data-polarity="<?= esc($polarity) ?>" 
              data-capped="<?= $isCapped ?>">
              
            <td class="text-center">
              <code style="font-size:10px;color:#888"><?= esc($kpi['kode']) ?></code>
            </td>
            <td>
              <span class="fw-semibold"><?= esc($kpi['nama_kpi']) ?></span>
              <small class="text-muted d-block" style="font-size:11px">
                Satuan: <span class="badge bg-light text-dark border py-0 px-1"><?= esc($kpi['satuan']) ?></span> 
                | Polarity: <strong><?= strtoupper($polarity) ?></strong>
              </small>
            </td>
            <td class="text-center fw-semibold" style="color:#1F4E79">
              <?= round($bobot * 100, 1) ?>%
            </td>
            <td class="text-center fw-bold text-muted">
              <?= number_format($target, 2) ?>
            </td>
            <td class="text-center">
              <input type="number" name="realisasi[<?= $kpi['kpi_id'] ?>]" 
                     class="form-control form-control-sm realisasi-input text-center" 
                     value="<?= $ex ? $ex['realisasi'] : '' ?>" 
                     step="any" placeholder="0.00" required
                     <?= (($current === 'submitted' || $current === 'approved') && !$isAdmin) ? 'disabled' : '' ?>>
            </td>
            <td class="text-center">
              <input type="number" name="skor[<?= $kpi['kpi_id'] ?>]" 
                     class="form-control form-control-sm skor-output text-center fw-semibold" 
                     value="<?= $ex ? $ex['skor'] : '' ?>" 
                     readonly style="background:#f8f9fa; font-size:12px;">
            </td>
            <td class="text-center">
              <input type="number" name="nilai_kontribusi[<?= $kpi['kpi_id'] ?>]" 
                     class="form-control form-control-sm kontribusi-output text-center fw-bold" 
                     value="<?= $ex ? $ex['nilai_kontribusi'] : '' ?>" 
                     readonly style="background:#eec; font-size:12px; color:#1F4E79;">
            </td>
            <td>
              <input type="text" name="catatan[<?= $kpi['kpi_id'] ?>]" 
                     class="form-control form-control-sm" 
                     value="<?= esc($ex['catatan'] ?? '') ?>" placeholder="Opsional"
                     <?= (($current === 'submitted' || $current === 'approved') && !$isAdmin) ? 'disabled' : '' ?>>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endforeach; ?>

  <div class="d-flex gap-2 mt-2 mb-4">
    <?php if ($current === 'draft' || $current === 'rejected' || $isAdmin): ?>
        <button type="submit" class="btn btn-primary px-4">
          <i class="ti ti-device-floppy me-1"></i> Simpan Semua Penilaian
        </button>
    <?php endif; ?>
    <a href="<?= base_url('penilaian') ?>" class="btn btn-light border px-4">Kembali</a>
  </div>

  <?php if (!empty($histori)): ?>
  <div class="card border-0 shadow-sm mt-4">
    <div class="card-header py-2" style="background:#F3E5F5">
      <span class="fw-semibold" style="color:#5C2A6B;font-size:13px">
        <i class="ti ti-history me-1"></i> Histori Perubahan Penilaian
      </span>
    </div>
    <div class="card-body p-0" style="max-height:300px;overflow-y:auto">
      <table class="table table-sm align-middle mb-0" style="font-size:12px">
        <thead style="background:#f8fafc;position:sticky;top:0">
          <tr>
            <th style="width:140px">Waktu</th>
            <th style="width:80px" class="text-center">Aksi</th>
            <th>Oleh</th>
            <th>Jabatan</th>
            <th>Perubahan</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($histori as $h): ?>
          <?php
          $actionConfig = [
              'create'  => ['bg'=>'#C6EFCE','color'=>'#375623','label'=>'Buat'],
              'update'  => ['bg'=>'#BDD7EE','color'=>'#1F4E79','label'=>'Edit'],
              'submit'  => ['bg'=>'#FFF2CC','color'=>'#7F6000','label'=>'Submit'],
              'approve' => ['bg'=>'#C6EFCE','color'=>'#375623','label'=>'Approve'],
              'reject'  => ['bg'=>'#FCE4D6','color'=>'#C00000','label'=>'Reject'],
              'delete'  => ['bg'=>'#FFCCCC','color'=>'#7B0000','label'=>'Hapus'],
          ];
          $ac = $actionConfig[$h['action']] ?? ['bg'=>'#f0f0f0','color'=>'#888','label'=>$h['action']];
          ?>
          <tr>
            <td style="color:#888">
              <?= date('d M Y', strtotime($h['created_at'])) ?><br>
              <span style="font-size:11px"><?= date('H:i:s', strtotime($h['created_at'])) ?></span>
            </td>
            <td class="text-center">
              <span class="badge" style="background:<?= $ac['bg'] ?>; color:<?= $ac['color'] ?>;font-size:11px">
                <?= $ac['label'] ?>
              </span>
            </td>
            <td class="fw-semibold"><?= esc($h['user_nama']) ?></td>
            <td style="color:#888"><?= esc($h['user_jabatan'] ?? '—') ?></td>
            <td style="color:#555"><?= esc($h['keterangan']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('.kpi-row');

    rows.forEach(row => {
        const inputRealisasi = row.querySelector('.realisasi-input');
        const outputSkor     = row.querySelector('.skor-output');
        const outputKontrib  = row.querySelector('.kontribusi-output');

        if (!inputRealisasi) return;

        inputRealisasi.addEventListener('input', function() {
            const realisasi = this.value;
            const kpiId     = row.getAttribute('data-kpi');
            const pegawaiId = "<?= $pegawai['id'] ?>";

            if (realisasi === '') return;

            let params = new URLSearchParams();
            params.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
            params.append('kpi_id', kpiId);
            params.append('pegawai_id', pegawaiId);
            params.append('realisasi', realisasi);

            fetch("<?= site_url('penilaian/ajaxHitung') ?>", {
                method: "POST",
                body: params
            })
            .then(response => response.json())
            .then(data => {
                if (data.valid) {
                    // Paksa format 2 digit di belakang titik menggunakan toFixed(2)
                    outputSkor.value = parseFloat(data.skor).toFixed(2);
                    outputKontrib.value = parseFloat(data.kontribusi).toFixed(2);
                }
            });
        });
    });
});
</script>