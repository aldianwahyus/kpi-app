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
  <a href="<?= base_url("master-target/edit/{$pegawai['id']}") ?>" class="btn btn-sm btn-outline-primary">
    <i class="ti ti-target-arrow me-1"></i> Master Target
  </a>
</div>

<div class="alert py-2 mb-3 d-flex align-items-center gap-2" style="background:#E6F1FB;border:1px solid #2E75B6;font-size:12px">
  <i class="ti ti-info-circle" style="color:#1F4E79"></i>
  <span style="color:#1F4E79">
    Layar ini hanya untuk memilih parameter KPI pegawai. Bobot & Target diisi di menu
    <strong>"Master Target"</strong> di atas.
  </span>
</div>

<?php if ($periodeAktif): ?>
<div class="alert py-2 mb-3 d-flex align-items-center gap-2" style="background:#EAF3DE;border:1px solid #70AD47;font-size:12px">
  <i class="ti ti-calendar-check" style="color:#375623"></i>
  <span style="color:#375623">
    Periode Aktif: <strong><?= esc($periodeAktif['nama']) ?></strong> — kolom Bobot Penilaian &
    Target di bawah menampilkan nilai efektif (read-only) untuk periode ini.
  </span>
</div>
<?php endif; ?>

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

        <form action="<?= base_url("kpi-pegawai/save-deskripsi/{$pegawai['id']}") ?>"
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
            $previewInduk = $previewIndukById[$kpi['id']] ?? null;
          ?>
          <div class="border-bottom" data-induk-id="<?= $kpi['id'] ?>">
            <div class="px-3 py-2">
              <input type="hidden" name="kp_id[]"  value="<?= $kpi['id'] ?>">

              <!-- Baris 1: nama, kode, satuan, polarity, badge Turunan -->
              <div class="mb-2">
                <div class="fw-semibold" style="font-size:13px">
                  <?= esc($kpi['nama_kpi']) ?>
                </div>
                <?php
                  $polarityLabels = [
                      'max'        => ['↑ Max', '#375623'],
                      'min'        => ['↓ Min', '#C00000'],
                      'precise'    => ['◎ Precise is Better', '#1F4E79'],
                      'special'    => ['⚑ Special Scoring', '#7F6000'],
                      'tertimbang' => ['⚖ Scoring Tertimbang', '#5C2A6B'],
                  ];
                  [$polarityLabel, $polarityColor] = $polarityLabels[$kpi['polarity']] ?? ['—', '#888'];
                ?>
                <div style="font-size:11px;color:#888">
                  <code><?= esc($kpi['kode']) ?></code>
                  &nbsp;·&nbsp; <?= esc($kpi['satuan']) ?>
                  &nbsp;·&nbsp;
                  <span style="color:<?= $polarityColor ?>">
                    <?= $polarityLabel ?>
                    <?php if (in_array($kpi['polarity'], ['max', 'min'], true)): ?>
                      (<?= $kpi['perubahan_polarity']==='pos' ? 'Positif' : 'Negatif' ?>)
                    <?php endif; ?>
                  </span>
                  <?php if ($punyaTurunan): ?>
                    &nbsp;·&nbsp;
                    <span class="badge bg-light text-dark border" style="font-size:10px">
                      <?= count($turunanList) ?> Parameter Turunan
                    </span>
                  <?php endif; ?>
                </div>
                <?php if ($periodeAktif && !$punyaTurunan): ?>
                <div class="mt-1" style="font-size:11px">
                  <span class="badge bg-light text-dark border">
                    Bobot Penilaian: <?= $previewInduk && $previewInduk['bobot'] !== null ? round($previewInduk['bobot'] * 100, 2) . '%' : 'belum di-setup' ?>
                  </span>
                  <span class="badge bg-light text-dark border">
                    Target: <?= $previewInduk && ($previewInduk['target'] !== null || $kpi['polarity'] === 'special') ? ($kpi['polarity'] === 'special' ? '—' : number_format($previewInduk['target'], 2)) : 'belum di-setup' ?>
                  </span>
                </div>
                <?php endif; ?>
              </div>

              <!-- Baris 2: tombol aksi -->
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <button type="button"
                        class="btn btn-outline-primary btn-sm"
                        style="padding:3px 10px;font-size:11px;white-space:nowrap"
                        data-bs-toggle="modal"
                        data-bs-target="#modalTambahTurunan<?= $kpi['id'] ?>">
                  <i class="ti ti-list-tree" style="font-size:13px"></i> Tambah Parameter
                </button>

                <!-- Hapus -->
                <a href="<?= base_url("kpi-pegawai/delete/{$kpi['id']}") ?>"
                   class="btn btn-outline-danger btn-sm ms-auto"
                   style="padding:3px 9px"
                   onclick="return confirmAction(event, { title: 'Hapus KPI', text: 'Hapus KPI ini beserta seluruh Parameter Turunannya?', confirmText: 'Ya, Hapus', danger: true })">
                  <i class="ti ti-trash" style="font-size:13px"></i>
                </a>
              </div>

              <!-- Deskripsi Target — panduan pengisian Realisasi untuk Drafter/Approver. -->
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
                    <?php if ($periodeAktif): ?>
                    <th style="font-weight:500;width:110px;border-bottom:1px solid #e5e7eb" class="text-center">Target</th>
                    <th style="font-weight:500;width:110px;border-bottom:1px solid #e5e7eb" class="text-center">Bobot</th>
                    <?php endif; ?>
                    <th style="width:70px;border-bottom:1px solid #e5e7eb"></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($turunanList as $t): ?>
                  <?php $previewT = $previewTurunanById[$t['id']] ?? null; ?>
                  <tr>
                    <td style="border-bottom:1px solid #f0f0f0"><i class="ti ti-corner-down-right me-1" style="color:#aaa"></i><?= esc($t['nama_turunan']) ?></td>
                    <?php if ($periodeAktif): ?>
                    <td class="text-center" style="border-bottom:1px solid #f0f0f0">
                      <?= ($t['polarity'] ?? 'max') === 'special' ? '—' : ($previewT && $previewT['target'] !== null ? number_format((float)$previewT['target'], 2) : '—') ?>
                    </td>
                    <td class="text-center" style="border-bottom:1px solid #f0f0f0">
                      <?= $previewT && $previewT['bobot'] !== null ? round((float)$previewT['bobot']*100, 2) . '%' : '—' ?>
                    </td>
                    <?php endif; ?>
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
              Simpan Deskripsi Target
            </button>
          </div>
        </form>

        <!-- Modal Tambah Parameter Turunan — ditempatkan DI LUAR form
             "save-deskripsi" di atas (yang ditutup tepat sebelum baris ini)
             agar tidak terjadi <form> bersarang di dalam <form>, sesuai
             pola yang sudah diperbaiki sebelumnya pada draft_ulang/_content.php. -->
        <?php foreach ($assignedGrouped as $perspektif => $kpis): ?>
        <?php foreach ($kpis as $kpi): ?>
        <?php $turunanListModal = $turunanByInduk[$kpi['id']] ?? []; ?>
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
                  <div class="alert py-2 mb-3" style="font-size:11px;background:#E6F1FB;border:1px solid #2E75B6;color:#1F4E79">
                    Bobot & Target Parameter Turunan diisi di menu "Master Target" setelah Parameter ini dibuat.
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
                    <div class="col-12">
                      <label class="form-label fw-semibold small">
                        Satuan
                      </label>
                      <input type="text" name="satuan" class="form-control form-control-sm"
                             placeholder="%, Rp Juta, Skor, Jumlah ...">
                    </div>
                  </div>
                  <div class="row g-2 mb-2">
                    <div class="col-12">
                      <label class="form-label fw-semibold small">
                        Polarity <span class="text-danger">*</span>
                      </label>
                      <select name="polarity" class="form-select form-select-sm sel-polarity-turunan">
                        <option value="max">↑ Max (lebih besar lebih baik)</option>
                        <option value="min">↓ Min (lebih kecil lebih baik)</option>
                        <option value="precise">◎ Precise is Better</option>
                        <option value="special">⚑ Special Scoring</option>
                        <option value="tertimbang">⚖ Scoring Tertimbang</option>
                      </select>
                    </div>
                  </div>
                  <div class="row g-2 mb-2 polarity-field" data-for="max,min">
                    <div class="col-12">
                      <label class="form-label fw-semibold small">
                        Perubahan Polarity <span class="text-danger">*</span>
                      </label>
                      <select name="perubahan_polarity" class="form-select form-select-sm">
                        <option value="pos">Positif</option>
                        <option value="neg">Negatif</option>
                      </select>
                    </div>
                  </div>
                  <div class="mb-2 polarity-field" data-for="precise">
                    <div class="alert py-2 mb-2" style="font-size:10px;background:#E6F1FB;border:1px solid #2E75B6;color:#1F4E79">
                      Toleransi deviasi (%) dari target, simetris. Skor 1 otomatis di luar Toleransi Skor 2.
                    </div>
                    <div class="row g-2">
                      <div class="col-4">
                        <label class="form-label fw-semibold small">Skor 4 (±%)</label>
                        <input type="number" name="toleransi_skor4" class="form-control form-control-sm" step="any" min="0" placeholder="2.5">
                      </div>
                      <div class="col-4">
                        <label class="form-label fw-semibold small">Skor 3 (±%)</label>
                        <input type="number" name="toleransi_skor3" class="form-control form-control-sm" step="any" min="0" placeholder="7.5">
                      </div>
                      <div class="col-4">
                        <label class="form-label fw-semibold small">Skor 2 (±%)</label>
                        <input type="number" name="toleransi_skor2" class="form-control form-control-sm" step="any" min="0" placeholder="12.5">
                      </div>
                    </div>
                  </div>
                  <div class="row g-2 mb-2 polarity-field" data-for="special">
                    <div class="col-12">
                      <label class="form-label fw-semibold small">Sifat <span class="text-danger">*</span></label>
                      <select name="sifat_khusus" class="form-select form-select-sm">
                        <option value="maximize">Maximize — (Contoh: Jika Ada/Terealisasi = Skor 4, Jika Tidak Ada/Tidak Terealisasi = Skor 1)</option>
                        <option value="minimize">Minimize — (Contoh: Jika Ada/Terjadi = Skor 1, Jika Tidak Ada/Tidak Terjadi = Skor 4)</option>
                      </select>
                    </div>
                  </div>
                  <div class="mb-2 polarity-field" data-for="tertimbang">
                    <div class="alert py-2 mb-0" style="font-size:10px;background:#E6F1FB;border:1px solid #2E75B6;color:#1F4E79">
                      Skor Akhir = Skor Indikator (Realisasi/Target) × Pengkali (dari persentase Rata-rata Harian saat penilaian). Tidak ada konfigurasi tambahan di sini.
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
                  <div class="alert py-2 mb-3" style="font-size:11px;background:#E6F1FB;border:1px solid #2E75B6;color:#1F4E79">
                    Bobot & Target diatur di menu "Master Target", bukan di sini.
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
                    <div class="col-12">
                      <label class="form-label fw-semibold small">Satuan</label>
                      <input type="text" name="satuan" class="form-control form-control-sm"
                             value="<?= esc($t['satuan'] ?? '') ?>"
                             placeholder="%, Rp Juta, Skor ...">
                    </div>
                  </div>
                  <div class="row g-2 mb-2">
                    <div class="col-12">
                      <label class="form-label fw-semibold small">
                        Polarity <span class="text-danger">*</span>
                      </label>
                      <select name="polarity" class="form-select form-select-sm sel-polarity-turunan">
                        <option value="max"        <?= ($t['polarity'] ?? 'max') === 'max'        ? 'selected' : '' ?>>↑ Max (lebih besar lebih baik)</option>
                        <option value="min"        <?= ($t['polarity'] ?? 'max') === 'min'        ? 'selected' : '' ?>>↓ Min (lebih kecil lebih baik)</option>
                        <option value="precise"    <?= ($t['polarity'] ?? 'max') === 'precise'    ? 'selected' : '' ?>>◎ Precise is Better</option>
                        <option value="special"    <?= ($t['polarity'] ?? 'max') === 'special'    ? 'selected' : '' ?>>⚑ Special Scoring</option>
                        <option value="tertimbang" <?= ($t['polarity'] ?? 'max') === 'tertimbang' ? 'selected' : '' ?>>⚖ Scoring Tertimbang</option>
                      </select>
                    </div>
                  </div>
                  <div class="row g-2 mb-2 polarity-field" data-for="max,min">
                    <div class="col-12">
                      <label class="form-label fw-semibold small">
                        Perubahan Polarity <span class="text-danger">*</span>
                      </label>
                      <select name="perubahan_polarity" class="form-select form-select-sm">
                        <option value="pos" <?= ($t['perubahan_polarity'] ?? 'pos') === 'pos' ? 'selected' : '' ?>>Positif</option>
                        <option value="neg" <?= ($t['perubahan_polarity'] ?? 'pos') === 'neg' ? 'selected' : '' ?>>Negatif</option>
                      </select>
                    </div>
                  </div>
                  <div class="mb-2 polarity-field" data-for="precise">
                    <div class="alert py-2 mb-2" style="font-size:10px;background:#E6F1FB;border:1px solid #2E75B6;color:#1F4E79">
                      Toleransi deviasi (%) dari target, simetris. Skor 1 otomatis di luar Toleransi Skor 2.
                    </div>
                    <div class="row g-2">
                      <div class="col-4">
                        <label class="form-label fw-semibold small">Skor 4 (±%)</label>
                        <input type="number" name="toleransi_skor4" class="form-control form-control-sm" step="any" min="0"
                               value="<?= esc($t['toleransi_skor4'] ?? '') ?>" placeholder="2.5">
                      </div>
                      <div class="col-4">
                        <label class="form-label fw-semibold small">Skor 3 (±%)</label>
                        <input type="number" name="toleransi_skor3" class="form-control form-control-sm" step="any" min="0"
                               value="<?= esc($t['toleransi_skor3'] ?? '') ?>" placeholder="7.5">
                      </div>
                      <div class="col-4">
                        <label class="form-label fw-semibold small">Skor 2 (±%)</label>
                        <input type="number" name="toleransi_skor2" class="form-control form-control-sm" step="any" min="0"
                               value="<?= esc($t['toleransi_skor2'] ?? '') ?>" placeholder="12.5">
                      </div>
                    </div>
                  </div>
                  <div class="row g-2 mb-2 polarity-field" data-for="special">
                    <div class="col-12">
                      <label class="form-label fw-semibold small">Sifat <span class="text-danger">*</span></label>
                      <select name="sifat_khusus" class="form-select form-select-sm">
                        <option value="maximize" <?= ($t['sifat_khusus'] ?? 'maximize') === 'maximize' ? 'selected' : '' ?>>Maximize — (Contoh: Jika Ada/Terealisasi = Skor 4, Jika Tidak Ada/Tidak Terealisasi = Skor 1)</option>
                        <option value="minimize" <?= ($t['sifat_khusus'] ?? 'maximize') === 'minimize' ? 'selected' : '' ?>>Minimize — (Contoh: Jika Ada/Terjadi = Skor 1, Jika Tidak Ada/Tidak Terjadi = Skor 4)</option>
                      </select>
                    </div>
                  </div>
                  <div class="mb-2 polarity-field" data-for="tertimbang">
                    <div class="alert py-2 mb-0" style="font-size:10px;background:#E6F1FB;border:1px solid #2E75B6;color:#1F4E79">
                      Skor Akhir = Skor Indikator (Realisasi/Target) × Pengkali (dari persentase Rata-rata Harian saat penilaian). Tidak ada konfigurasi tambahan di sini.
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
            Bobot & Target di Master Target <strong>tidak ikut disalin</strong> —
            lengkapi lagi di menu Master Target setelah menyalin.
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

// Tampilkan/sembunyikan field tambahan (Perubahan Polarity / Toleransi
// Precise / Sifat Special) sesuai Polarity yang dipilih pada tiap modal
// Tambah/Edit Parameter Turunan — di-scope per modal (.modal-content)
// karena ada satu select serupa per KPI Induk.
document.querySelectorAll('.sel-polarity-turunan').forEach(function (sel) {
    const modalContent = sel.closest('.modal-content');
    if (!modalContent) return;

    function toggleTurunanFields() {
        const polarity = sel.value;
        modalContent.querySelectorAll('.polarity-field').forEach(function (el) {
            const forList = (el.getAttribute('data-for') || '').split(',');
            const active  = forList.includes(polarity);
            el.style.display = active ? '' : 'none';
            el.querySelectorAll('input, select').forEach(function (field) {
                field.disabled = !active;
            });
        });
    }

    sel.addEventListener('change', toggleTurunanFields);
    toggleTurunanFields();
});
</script>
