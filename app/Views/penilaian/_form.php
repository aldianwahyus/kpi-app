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
          <div class="text-danger small mt-1">
            <strong>Alasan Reject:</strong> <?= esc($rejectNote) ?>
          </div>
        <?php endif; ?>

        <?php if ($current === 'approved' && $role === 'approver'): ?>
          <?php
          $latestDeclined = $statusApproval['latest_declined_draft_request'] ?? null;
          ?>
          <?php if ($latestDeclined): ?>
            <div class="alert alert-danger py-2 px-3 mt-2 mb-0" style="font-size:12px">
              <i class="ti ti-alert-triangle me-1"></i>
              <strong>Permintaan Draft Ulang Anda ditolak</strong> oleh Administrator
              pada <?= date('d M Y H:i', strtotime($latestDeclined['confirmed_at'])) ?>.
              <?php if (!empty($latestDeclined['catatan_admin'])): ?>
                <br>Alasan: <?= esc($latestDeclined['catatan_admin']) ?>
              <?php endif; ?>
              <br><span class="text-muted">Selengkapnya dapat ditelusuri pada Histori Perubahan Nilai di bawah.</span>
            </div>
          <?php endif; ?>
          <?php if ($statusApproval['has_pending_draft_request'] ?? false): ?>
            <span class="badge" style="background:#FFF3CD;color:#7F6000;font-size:12px">
              <i class="ti ti-clock me-1"></i> Menunggu konfirmasi Admin
            </span>
          <?php else: ?>
            <button type="button"
                    class="btn btn-outline-warning btn-sm"
                    style="font-size:12px"
                    data-bs-toggle="modal"
                    data-bs-target="#modalDraftUlang">
              <i class="ti ti-refresh-dot me-1"></i> Ajukan Draft Ulang
            </button>
          <?php endif; ?>
        <?php endif; ?>
    </div>
    <div>
        <?php if ($current === 'draft' || $current === 'rejected'): ?>
            <form action="<?= base_url('penilaian/submit/' . $pegawai['id']) ?>" method="POST" class="d-inline" onsubmit="return confirmAction(event, { title: 'Submit untuk Approval', text: 'Apakah Anda yakin ingin mengirim penilaian ini untuk diapprove?', icon: 'question', confirmText: 'Ya, Submit' })">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-warning btn-sm">
                    <i class="ti ti-send"></i> Submit untuk Approval
                </button>
            </form>
        <?php endif; ?>

        <?php if ($current === 'submitted' && in_array($role, ['admin', 'hr', 'approver'])): ?>
            <form action="<?= base_url('penilaian/approve/' . $pegawai['id']) ?>" method="POST" class="d-inline me-2" onsubmit="return confirmAction(event, { title: 'Approve Penilaian', text: 'Setujui penilaian ini?', icon: 'question', confirmText: 'Ya, Setujui' })">
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

<!-- Modal Request Draft Ulang -->
<div class="modal fade" id="modalDraftUlang" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title fw-semibold text-warning">
          <i class="ti ti-refresh-dot me-1"></i> Ajukan Draft Ulang
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="<?= base_url("draft-ulang/request-pegawai/{$pegawai['id']}") ?>" method="post">
        <?= csrf_field() ?>
        <div class="modal-body">
          <div class="alert alert-warning py-2" style="font-size:12px">
            Permintaan akan dikirim ke <strong>Administrator</strong> untuk konfirmasi.
            Status tidak langsung berubah sampai dikonfirmasi.
          </div>
          <label class="form-label fw-semibold small">
            Alasan Draft Ulang <span class="text-danger">*</span>
          </label>
          <textarea name="alasan" class="form-control" rows="3"
                    placeholder="Jelaskan alasan perlu draft ulang..." required></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-warning btn-sm">
            <i class="ti ti-send me-1"></i> Kirim Permintaan
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
            <th style="width:120px" class="text-center">Realisasi</th>
            <th style="width:100px" class="text-center">Target</th>
            <th style="width:90px" class="text-center">Pencapaian</th>
            <th style="width:70px" class="text-center">Skor</th>
            <th style="width:70px" class="text-center">Nilai</th>
            <th style="width:70px" class="text-center">Bobot</th>
            <th style="width:90px" class="text-center">Total</th>
            <th style="width:160px">Catatan</th>
          </tr>
        </thead>
        <tbody>
          <?php $calc = $calc ?? new \App\Services\KpiCalculationService(); ?>
          <?php foreach ($kpis as $kpi): ?>
          <?php
            $ex       = $existing[$kpi['kpi_id']] ?? null;
            $target   = (float)($kpi['target'] ?? 100);
            $bobot    = (float)($kpi['bobot'] ?? 0);
            $polarity = $kpi['polarity'] ?? 'max';
            $isCapped = isset($kpi['is_capped']) ? (int)$kpi['is_capped'] : 1;

            $listTurunan  = $turunanByInduk[$kpi['id']] ?? [];
            $punyaTurunan = !empty($listTurunan);
            $realisasiTurunanExisting = $realisasiTurunan[$kpi['id']] ?? [];

            // Kolom Pencapaian hanya bermakna sebagai rasio tunggal untuk
            // KPI tanpa Parameter Turunan berpolarity max/min/precise — KPI
            // Induk yang skornya rata-rata tertimbang dari Turunan bersifat
            // informatif saja (bukan Skor asli); 'special' bersifat biner
            // (Ada/Tidak Ada); 'tertimbang' punya DUA indikator terpisah
            // sehingga ditampilkan sebagai rincian Skor Dasar x Pengkali.
            $pencapaianDisplay = null;
            if (!$punyaTurunan && $ex) {
                if (in_array($polarity, ['max', 'min', 'precise'], true) && $ex['realisasi'] !== null && $target > 0) {
                    $pRaw = $calc->hitungPencapaianPersen((float)$ex['realisasi'], $target, $polarity === 'precise' ? 'max' : $polarity);
                    $pencapaianDisplay = is_infinite($pRaw) ? '∞' : number_format($pRaw, 2) . '%';
                } elseif ($polarity === 'special' && $ex['realisasi'] !== null) {
                    $pencapaianDisplay = ((float)$ex['realisasi'] == 1.0) ? 'Ada' : 'Tidak Ada';
                } elseif ($polarity === 'tertimbang' && $ex['realisasi'] !== null && ($ex['realisasi_harian'] ?? null) !== null) {
                    $skorIndikatorDisplay = $calc->hitungSkorCapaian((float)$ex['realisasi'], $target, 'max', true);
                    $pengkaliDisplay      = $calc->hitungPengkaliHarian((float)$ex['realisasi_harian']);
                    $pencapaianDisplay = number_format($skorIndikatorDisplay, 1) . ' × ' . number_format($pengkaliDisplay * 100, 0) . '%';
                }
            }
          ?>
          <tr class="kpi-row" 
              data-kpi="<?= $kpi['kpi_id'] ?>" 
              data-induk-id="<?= $kpi['id'] ?>"
              data-target="<?= $target ?>" 
              data-bobot="<?= $bobot * 100 ?>" 
              data-polarity="<?= esc($polarity) ?>" 
              data-capped="<?= $isCapped ?>"
              data-punya-turunan="<?= $punyaTurunan ? '1' : '0' ?>">
              
            <td class="text-center">
              <code style="font-size:10px;color:#888"><?= esc($kpi['kode']) ?></code>
            </td>
            <td>
              <span class="fw-semibold"><?= esc($kpi['nama_kpi']) ?></span>
              <small class="text-muted d-block" style="font-size:11px">
                Satuan: <span class="badge bg-light text-dark border py-0 px-1"><?= esc($kpi['satuan']) ?></span> 
                | Polarity: <strong><?= strtoupper($polarity) ?></strong>
                <?php if ($punyaTurunan): ?>
                  <span class="badge bg-light text-dark border ms-1" style="font-size:10px">
                    <?= count($listTurunan) ?> Parameter Turunan
                  </span>
                <?php endif; ?>
              </small>
              <?php if (!empty($kpi['deskripsi_target'])): ?>
              <div class="mt-1 d-flex align-items-start gap-1"
                   style="font-size:11px;background:#EFF6FF;border-left:3px solid #2E75B6;
                          padding:3px 7px;border-radius:0 4px 4px 0">
                <i class="ti ti-info-circle mt-1 flex-shrink-0" style="color:#2E75B6;font-size:12px"></i>
                <span style="color:#1F4E79"><?= esc($kpi['deskripsi_target']) ?></span>
              </div>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <?php
              $currentStatus = $statusApproval['current'] ?? 'draft';

              // Matriks akses Realisasi berdasarkan Role & Status:
              // - Admin            : selalu dapat mengedit, kapan pun.
              // - Drafter          : dapat mengedit saat status draft atau rejected
              //                      (termasuk saat belum ada data sama sekali).
              // - Approver         : dapat mengedit HANYA saat status submitted
              //                      (melakukan Review), terkunci di status lain.
              if ($role === 'admin') {
                  $isRealisasiReadonly = false;
              } elseif ($role === 'approver') {
                  $isRealisasiReadonly = ($currentStatus !== 'submitted');
              } else {
                  // Drafter & role lainnya
                  $isRealisasiReadonly = !in_array($currentStatus, ['draft', 'rejected']);
              }
              ?>
              <?php $roAttr = $isRealisasiReadonly ? 'readonly style="background:#f8f9fa;cursor:not-allowed"' : ''; ?>
              <?php if ($punyaTurunan): ?>
                <!-- Realisasi Induk selalu readonly & otomatis terisi SUM
                     Realisasi seluruh Turunan, dihitung ulang real-time oleh
                     JavaScript setiap kali salah satu input Turunan berubah. -->
                <input type="number" id="realisasi_induk_<?= $kpi['kpi_id'] ?>"
                       class="form-control realisasi-induk-readonly text-center"
                       value="<?= $ex ? number_format((float)$ex['realisasi'], 2, '.', '') : '' ?>"
                       readonly style="background:#f8f9fa;cursor:not-allowed"
                       title="Otomatis dari SUM Realisasi Parameter Turunan di bawah">
              <?php elseif ($polarity === 'special'): ?>
                <!-- Special Scoring: penilaian biner Ada/Tidak Ada, bukan angka -->
                <select name="realisasi[<?= $kpi['kpi_id'] ?>]" class="form-select form-select-sm realisasi-input" <?= $roAttr ?>>
                  <option value=""  <?= ($ex === null || $ex['realisasi'] === null) ? 'selected' : '' ?>>-- Pilih --</option>
                  <option value="1" <?= ($ex && (float)$ex['realisasi'] == 1.0) ? 'selected' : '' ?>>Ada</option>
                  <option value="0" <?= ($ex && (float)$ex['realisasi'] == 0.0) ? 'selected' : '' ?>>Tidak Ada</option>
                </select>
              <?php elseif ($polarity === 'tertimbang'): ?>
                <!-- Scoring Tertimbang: Realisasi Posisi Akhir (vs Target) +
                     Rata-rata Harian (persentase langsung, sudah dihitung di
                     luar sistem selama periode penilaian, bukan rasio
                     realisasi/target). -->
                <div class="mb-1">
                  <small class="text-muted" style="font-size:10px">Posisi Akhir</small>
                  <input type="number" name="realisasi[<?= $kpi['kpi_id'] ?>]"
                         class="form-control form-control-sm realisasi-input"
                         value="<?= ($ex && $ex['realisasi'] !== null) ? rtrim(rtrim(sprintf('%.4f', (float)$ex['realisasi']), '0'), '.') : '' ?>" <?= $roAttr ?>>
                </div>
                <div>
                  <small class="text-muted" style="font-size:10px">Rata-rata Harian (%)</small>
                  <div class="input-group input-group-sm">
                    <input type="number" name="realisasi_harian[<?= $kpi['kpi_id'] ?>]"
                           class="form-control form-control-sm realisasi-harian-input"
                           value="<?= ($ex && isset($ex['realisasi_harian'])) ? $ex['realisasi_harian'] : '' ?>" <?= $roAttr ?>>
                    <span class="input-group-text" style="font-size:11px">%</span>
                  </div>
                </div>
              <?php else: ?>
                <input type="number" name="realisasi[<?= $kpi['kpi_id'] ?>]"
                       class="form-control realisasi-input"
                       value="<?= ($ex && $ex['realisasi'] !== null) ? rtrim(rtrim(sprintf('%.4f', (float)$ex['realisasi']), '0'), '.') : '' ?>" <?= $roAttr ?>>
              <?php endif; ?>
            </td>
            <td class="text-center fw-bold text-muted">
              <?php if ($polarity === 'special'): ?>
                <span title="Tidak berlaku untuk Special Scoring">—</span>
              <?php elseif ($polarity === 'tertimbang'): ?>
                <div style="font-size:11px"><?= number_format($target, 2) ?></div>
                <div style="font-size:10px" class="fw-normal">(Harian: % langsung)</div>
              <?php else: ?>
                <?= number_format($target, 2) ?>
              <?php endif; ?>
            </td>
            <td class="text-center fw-semibold pencapaian-output" style="color:#1F4E79">
              <?php if ($punyaTurunan): ?>
                <span class="text-muted" style="font-size:11px">Lihat per Parameter Turunan</span>
              <?php else: ?>
                <?= $pencapaianDisplay ?? '' ?>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <input type="number" name="skor[<?= $kpi['kpi_id'] ?>]"
                     class="form-control form-control-sm skor-output text-center fw-semibold"
                     value="<?= $ex ? number_format((float)$ex['skor'], 2, '.', '') : '' ?>"
                     readonly style="background:#f8f9fa; font-size:12px;">
            </td>
            <td class="text-center">
              <input type="number"
                     class="form-control form-control-sm nilai-output text-center fw-semibold"
                     value="<?= $ex ? number_format((float)$ex['skor'], 2, '.', '') : '' ?>"
                     readonly style="background:#f8f9fa; font-size:12px;"
                     title="Nilai = Skor (identik, sesuai skema kriteria pencapaian)">
            </td>
            <td class="text-center fw-semibold" style="color:#1F4E79">
              <?= round($bobot * 100, 1) ?>%
            </td>
            <td class="text-center">
              <input type="text" inputmode="decimal" name="nilai_kontribusi[<?= $kpi['kpi_id'] ?>]"
                     class="form-control form-control-sm kontribusi-output text-center fw-bold"
                     value="<?= $ex ? number_format((float)$ex['nilai_kontribusi'], 2, '.', '') : '' ?>"
                     readonly style="background:#eec; font-size:12px; color:#1F4E79;">
            </td>
            <td>
              <?php if (!$punyaTurunan): ?>
              <input type="text" name="catatan[<?= $kpi['kpi_id'] ?>]"
                     class="form-control form-control-sm"
                     value="<?= esc($ex['catatan'] ?? '') ?>" placeholder="Opsional"
                     <?= $isRealisasiReadonly ? 'disabled' : '' ?>>
              <?php else: ?>
              <span class="text-muted" style="font-size:11px">Lihat per Parameter Turunan</span>
              <?php endif; ?>
            </td>
          </tr>

          <?php if ($punyaTurunan): ?>
          <!-- Sub-baris: input Realisasi per Parameter Turunan, ditampilkan
               nested tepat di bawah baris Induknya pada tabel yang sama. -->
          <?php foreach ($listTurunan as $t): ?>
          <?php $exT = $realisasiTurunanExisting[$t['id']] ?? null; ?>
          <?php
            $tPolarity = $t['polarity'] ?? 'max';
            $tTarget   = (float)$t['target'];
            $pencapaianTDisplay = null;
            if (in_array($tPolarity, ['max', 'min', 'precise'], true) && $exT && $exT['realisasi'] !== null && $tTarget > 0) {
                $pRawT = $calc->hitungPencapaianPersen((float)$exT['realisasi'], $tTarget, $tPolarity === 'precise' ? 'max' : $tPolarity);
                $pencapaianTDisplay = is_infinite($pRawT) ? '∞' : number_format($pRawT, 2) . '%';
            } elseif ($tPolarity === 'special' && $exT && $exT['realisasi'] !== null) {
                $pencapaianTDisplay = ((float)$exT['realisasi'] == 1.0) ? 'Ada' : 'Tidak Ada';
            } elseif ($tPolarity === 'tertimbang' && $exT && $exT['realisasi'] !== null && ($exT['realisasi_harian'] ?? null) !== null) {
                $skorIndikatorT = $calc->hitungSkorCapaian((float)$exT['realisasi'], $tTarget, 'max', true);
                $pengkaliT      = $calc->hitungPengkaliHarian((float)$exT['realisasi_harian']);
                $pencapaianTDisplay = number_format($skorIndikatorT, 1) . ' × ' . number_format($pengkaliT * 100, 0) . '%';
            }
            $tPolarityLabels = [
                'max'        => ['↑ Max', '#375623'],
                'min'        => ['↓ Min', '#C00000'],
                'precise'    => ['◎ Precise', '#1F4E79'],
                'special'    => ['⚑ Special', '#7F6000'],
                'tertimbang' => ['⚖ Tertimbang', '#5C2A6B'],
            ];
            [$tPolarityLabel, $tPolarityColor] = $tPolarityLabels[$tPolarity] ?? ['—', '#888'];
            $roAttrT = $isRealisasiReadonly ? 'readonly style="background:#f0f0f0;cursor:not-allowed"' : '';
          ?>
          <tr class="turunan-row" style="background:#FAFBFC" data-parent-induk="<?= $kpi['kpi_id'] ?>">
            <td></td>
            <td style="padding-left:32px">
              <i class="ti ti-corner-down-right me-1" style="color:#aaa"></i>
              <span style="font-size:12px"><?= esc($t['nama_turunan']) ?></span>
              <small class="text-muted d-block" style="font-size:10px;margin-left:18px">
                <?php if (!empty($t['satuan'])): ?>
                  Satuan: <strong><?= esc($t['satuan']) ?></strong> &nbsp;·&nbsp;
                <?php endif; ?>
                <span style="color:<?= $tPolarityColor ?>"><?= $tPolarityLabel ?></span>
              </small>
              <?php if (!empty($t['deskripsi_target'])): ?>
              <div class="mt-1 d-flex align-items-start gap-1"
                   style="font-size:10px;background:#EFF6FF;border-left:2px solid #2E75B6;
                          padding:2px 6px;border-radius:0 3px 3px 0;margin-left:18px">
                <i class="ti ti-info-circle flex-shrink-0" style="color:#2E75B6;font-size:10px;margin-top:1px"></i>
                <span style="color:#1F4E79"><?= esc($t['deskripsi_target']) ?></span>
              </div>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <?php if ($tPolarity === 'special'): ?>
                <select name="realisasi_turunan[<?= $kpi['id'] ?>][<?= $t['id'] ?>]"
                        class="form-select form-select-sm realisasi-turunan-input"
                        data-turunan-id="<?= $t['id'] ?>"
                        data-induk-kpi="<?= $kpi['kpi_id'] ?>"
                        data-induk-kp-id="<?= $kpi['id'] ?>"
                        data-bobot-turunan="<?= (float)$t['bobot'] ?>"
                        data-bobot-induk="<?= (float)$kpi['bobot'] ?>"
                        <?= $roAttrT ?>>
                  <option value=""  <?= ($exT === null || $exT['realisasi'] === null) ? 'selected' : '' ?>>-- Pilih --</option>
                  <option value="1" <?= ($exT && (float)$exT['realisasi'] == 1.0) ? 'selected' : '' ?>>Ada</option>
                  <option value="0" <?= ($exT && (float)$exT['realisasi'] == 0.0) ? 'selected' : '' ?>>Tidak Ada</option>
                </select>
              <?php elseif ($tPolarity === 'tertimbang'): ?>
                <div class="mb-1">
                  <small class="text-muted" style="font-size:10px">Akhir</small>
                  <input type="number"
                         name="realisasi_turunan[<?= $kpi['id'] ?>][<?= $t['id'] ?>]"
                         class="form-control form-control-sm realisasi-turunan-input"
                         data-turunan-id="<?= $t['id'] ?>"
                         data-induk-kpi="<?= $kpi['kpi_id'] ?>"
                         data-induk-kp-id="<?= $kpi['id'] ?>"
                         data-bobot-turunan="<?= (float)$t['bobot'] ?>"
                         data-bobot-induk="<?= (float)$kpi['bobot'] ?>"
                         value="<?= $exT ? number_format((float)$exT['realisasi'], 2, '.', '') : '' ?>" <?= $roAttrT ?>>
                </div>
                <div>
                  <small class="text-muted" style="font-size:10px">Harian (%)</small>
                  <div class="input-group input-group-sm">
                    <input type="number"
                           name="realisasi_turunan_harian[<?= $kpi['id'] ?>][<?= $t['id'] ?>]"
                           class="form-control form-control-sm realisasi-turunan-harian-input"
                           data-turunan-id="<?= $t['id'] ?>"
                           data-induk-kpi="<?= $kpi['kpi_id'] ?>"
                           value="<?= ($exT && isset($exT['realisasi_harian'])) ? $exT['realisasi_harian'] : '' ?>" <?= $roAttrT ?>>
                    <span class="input-group-text" style="font-size:10px">%</span>
                  </div>
                </div>
              <?php else: ?>
                <input type="number"
                       name="realisasi_turunan[<?= $kpi['id'] ?>][<?= $t['id'] ?>]"
                       class="form-control form-control-sm realisasi-turunan-input"
                       data-turunan-id="<?= $t['id'] ?>"
                       data-induk-kpi="<?= $kpi['kpi_id'] ?>"
                       data-induk-kp-id="<?= $kpi['id'] ?>"
                       data-bobot-turunan="<?= (float)$t['bobot'] ?>"
                       data-bobot-induk="<?= (float)$kpi['bobot'] ?>"
                       value="<?= $exT ? number_format((float)$exT['realisasi'], 2, '.', '') : '' ?>" <?= $roAttrT ?>>
              <?php endif; ?>
            </td>
            <td class="text-center text-muted" style="font-size:12px">
              <?php if ($tPolarity === 'special'): ?>
                <span title="Tidak berlaku untuk Special Scoring">—</span>
              <?php elseif ($tPolarity === 'tertimbang'): ?>
                <div><?= number_format($tTarget, 2) ?></div>
                <div style="font-size:10px">(Harian: % langsung)</div>
              <?php else: ?>
                <?= number_format($tTarget, 2) ?>
              <?php endif; ?>
            </td>
            <td class="text-center fw-semibold pencapaian-turunan-output" id="pencapaianT_<?= $t['id'] ?>" style="font-size:11px;color:#1F4E79">
              <?= $pencapaianTDisplay ?? '' ?>
            </td>
            <td class="text-center" colspan="2">
              <!-- Skor & Nilai per Turunan identik — satu badge untuk keduanya.
                   Diisi real-time oleh JS via ajaxHitungTurunan. -->
              <span class="skor-turunan-badge badge bg-secondary"
                    id="skorT_<?= $t['id'] ?>"
                    style="font-size:11px;display:<?= ($exT && $exT['skor']) ? 'inline' : 'none' ?>">
                <?= $exT ? number_format((float)($exT['skor'] ?? 0), 2) : '' ?>
              </span>
            </td>
            <td class="text-center text-muted" style="font-size:12px">
              <?= round((float)$t['bobot'] * 100, 2) ?>%
            </td>
            <td class="text-center fw-bold kontribusi-turunan-output" id="kontribT_<?= $t['id'] ?>" style="font-size:12px;color:#1F4E79">
              <?= ($exT && isset($exT['nilai_kontribusi'])) ? number_format((float)$exT['nilai_kontribusi'], 2) : '' ?>
            </td>
            <td>
              <input type="text"
                     name="catatan_turunan[<?= $kpi['id'] ?>][<?= $t['id'] ?>]"
                     class="form-control form-control-sm"
                     value="<?= esc($exT['catatan'] ?? '') ?>" placeholder="Opsional"
                     <?= $isRealisasiReadonly ? 'disabled' : '' ?>>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endforeach; ?>

  <div class="d-flex gap-2 mt-2 mb-4">
    <?php
    $canShowSaveButton = ($current !== 'approved')
        && !($role === 'approver' && $current !== 'submitted');
    ?>
    <?php if ($canShowSaveButton): ?>
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
              'create'              => ['bg'=>'#C6EFCE','color'=>'#375623','label'=>'Buat'],
              'update'              => ['bg'=>'#BDD7EE','color'=>'#1F4E79','label'=>'Edit'],
              'submit'              => ['bg'=>'#FFF2CC','color'=>'#7F6000','label'=>'Submit'],
              'approve'             => ['bg'=>'#C6EFCE','color'=>'#375623','label'=>'Approve'],
              'reject'              => ['bg'=>'#FCE4D6','color'=>'#C00000','label'=>'Reject'],
              'delete'              => ['bg'=>'#FFCCCC','color'=>'#7B0000','label'=>'Hapus'],
              'draft_ulang'         => ['bg'=>'#E0E7FF','color'=>'#3730A3','label'=>'Draft Ulang Dikonfirmasi'],
              'draft_ulang_ditolak' => ['bg'=>'#FCE4D6','color'=>'#C00000','label'=>'Draft Ulang Ditolak'],
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
// Token CSRF disimpan sebagai variabel yang dapat diperbarui, bukan
// nilai statis — karena Config\Security::$regenerate = true membuat
// token berubah setelah setiap verifikasi berhasil. Tanpa pembaruan
// ini, permintaan AJAX kedua dan seterusnya (preview Skor/Kontribusi
// saat mengetik Realisasi) akan ditolak server dengan error 403
// begitu filter CSRF global diaktifkan.
let csrfTokenName  = '<?= csrf_token() ?>';
let csrfHashValue  = '<?= csrf_hash() ?>';

// Sinkronkan hidden input CSRF di form utama setiap kali token
// di-regenerate oleh respons AJAX — tanpa ini, submit form akan
// mengirim token lama yang sudah tidak valid (SecurityException #403).
function updateCsrfHiddenInput(newHash) {
    const hiddenInputs = document.querySelectorAll(
        'input[type="hidden"][name="' + csrfTokenName + '"]'
    );
    hiddenInputs.forEach(function(el) { el.value = newHash; });
}

document.addEventListener('DOMContentLoaded', function() {
    // Dipakai bersama oleh handler realisasi KPI utama maupun Parameter
    // Turunan di bawah — sebelumnya hanya dideklarasikan lokal di dalam
    // handler KPI utama, sehingga handler Turunan gagal (ReferenceError:
    // pegawaiId is not defined) setiap kali diketik, dan preview Skor/
    // Pencapaian Turunan tidak pernah tampil.
    const pegawaiId = "<?= $pegawai['id'] ?>";

    const rows = document.querySelectorAll('.kpi-row');

    rows.forEach(row => {
        const inputRealisasi  = row.querySelector('.realisasi-input');
        const inputHarian     = row.querySelector('.realisasi-harian-input');
        const outputSkor      = row.querySelector('.skor-output');
        const outputNilai     = row.querySelector('.nilai-output');
        const outputKontrib   = row.querySelector('.kontribusi-output');
        const outputPencapaian = row.querySelector('.pencapaian-output');

        if (!inputRealisasi) return;

        // Untuk polarity 'tertimbang', KEDUA indikator (Posisi Akhir & Rata-
        // rata Harian) harus terisi sebelum preview dihitung — perubahan di
        // salah satu input tetap harus membaca nilai TERKINI dari keduanya.
        function hitungPreviewInduk() {
            const realisasi = inputRealisasi.value;
            const kpiId     = row.getAttribute('data-kpi');

            if (realisasi === '') return;
            if (inputHarian && inputHarian.value === '') return;

            let params = new URLSearchParams();
            params.append(csrfTokenName, csrfHashValue);
            params.append('kpi_id', kpiId);
            params.append('pegawai_id', pegawaiId);
            params.append('realisasi', realisasi);
            if (inputHarian) params.append('realisasi_harian', inputHarian.value);

            fetch("<?= site_url('penilaian/ajaxHitung') ?>", {
                method: "POST",
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: params
            })
            .then(response => response.json())
            .then(data => {
                if (data.csrf_hash) {
                    csrfHashValue = data.csrf_hash;
                    updateCsrfHiddenInput(data.csrf_hash);
                }

                if (data.valid) {
                    // Paksa format 2 digit di belakang titik menggunakan toFixed(2)
                    outputSkor.value = parseFloat(data.skor).toFixed(2);
                    outputNilai.value = parseFloat(data.nilai).toFixed(2); // Nilai = Skor (identik)
                    outputKontrib.value = parseFloat(data.kontribusi).toFixed(2);

                    if (outputPencapaian) {
                        if (data.pencapaian_tak_terhingga) {
                            outputPencapaian.textContent = '∞';
                        } else if (data.pencapaian !== null) {
                            outputPencapaian.textContent = parseFloat(data.pencapaian).toFixed(2) + '%';
                        } else {
                            outputPencapaian.textContent = '';
                        }
                    }
                }
            });
        }

        inputRealisasi.addEventListener('input', hitungPreviewInduk);
        inputRealisasi.addEventListener('change', hitungPreviewInduk);
        if (inputHarian) {
            inputHarian.addEventListener('input', hitungPreviewInduk);
            inputHarian.addEventListener('change', hitungPreviewInduk);
        }
    });

    // ── Penanganan input Realisasi pada Parameter Turunan ──────────
    // Setiap perubahan Realisasi Turunan: panggil ajaxHitungTurunan
    // untuk mendapat skor individual Turunan tersebut, tampilkan di
    // badge inline, lalu hitung ulang Skor Induk sebagai rata-rata
    // tertimbang (Cara B: ΣKontribusiT / BobotInduk). Untuk Turunan
    // ber-polarity 'tertimbang', KEDUA indikator (Akhir & Harian) harus
    // terisi sebelum preview dihitung — satu handler dipasang per Turunan
    // (bukan per elemen input) supaya bisa membaca nilai TERKINI kedua
    // field sekaligus, mana pun yang baru saja diketik.
    const turunanIds = new Set();
    document.querySelectorAll('.realisasi-turunan-input').forEach(function (el) {
        turunanIds.add(el.getAttribute('data-turunan-id'));
    });

    turunanIds.forEach(function (turunanId) {
        const mainInput   = document.querySelector('.realisasi-turunan-input[data-turunan-id="' + turunanId + '"]');
        const harianInput = document.querySelector('.realisasi-turunan-harian-input[data-turunan-id="' + turunanId + '"]');
        if (!mainInput) return;

        const indukKpiId     = mainInput.getAttribute('data-induk-kpi');
        const badge          = document.getElementById('skorT_' + turunanId);
        const pencapaianCell = document.getElementById('pencapaianT_' + turunanId);
        const kontribCell    = document.getElementById('kontribT_' + turunanId);

        function hitungPreviewTurunan() {
            const realisasiStr = mainInput.value;
            const realisasi     = parseFloat(realisasiStr);
            const harianStr     = harianInput ? harianInput.value : null;
            const realisasiHarian = harianInput ? parseFloat(harianStr) : null;

            // Hanya field yang benar-benar kosong yang tidak dihitung.
            // Realisasi = 0 yang sengaja diisi tetap valid (KPI 'min' bisa
            // memiliki capaian terbaik di angka 0).
            const mainKosong   = (realisasiStr === '' || isNaN(realisasi));
            const harianKosong = harianInput && (harianStr === '' || isNaN(realisasiHarian));

            if (mainKosong || harianKosong) {
                if (badge) { badge.style.display = 'none'; badge.textContent = ''; }
                if (pencapaianCell) pencapaianCell.textContent = '';
                if (kontribCell)    kontribCell.textContent = '';
                hitungUlangInduk(indukKpiId);
                return;
            }

            let params = new URLSearchParams();
            params.append(csrfTokenName, csrfHashValue);
            params.append('turunan_id', turunanId);
            params.append('pegawai_id', pegawaiId);
            params.append('realisasi', realisasi);
            if (harianInput) params.append('realisasi_harian', realisasiHarian);

            fetch("<?= site_url('penilaian/ajaxHitungTurunan') ?>", {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: params
            })
            .then(r => r.json())
            .then(data => {
                if (data.csrf_hash) {
                    csrfHashValue = data.csrf_hash;
                    updateCsrfHiddenInput(data.csrf_hash);
                }
                if (data.valid && badge) {
                    badge.textContent  = 'Skor: ' + parseFloat(data.skor).toFixed(2);
                    badge.className    = 'skor-turunan-badge badge text-bg-' + data.color;
                    badge.style.display = 'inline';
                    badge.setAttribute('data-skor-t',      data.skor);
                    badge.setAttribute('data-kontribusi-t', data.kontribusi_t);
                }
                if (data.valid && pencapaianCell) {
                    if (data.pencapaian_tak_terhingga) {
                        pencapaianCell.textContent = '∞';
                    } else if (data.pencapaian !== null) {
                        pencapaianCell.textContent = parseFloat(data.pencapaian).toFixed(2) + '%';
                    } else {
                        pencapaianCell.textContent = '';
                    }
                }
                if (data.valid && kontribCell) {
                    kontribCell.textContent = parseFloat(data.kontribusi_t).toFixed(2);
                }
                hitungUlangInduk(indukKpiId);
            })
            .catch(() => hitungUlangInduk(indukKpiId));
        }

        mainInput.addEventListener('input', hitungPreviewTurunan);
        mainInput.addEventListener('change', hitungPreviewTurunan);
        if (harianInput) {
            harianInput.addEventListener('input', hitungPreviewTurunan);
            harianInput.addEventListener('change', hitungPreviewTurunan);
        }
    });

    // Hitung ulang Skor & Kontribusi Induk dari semua badge Turunan
    // yang sudah terisi — Cara B: ΣKontribusiT / BobotInduk
    function hitungUlangInduk(indukKpiId) {
        const indukRow = document.querySelector('.kpi-row[data-kpi="' + indukKpiId + '"]');
        if (!indukRow) return;

        const outputSkor      = indukRow.querySelector('.skor-output');
        const outputNilai     = indukRow.querySelector('.nilai-output');
        const outputKontrib   = indukRow.querySelector('.kontribusi-output');
        const outputRealisasi = document.getElementById('realisasi_induk_' + indukKpiId);
        const bobotInduk      = parseFloat(
            document.querySelector('.realisasi-turunan-input[data-induk-kpi="' + indukKpiId + '"]')
                ?.getAttribute('data-bobot-induk') || 0
        );

        // Kumpulkan semua kontribusi Turunan yang sudah dihitung (badge,
        // tergantung selesainya AJAX), sekaligus SUM Realisasi Turunan untuk
        // field Realisasi Induk (readonly) — SUM Realisasi ini dihitung
        // langsung dari nilai yang sedang diketik, TIDAK menunggu AJAX
        // selesai, supaya terasa instan. Field ini sebelumnya tidak pernah
        // ter-update sama sekali walau labelnya menjanjikan "otomatis
        // terisi SUM ... real-time".
        let sumKontribusiT = 0;
        let sumRealisasiT  = 0;
        let adaKontribusi  = false;
        let adaRealisasi   = false;
        document.querySelectorAll(
            '.realisasi-turunan-input[data-induk-kpi="' + indukKpiId + '"]'
        ).forEach(function (inp) {
            const tid   = inp.getAttribute('data-turunan-id');
            const badge = document.getElementById('skorT_' + tid);
            if (badge && badge.getAttribute('data-kontribusi-t')) {
                sumKontribusiT += parseFloat(badge.getAttribute('data-kontribusi-t') || 0);
                adaKontribusi = true;
            }
            const rt = parseFloat(inp.value);
            if (inp.value !== '' && !isNaN(rt)) {
                sumRealisasiT += rt;
                adaRealisasi = true;
            }
        });

        if (outputRealisasi) outputRealisasi.value = adaRealisasi ? sumRealisasiT.toFixed(2) : '';

        if (!adaKontribusi || bobotInduk === 0) {
            if (outputSkor)    outputSkor.value    = '';
            if (outputNilai)   outputNilai.value   = '';
            if (outputKontrib) outputKontrib.value = '';
            return;
        }

        // Cara B: Skor_Induk = ΣKontribusiT / BobotInduk. Hanya di-cap ke
        // atas (maks 4) — TIDAK di-floor ke 1, karena Turunan ber-polarity
        // 'tertimbang' bisa sah menghasilkan Skor_T di bawah 1 (serendah
        // 0,85), jadi rata-rata tertimbangnya pun boleh di bawah 1.
        const skorInduk    = Math.min(4, sumKontribusiT / bobotInduk);
        const kontribInduk = skorInduk * bobotInduk;

        if (outputSkor)    outputSkor.value    = skorInduk.toFixed(2);
        if (outputNilai)   outputNilai.value    = skorInduk.toFixed(2); // Nilai = Skor (identik)
        if (outputKontrib) outputKontrib.value = kontribInduk.toFixed(2);
    }
});
</script>