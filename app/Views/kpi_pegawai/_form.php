<div class="d-flex align-items-center gap-2 mb-3">
  <a href="<?= base_url('kpi-pegawai') ?>"
     class="btn btn-sm btn-light border">
    <i class="ti ti-arrow-left"></i>
  </a>
  <div class="flex-grow-1">
    <h5 class="mb-0 fw-semibold" style="color:#1F4E79">
      KPI Per Pegawai — <?= esc($pegawai['nama']) ?>
    </h5>
    <small class="text-muted">
      <?= esc($pegawai['jabatan'] ?? '') ?>
    </small>
  </div>
</div>

<!-- Total bobot indicator -->
<?php $bobot_pct = round($totalBobot * 100, 2); ?>
<div class="alert py-2 mb-3 d-flex align-items-center gap-2"
     id="alert-bobot"
     style="background:<?= $bobot_pct==100?'#E2EFDA':'#FCE4D6' ?>;
            border:1px solid <?= $bobot_pct==100?'#70AD47':'#C00000' ?>">
  <i class="ti ti-calculator"></i>
  <span style="font-size:13px">
    Total bobot KPI pegawai ini:
    <strong id="total-bobot-display"><?= $bobot_pct ?>%</strong>
    <?= $bobot_pct==100 ? '— ✓ Sudah tepat 100%' : '— Harus tepat 100%!' ?>
  </span>
</div>

<div class="row g-3">

  <!-- KOLOM KIRI: KPI yang sudah di-assign -->
  <div class="col-md-8">
    <div class="card border-0 shadow-sm">
      <div class="card-header py-2 d-flex align-items-center
                  justify-content-between"
           style="background:#E6F1FB">
        <span class="fw-semibold" style="color:#1F4E79;font-size:13px">
          <i class="ti ti-list-check me-1"></i>
          KPI <?= esc($pegawai['nama']) ?>
          <span class="badge bg-primary ms-1">
            <?= count($assigned) ?>
          </span>
        </span>

        <!-- Tombol copy dari pegawai lain -->
        <button type="button"
                class="btn btn-outline-secondary btn-sm"
                style="font-size:11px"
                data-bs-toggle="modal"
                data-bs-target="#modalCopy">
          <i class="ti ti-copy me-1"></i> Copy dari pegawai lain
        </button>
      </div>

      <div class="card-body p-0">
        <?php if (empty($assigned)): ?>
          <div class="text-center py-4 text-muted" style="font-size:13px">
            <i class="ti ti-playlist-x fs-2 d-block mb-1"></i>
            Belum ada KPI. Pilih dari daftar kanan.
          </div>
        <?php else: ?>

        <form action="<?= base_url("kpi-pegawai/save-bobot/{$pegawai['id']}") ?>"
              method="post">
          <?= csrf_field() ?>

          <?php
          $persp_colors = [
              'Financial'        => ['#E6F1FB','#1F4E79'],
              'Customer'         => ['#EAF3DE','#375623'],
              'Internal Process' => ['#FFF3CD','#7F6000'],
              'Learning & Growth'=> ['#F3E5F5','#5C2A6B'],
          ];
          ?>

          <?php foreach ($assignedGrouped as $perspektif => $kpis): ?>
          <?php $pc = $persp_colors[$perspektif] ?? ['#f8f9fa','#333']; ?>
          <div class="px-3 py-1"
               style="background:<?= $pc[0] ?>;
                      border-left:3px solid <?= $pc[1] ?>">
            <small class="fw-semibold" style="color:<?= $pc[1] ?>">
              <?= esc($perspektif) ?>
            </small>
          </div>

          <?php foreach ($kpis as $kpi): ?>
          <?php
            $turunanList = $turunanByInduk[$kpi['id']] ?? [];
            $punyaTurunan = !empty($turunanList);
            $totalBobotTurunan = array_sum(array_column($turunanList, 'bobot'));
            $sisaBobotTurunan  = max(0, round((float)$kpi['bobot'] - $totalBobotTurunan, 4));
          ?>
          <div class="border-bottom" data-induk-id="<?= $kpi['id'] ?>"
               data-bobot-induk="<?= (float)$kpi['bobot'] ?>">
            <div class="px-3 py-2">
              <input type="hidden" name="kp_id[]"  value="<?= $kpi['id'] ?>">

              <!-- Baris 1: nama, kode, satuan, polarity, badge Turunan -->
              <div class="mb-2">
                <div class="fw-semibold" style="font-size:13px">
                  <?= esc($kpi['nama_kpi']) ?>
                </div>
                <div style="font-size:11px;color:#888">
                  <code><?= esc($kpi['kode']) ?></code>
                  &nbsp;·&nbsp; <?= esc($kpi['satuan']) ?>
                  &nbsp;·&nbsp;
                  <span style="color:<?= $kpi['polarity']==='max'
                      ? '#375623' : '#C00000' ?>">
                    <?= $kpi['polarity']==='max' ? '↑ Max' : '↓ Min' ?>
                    (<?= $kpi['perubahan_polarity']==='pos'
                        ? 'Positif' : 'Negatif' ?>)
                  </span>
                  <?php if ($punyaTurunan): ?>
                    &nbsp;·&nbsp;
                    <span class="badge bg-light text-dark border" style="font-size:10px">
                      <?= count($turunanList) ?> Parameter Turunan
                    </span>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Baris 2: kontrol Target, Bobot, dan tombol aksi —
                   dipisah ke baris sendiri (bukan satu baris flex padat
                   dengan nama KPI) agar tidak berdesakan/terpotong pada
                   lebar kolom yang tersedia. -->
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <!-- Input Target — pagu/plafon yang ditentukan manual oleh
                     Admin. Begitu memiliki Parameter Turunan, field ini
                     terkunci (readonly) agar tidak diubah sembarangan tanpa
                     menyesuaikan Turunannya — perilakunya sama persis
                     dengan field Bobot di sebelahnya. -->
                <div style="width:130px">
                  <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light text-muted" style="font-size:10px; padding: 2px 5px;">Trg</span>
                    <input type="number"
                           name="target[]"
                           class="form-control text-center"
                           value="<?= esc($kpi['target'] ?? '100.00') ?>"
                           step="any" min="0"
                           placeholder="100" required
                           <?= $punyaTurunan ? 'readonly style="background:#f8f9fa;cursor:not-allowed"' : '' ?>
                           title="<?= $punyaTurunan ? 'Tidak dapat diubah karena sudah memiliki Parameter Turunan' : '' ?>">
                  </div>
                </div>

                <!-- Input bobot — readonly apabila sudah memiliki Turunan,
                     karena Bobot Turunan adalah pecahan dari Bobot Induk ini -->
                <div style="width:130px">
                  <div class="input-group input-group-sm">
                    <input type="number"
                           name="bobot[]"
                           class="form-control bobot-input text-center"
                           value="<?= $kpi['bobot'] ?>"
                           step="0.001" min="0" max="1"
                           placeholder="0.10" required
                           <?= $punyaTurunan ? 'readonly style="background:#f8f9fa;cursor:not-allowed"' : '' ?>
                           title="<?= $punyaTurunan ? 'Tidak dapat diubah karena sudah memiliki Parameter Turunan' : '' ?>">
                    <span class="input-group-text b-input-pct" style="font-size:11px; padding: 2px 6px;">
                      <?= round($kpi['bobot']*100, 1) ?>%
                    </span>
                  </div>
                </div>

                <!-- Tambah Parameter Turunan -->
                <?php if ((float)$kpi['bobot'] <= 0): ?>
                <button type="button"
                        class="btn btn-outline-secondary btn-sm"
                        style="padding:3px 10px;font-size:11px;white-space:nowrap;opacity:0.6"
                        disabled
                        title="Isi dan simpan Bobot KPI Induk terlebih dahulu sebelum menambah Parameter Turunan">
                  <i class="ti ti-list-tree" style="font-size:13px"></i> Tambah Parameter
                  <i class="ti ti-alert-circle text-warning ms-1" style="font-size:11px"></i>
                </button>
                <?php else: ?>
                <button type="button"
                        class="btn btn-outline-primary btn-sm"
                        style="padding:3px 10px;font-size:11px;white-space:nowrap"
                        data-bs-toggle="modal"
                        data-bs-target="#modalTambahTurunan<?= $kpi['id'] ?>">
                  <i class="ti ti-list-tree" style="font-size:13px"></i> Tambah Parameter
                </button>
                <?php endif; ?>

                <!-- Hapus -->
                <a href="<?= base_url("kpi-pegawai/delete/{$kpi['id']}") ?>"
                   class="btn btn-outline-danger btn-sm ms-auto"
                   style="padding:3px 9px"
                   onclick="return confirmAction(event, { title: 'Hapus KPI', text: 'Hapus KPI ini beserta seluruh Parameter Turunannya?', confirmText: 'Ya, Hapus', danger: true })">
                  <i class="ti ti-trash" style="font-size:13px"></i>
                </a>
              </div>

              <!-- Deskripsi Target — panduan pengisian Realisasi untuk Drafter/Approver.
                   Input ini menggunakan array index yang sama dengan kp_id[], bobot[],
                   dan target[] sehingga saveBobot() bisa memetakannya per-KPI dengan benar. -->
              <div class="pb-2 mt-1">
                <div class="input-group input-group-sm">
                  <span class="input-group-text bg-light text-muted"
                        style="font-size:10px;white-space:nowrap;padding:2px 6px">
                    <i class="ti ti-info-circle me-1"></i>Deskripsi Target
                  </span>
                  <input type="text"
                         name="deskripsi_target[]"
                         class="form-control"
                         value="<?= esc($kpi['deskripsi_target'] ?? '') ?>"
                         placeholder="Contoh: Turnover ≤ 10 orang, atau Minimal 2x pelatihan setahun"
                         style="font-size:12px">
                </div>
              </div>
            </div>

            <?php if ($punyaTurunan): ?>
            <!-- Sub-baris: daftar Parameter Turunan milik KPI Induk ini -->
            <div style="background:#FAFBFC;border-left:3px solid #BDD7EE;
                        margin:0 16px 10px 16px;padding:8px 14px;border-radius:0 6px 6px 0">
              <table class="table table-sm mb-0" style="font-size:12px">
                <thead>
                  <tr style="color:#888">
                    <th style="font-weight:500;border-bottom:1px solid #e5e7eb">Parameter Turunan</th>
                    <th style="font-weight:500;width:110px;border-bottom:1px solid #e5e7eb" class="text-center">Target</th>
                    <th style="font-weight:500;width:110px;border-bottom:1px solid #e5e7eb" class="text-center">Bobot</th>
                    <th style="width:70px;border-bottom:1px solid #e5e7eb"></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($turunanList as $t): ?>
                  <tr>
                    <td style="border-bottom:1px solid #f0f0f0"><i class="ti ti-corner-down-right me-1" style="color:#aaa"></i><?= esc($t['nama_turunan']) ?></td>
                    <td class="text-center" style="border-bottom:1px solid #f0f0f0"><?= number_format((float)$t['target'], 2) ?></td>
                    <td class="text-center" style="border-bottom:1px solid #f0f0f0">
                      <?= round((float)$t['bobot']*100, 2) ?>%
                    </td>
                    <td class="text-center" style="border-bottom:1px solid #f0f0f0">
                      <div class="d-flex gap-1 justify-content-center">
                        <button type="button"
                                class="btn btn-outline-secondary btn-sm"
                                style="padding:1px 6px"
                                data-bs-toggle="modal"
                                data-bs-target="#modalEditTurunan<?= $t['id'] ?>">
                          <i class="ti ti-edit" style="font-size:11px"></i>
                        </button>
                        <a href="<?= base_url("kpi-pegawai/turunan/delete/{$t['id']}") ?>"
                           class="btn btn-outline-danger btn-sm"
                           style="padding:1px 6px"
                           onclick="return confirmAction(event, { title: 'Hapus Parameter Turunan', text: 'Hapus parameter turunan ini?', confirmText: 'Ya, Hapus', danger: true })">
                          <i class="ti ti-trash" style="font-size:11px"></i>
                        </a>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                  <tr style="background:#F0F4F8">
                    <td class="fw-semibold" style="border:none">Total Turunan</td>
                    <td class="text-center fw-semibold" style="border:none">
                      <?= number_format(array_sum(array_column($turunanList,'target')), 2) ?>
                      <?php $sisaTargetTurunan = max(0, round((float)$kpi['target'] - array_sum(array_column($turunanList,'target')), 2)); ?>
                      <?php if ($sisaTargetTurunan > 0): ?>
                        <span class="text-warning" style="font-size:10px">
                          (sisa <?= number_format($sisaTargetTurunan, 2) ?>)
                        </span>
                      <?php endif; ?>
                    </td>
                    <td class="text-center fw-semibold" style="border:none">
                      <?= round($totalBobotTurunan * 100, 2) ?>%
                      <?php if ($sisaBobotTurunan > 0): ?>
                        <span class="text-warning" style="font-size:10px">
                          (sisa <?= round($sisaBobotTurunan * 100, 2) ?>%)
                        </span>
                      <?php endif; ?>
                    </td>
                    <td style="border:none"></td>
                  </tr>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
          <?php endforeach; ?>

          <div class="p-3">
            <button type="submit" class="btn btn-primary btn-sm px-4">
              <i class="ti ti-device-floppy me-1"></i>
              Simpan Konfigurasi KPI
            </button>
          </div>
        </form>

        <!-- Modal Tambah Parameter Turunan — ditempatkan DI LUAR form
             "save-bobot" di atas (yang ditutup tepat sebelum baris ini)
             agar tidak terjadi <form> bersarang di dalam <form>, sesuai
             pola yang sudah diperbaiki sebelumnya pada draft_ulang/_content.php. -->
        <?php foreach ($assignedGrouped as $perspektif => $kpis): ?>
        <?php foreach ($kpis as $kpi): ?>
        <?php
          $turunanListModal = $turunanByInduk[$kpi['id']] ?? [];
          $totalBobotTurunanModal = array_sum(array_column($turunanListModal, 'bobot'));
          $sisaBobotTurunanModal  = max(0, round((float)$kpi['bobot'] - $totalBobotTurunanModal, 4));
        ?>
        <div class="modal fade" id="modalTambahTurunan<?= $kpi['id'] ?>" tabindex="-1">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h6 class="modal-title fw-semibold">
                  <i class="ti ti-list-tree me-1"></i>
                  Tambah Parameter Turunan — <?= esc($kpi['nama_kpi']) ?>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <form action="<?= base_url("kpi-pegawai/turunan/add/{$kpi['id']}") ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-body">
                  <div class="alert py-2 mb-3" style="font-size:12px;background:#E6F1FB;border:1px solid #2E75B6;color:#1F4E79">
                    Bobot Parameter Induk: <strong><?= round((float)$kpi['bobot']*100, 2) ?>%</strong>.
                    Sisa bobot tersedia:
                    <strong id="sisaBobotInfo<?= $kpi['id'] ?>"><?= round($sisaBobotTurunanModal * 100, 2) ?>%</strong>.
                    <br><span style="font-size:11px">Target setiap Turunan bersifat independen — bebas diisi sesuai satuan masing-masing.</span>
                  </div>
                  <div class="mb-2">
                    <label class="form-label fw-semibold small">
                      Nama Parameter Turunan <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="nama_turunan" class="form-control form-control-sm"
                           placeholder="Misal: Penjualan Cabang A" required>
                  </div>
                  <div class="mb-2">
                    <label class="form-label fw-semibold small">Deskripsi Target</label>
                    <input type="text" name="deskripsi_target" class="form-control form-control-sm"
                           placeholder="Contoh: Penjualan Cabang A minimal 500 nasabah">
                    <div class="form-text" style="font-size:10px">Panduan pengisian Realisasi untuk Drafter</div>
                  </div>
                  <div class="row g-2 mb-2">
                    <div class="col-6">
                      <label class="form-label fw-semibold small">
                        Target <span class="text-danger">*</span>
                      </label>
                      <input type="number" name="target" class="form-control form-control-sm"
                             step="any" min="0" placeholder="100" required>
                      <div class="form-text" style="font-size:10px">Bebas — sesuai satuan Turunan ini</div>
                    </div>
                    <div class="col-6">
                      <label class="form-label fw-semibold small">
                        Satuan
                      </label>
                      <input type="text" name="satuan" class="form-control form-control-sm"
                             placeholder="%, Rp Juta, Skor, Jumlah ...">
                    </div>
                  </div>
                  <div class="row g-2 mb-2">
                    <div class="col-6">
                      <label class="form-label fw-semibold small">
                        Polarity <span class="text-danger">*</span>
                      </label>
                      <select name="polarity" class="form-select form-select-sm">
                        <option value="max">↑ Max (lebih besar lebih baik)</option>
                        <option value="min">↓ Min (lebih kecil lebih baik)</option>
                      </select>
                    </div>
                    <div class="col-6">
                      <label class="form-label fw-semibold small">
                        Perubahan Polarity <span class="text-danger">*</span>
                      </label>
                      <select name="perubahan_polarity" class="form-select form-select-sm">
                        <option value="pos">Positif</option>
                        <option value="neg">Negatif</option>
                      </select>
                    </div>
                  </div>
                  <div class="row g-2">
                    <div class="col-6">
                      <label class="form-label fw-semibold small">
                        Bobot (desimal) <span class="text-danger">*</span>
                      </label>
                      <input type="number" name="bobot" class="form-control form-control-sm bobot-turunan-input"
                             data-sisa-pagu="<?= $sisaBobotTurunanModal ?>"
                             step="0.001" min="0" max="<?= $sisaBobotTurunanModal ?>"
                             placeholder="0.10" required>
                      <div class="form-text peringatan-pagu" style="font-size:10px">
                        Maksimal <?= round($sisaBobotTurunanModal, 4) ?> (<?= round($sisaBobotTurunanModal*100, 2) ?>%)
                      </div>
                    </div>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                  <button type="submit" class="btn btn-primary btn-sm">
                    <i class="ti ti-plus me-1"></i> Tambah Parameter
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- Modal Edit untuk setiap Parameter Turunan yang sudah ada -->
        <?php foreach ($turunanListModal as $t): ?>
        <?php
          $totalBobotLainModal  = $totalBobotTurunanModal - (float)$t['bobot'];
          $sisaBobotEditModal   = max(0, round((float)$kpi['bobot'] - $totalBobotLainModal, 4));
        ?>
        <div class="modal fade" id="modalEditTurunan<?= $t['id'] ?>" tabindex="-1">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h6 class="modal-title fw-semibold">
                  <i class="ti ti-edit me-1"></i>
                  Edit Parameter Turunan — <?= esc($kpi['nama_kpi']) ?>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <form action="<?= base_url("kpi-pegawai/turunan/update/{$t['id']}") ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-body">
                  <div class="alert py-2 mb-3" style="font-size:12px;background:#E6F1FB;border:1px solid #2E75B6;color:#1F4E79">
                    Bobot maksimal: <strong><?= round($sisaBobotEditModal * 100, 2) ?>%</strong>.
                    <br><span style="font-size:11px">Target bersifat independen — bebas diisi sesuai satuan Turunan ini.</span>
                  </div>
                  <div class="mb-2">
                    <label class="form-label fw-semibold small">
                      Nama Parameter Turunan <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="nama_turunan" class="form-control form-control-sm"
                           value="<?= esc($t['nama_turunan']) ?>" required>
                  </div>
                  <div class="mb-2">
                    <label class="form-label fw-semibold small">Deskripsi Target</label>
                    <input type="text" name="deskripsi_target" class="form-control form-control-sm"
                           value="<?= esc($t['deskripsi_target'] ?? '') ?>"
                           placeholder="Contoh: Penjualan Cabang A minimal 500 nasabah">
                    <div class="form-text" style="font-size:10px">Panduan pengisian Realisasi untuk Drafter</div>
                  </div>
                  <div class="row g-2 mb-2">
                    <div class="col-6">
                      <label class="form-label fw-semibold small">
                        Target <span class="text-danger">*</span>
                      </label>
                      <input type="number" name="target" class="form-control form-control-sm"
                             value="<?= esc($t['target']) ?>"
                             step="any" min="0" required>
                      <div class="form-text" style="font-size:10px">Bebas — sesuai satuan Turunan ini</div>
                    </div>
                    <div class="col-6">
                      <label class="form-label fw-semibold small">Satuan</label>
                      <input type="text" name="satuan" class="form-control form-control-sm"
                             value="<?= esc($t['satuan'] ?? '') ?>"
                             placeholder="%, Rp Juta, Skor ...">
                    </div>
                  </div>
                  <div class="row g-2 mb-2">
                    <div class="col-6">
                      <label class="form-label fw-semibold small">
                        Polarity <span class="text-danger">*</span>
                      </label>
                      <select name="polarity" class="form-select form-select-sm">
                        <option value="max" <?= ($t['polarity'] ?? 'max') === 'max' ? 'selected' : '' ?>>↑ Max (lebih besar lebih baik)</option>
                        <option value="min" <?= ($t['polarity'] ?? 'max') === 'min' ? 'selected' : '' ?>>↓ Min (lebih kecil lebih baik)</option>
                      </select>
                    </div>
                    <div class="col-6">
                      <label class="form-label fw-semibold small">
                        Perubahan Polarity <span class="text-danger">*</span>
                      </label>
                      <select name="perubahan_polarity" class="form-select form-select-sm">
                        <option value="pos" <?= ($t['perubahan_polarity'] ?? 'pos') === 'pos' ? 'selected' : '' ?>>Positif</option>
                        <option value="neg" <?= ($t['perubahan_polarity'] ?? 'pos') === 'neg' ? 'selected' : '' ?>>Negatif</option>
                      </select>
                    </div>
                  </div>
                  <div class="row g-2">
                    <div class="col-6">
                      <label class="form-label fw-semibold small">
                        Bobot (desimal) <span class="text-danger">*</span>
                      </label>
                      <input type="number" name="bobot" class="form-control form-control-sm bobot-turunan-input"
                             data-sisa-pagu="<?= $sisaBobotEditModal ?>"
                             value="<?= esc($t['bobot']) ?>"
                             step="0.001" min="0" max="<?= $sisaBobotEditModal ?>" required>
                      <div class="form-text peringatan-pagu" style="font-size:10px">
                        Maksimal <?= round($sisaBobotEditModal, 4) ?> (<?= round($sisaBobotEditModal*100, 2) ?>%)
                      </div>
                    </div>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                  <button type="submit" class="btn btn-primary btn-sm">
                    <i class="ti ti-device-floppy me-1"></i> Simpan Perubahan
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php endforeach; ?>
        <?php endforeach; ?>

        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- KOLOM KANAN: Pool KPI dari Unit Kerja -->
  <div class="col-md-4">
    <div class="card border-0 shadow-sm">
      <div class="card-header py-2" style="background:#EAF3DE">
        <span class="fw-semibold" style="color:#375623;font-size:13px">
          <i class="ti ti-plus me-1"></i>
          Tambah dari KPI Unit Kerja
        </span>
      </div>
      <div class="card-body p-0">
        <div class="p-2 border-bottom">
          <input type="text" id="search-kpi"
                 class="form-control form-control-sm"
                 placeholder="Cari nama KPI atau kode...">
        </div>
        <div style="max-height:480px;overflow-y:auto">
          <?php
          $persp_colors2 = [
              'Financial'        => ['#E6F1FB','#1F4E79'],
              'Customer'         => ['#EAF3DE','#375623'],
              'Internal Process' => ['#FFF3CD','#7F6000'],
              'Learning & Growth'=> ['#F3E5F5','#5C2A6B'],
          ];
          ?>
          <?php foreach ($poolGrouped as $perspektif => $kpis): ?>
          <?php $pc2 = $persp_colors2[$perspektif] ?? ['#f8f9fa','#333']; ?>
          <div class="kpi-pool-group">
          <div class="px-3 py-1"
               style="background:<?= $pc2[0] ?>;
                      border-left:3px solid <?= $pc2[1] ?>">
            <small class="fw-semibold" style="color:<?= $pc2[1] ?>">
              <?= esc($perspektif) ?>
            </small>
          </div>
          <?php foreach ($kpis as $kpi): ?>
          <?php $isAssigned = in_array($kpi['kpi_id'], $assignedIds); ?>
          <div class="d-flex align-items-center gap-2 px-3 py-2
                      border-bottom kpi-pool-item"
               data-search="<?= esc(strtolower($kpi['nama_kpi'] . ' ' . $kpi['kode'] . ' ' . $kpi['satuan'])) ?>">
            <div class="flex-grow-1">
              <div style="font-size:13px;
                <?= $isAssigned
                    ? 'color:#aaa;text-decoration:line-through' : '' ?>">
                <?= esc($kpi['nama_kpi']) ?>
              </div>
              <div style="font-size:11px;color:#888">
                <code><?= esc($kpi['kode']) ?></code>
                &nbsp;·&nbsp; <?= esc($kpi['satuan']) ?>
              </div>
            </div>
            <?php if ($isAssigned): ?>
              <span class="badge"
                    style="background:#C6EFCE;color:#375623;font-size:10px">
                ✓ Sudah
              </span>
            <?php else: ?>
              <form action="<?= base_url("kpi-pegawai/add/{$pegawai['id']}") ?>"
                    method="post" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="kpi_id"
                       value="<?= $kpi['kpi_id'] ?>">
                <input type="hidden" name="urutan"
                       value="<?= $kpi['urutan'] ?>">
                <button type="submit"
                        class="btn btn-outline-success btn-sm"
                        style="padding:2px 8px;font-size:11px">
                  <i class="ti ti-plus"></i> Tambah
                </button>
              </form>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Copy dari Pegawai Lain -->
<?php $grouped = $grouped ?? []; ?>
<div class="modal fade" id="modalCopy" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title fw-semibold">
          <i class="ti ti-copy me-1"></i> Copy KPI dari Pegawai Lain
        </h6>
        <button type="button" class="btn-close"
                data-bs-dismiss="modal"></button>
      </div>
      <form action="<?= base_url("kpi-pegawai/copy/{$pegawai['id']}") ?>"
            method="post">
        <?= csrf_field() ?>
        <div class="modal-body">
          <div class="alert alert-warning py-2 mb-3"
               style="font-size:12px">
            <i class="ti ti-alert-triangle me-1"></i>
            Fitur ini akan <strong>mengganti semua KPI</strong>
            pegawai ini dengan KPI dari pegawai yang dipilih.
          </div>
          <label class="form-label fw-semibold small">
            Pilih Pegawai Sumber
          </label>
          <select name="source_pegawai_id"
                  class="form-select form-select-sm" required>
            <option value="">-- Pilih pegawai --</option>
            <?php
            $allPegawaiFlat = [];
            foreach ($grouped as $divNama => $listPegawai) {
                foreach ($listPegawai as $p) {
                    if ($p['id'] != $pegawai['id']) {
                        $allPegawaiFlat[] = array_merge(
                            $p, ['nama_divisi_label' => $divNama]
                        );
                    }
                }
            }
            usort($allPegawaiFlat,
                fn($a,$b) => strcmp(
                    $a['nama_divisi_label'],
                    $b['nama_divisi_label']
                )
            );

            $currentDiv = '';
            foreach ($allPegawaiFlat as $p):
                if ($currentDiv !== $p['nama_divisi_label']):
                    if ($currentDiv !== '') echo '</optgroup>';
                    echo '<optgroup label="' . esc($p['nama_divisi_label']) . '">';
                    $currentDiv = $p['nama_divisi_label'];
                endif;
            ?>
            <option value="<?= $p['id'] ?>">
              <?= esc($p['nama']) ?>
              <?php if (!empty($p['jabatan'])): ?>
                (<?= esc($p['jabatan']) ?>)
              <?php endif; ?>
            </option>
            <?php endforeach; ?>
            <?php if ($currentDiv !== ''): ?>
              </optgroup>
            <?php endif; ?>
          </select>
          <div class="form-text mt-1" style="font-size:11px">
            Semua pegawai yang sudah memiliki KPI tersedia sebagai sumber.
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light btn-sm"
                  data-bs-dismiss="modal">Batal</button>
          <button type="submit"
                  class="btn btn-primary btn-sm"
                  onclick="return confirmAction(event, { title: 'Salin KPI', text: 'Yakin? KPI yang sudah ada akan diganti dengan salinan baru.', confirmText: 'Ya, Salin', danger: true })">
            <i class="ti ti-copy me-1"></i> Copy KPI
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Search KPI (mencari berdasarkan nama, kode, dan satuan)
document.getElementById('search-kpi').addEventListener('input', function() {
    const q = this.value.toLowerCase().trim();
    document.querySelectorAll('.kpi-pool-group').forEach(group => {
        let anyVisible = false;
        group.querySelectorAll('.kpi-pool-item').forEach(el => {
            const match = !q || el.dataset.search.includes(q);
            el.style.display = match ? '' : 'none';
            if (match) anyVisible = true;
        });
        group.style.display = anyVisible ? '' : 'none';
    });
});

// Hitung total bobot real-time & update badge persentase per baris
document.querySelectorAll('.bobot-input').forEach(input => {
    input.addEventListener('input', function() {
        const val = parseFloat(this.value) || 0;
        const pctSpan = this.closest('.input-group').querySelector('.b-input-pct');
        if (pctSpan) {
            pctSpan.textContent = (val * 100).toFixed(1) + '%';
        }
        hitungTotalBobot();
    });
});

function hitungTotalBobot() {
    let total = 0;
    document.querySelectorAll('.bobot-input').forEach(i => {
        total += parseFloat(i.value) || 0;
    });
    const pct = Math.round(total * 10000) / 100;
    document.getElementById('total-bobot-display').textContent = pct + '%';
    const alert = document.getElementById('alert-bobot');
    if (pct === 100) {
        alert.style.background   = '#E2EFDA';
        alert.style.borderColor  = '#70AD47';
    } else {
        alert.style.background   = '#FCE4D6';
        alert.style.borderColor  = '#C00000';
    }
}

// Validasi real-time pada input Target dan Bobot di setiap modal Tambah
// Parameter maupun Edit Parameter — memberi peringatan visual langsung
// apabila nilai yang diketik melebihi sisa pagu yang tersedia (atribut
// max sudah diisi sesuai sisa pagu dari sisi server; validasi di sini
// murni untuk umpan balik visual sebelum pengguna menekan tombol Simpan).
document.querySelectorAll('.target-turunan-input, .bobot-turunan-input').forEach(function (input) {
    const peringatanEl = input.closest('.col-6')?.querySelector('.peringatan-pagu');
    const teksAsli      = peringatanEl ? peringatanEl.textContent : '';

    input.addEventListener('input', function () {
        const maxVal = parseFloat(this.getAttribute('data-sisa-pagu')) || 0;
        const val    = parseFloat(this.value) || 0;

        if (val > maxVal) {
            this.classList.add('is-invalid');
            if (peringatanEl) {
                peringatanEl.textContent = 'Melebihi sisa pagu Parameter Induk!';
                peringatanEl.classList.add('text-danger');
                peringatanEl.classList.remove('text-muted');
            }
        } else {
            this.classList.remove('is-invalid');
            if (peringatanEl) {
                peringatanEl.textContent = teksAsli;
                peringatanEl.classList.remove('text-danger');
            }
        }
    });
});
</script>